<?php

use App\Enums\EquipmentIssueStatus;
use App\Enums\InventoryAdjustmentType;
use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Models\AuditLog;
use App\Models\EquipmentCategory;
use App\Models\EquipmentIssue;
use App\Models\EquipmentItem;
use App\Models\EquipmentReturn;
use App\Models\EquipmentTransfer;
use App\Models\InventoryAdjustment;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\Meet;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\QueryException;
use Inertia\Testing\AssertableInertia;

/**
 * Supply/Equipment (WP-REALIGN-10) — quantity-based stock lines, per-meet
 * catalog, venue-to-venue transfers, category-flagged consumable vs
 * returnable issues. See docs/equipment-management.md.
 */
function supplyMember(?Meet $meet = null): User
{
    $meet ??= Meet::factory()->create();
    $team = ManagementTeam::factory()->create(['meet_id' => $meet->id, 'team_type' => ManagementTeamType::Supply]);
    $member = ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'status' => ManagementTeamMemberStatus::Active,
    ]);

    return $member->user;
}

// --- Model relationships ---

test('a meet has many equipment categories, a category has many items', function () {
    $meet = Meet::factory()->create();
    $category = EquipmentCategory::factory()->create(['meet_id' => $meet->id]);
    $item = EquipmentItem::factory()->create(['equipment_category_id' => $category->id]);

    expect($meet->equipmentCategories()->first()->id)->toBe($category->id)
        ->and($category->items()->first()->id)->toBe($item->id)
        ->and($item->category->id)->toBe($category->id);
});

test('a meet cannot have two categories with the same name', function () {
    $meet = Meet::factory()->create();
    EquipmentCategory::factory()->create(['meet_id' => $meet->id, 'name' => 'Basketballs']);

    expect(fn () => EquipmentCategory::factory()->create(['meet_id' => $meet->id, 'name' => 'Basketballs']))
        ->toThrow(QueryException::class);
});

test('available quantity is quantity minus outstanding issues', function () {
    $item = EquipmentItem::factory()->create(['quantity' => 20]);
    $issue = EquipmentIssue::factory()->create(['equipment_item_id' => $item->id, 'quantity' => 8]);
    EquipmentReturn::factory()->create(['equipment_issue_id' => $issue->id, 'quantity' => 3]);

    $item->refresh();

    expect($issue->refresh()->outstandingQuantity())->toBe(5)
        ->and($item->availableQuantity())->toBe(15);
});

test('deleting a category deletes its items', function () {
    $category = EquipmentCategory::factory()->create();
    $item = EquipmentItem::factory()->create(['equipment_category_id' => $category->id]);

    $category->delete();

    expect(EquipmentItem::query()->whereKey($item->id)->exists())->toBeFalse();
});

// --- EquipmentCategoryController ---

test('guests are redirected from the equipment page', function () {
    $this->get('/equipment')->assertRedirect('/login');
});

test('viewers without a supply role cannot view the equipment page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/equipment')
        ->assertForbidden();
});

test('admins, organizers, and active supply team members can view the equipment page', function () {
    $meet = Meet::factory()->create();
    $category = EquipmentCategory::factory()->create(['meet_id' => $meet->id]);
    EquipmentItem::factory()->create(['equipment_category_id' => $category->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/equipment')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('equipment/index')
            ->has('categories', 1)
            ->has('categories.0.items', 1));

    $this->actingAs(supplyMember($meet))
        ->get('/equipment')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('categories', 1));
});

test('a pending (unconfirmed) supply team member cannot view the equipment page', function () {
    $meet = Meet::factory()->create();
    $team = ManagementTeam::factory()->create(['meet_id' => $meet->id, 'team_type' => ManagementTeamType::Supply]);
    $member = ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'status' => ManagementTeamMemberStatus::Pending,
    ]);

    $this->actingAs($member->user)
        ->get('/equipment')
        ->assertForbidden();
});

test('a supply team member from a different meet cannot manage this meet\'s equipment', function () {
    $meet = Meet::factory()->create();
    $category = EquipmentCategory::factory()->create(['meet_id' => $meet->id]);
    $otherMeetSupplyMember = supplyMember();

    $this->actingAs($otherMeetSupplyMember)
        ->post('/equipment-categories', [
            'meet_id' => $meet->id,
            'name' => 'Basketballs',
        ])
        ->assertForbidden();

    expect(EquipmentCategory::query()->where('name', 'Basketballs')->exists())->toBeFalse();
    expect($category)->not->toBeNull();
});

test('organizers can create a category', function () {
    $meet = Meet::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/equipment-categories', [
            'meet_id' => $meet->id,
            'name' => 'Basketballs',
            'is_consumable' => false,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('equipment_categories', [
        'meet_id' => $meet->id,
        'name' => 'Basketballs',
        'is_consumable' => false,
    ]);

    expect(AuditLog::query()->where('action', 'equipment_category.created')->exists())->toBeTrue();
});

test('a supply team member can create a category for their own meet', function () {
    $meet = Meet::factory()->create();

    $this->actingAs(supplyMember($meet))
        ->post('/equipment-categories', [
            'meet_id' => $meet->id,
            'name' => 'First Aid Kits',
            'is_consumable' => true,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('equipment_categories', ['name' => 'First Aid Kits', 'is_consumable' => true]);
});

test('creating a second category with the same name for the same meet fails with a field error', function () {
    $category = EquipmentCategory::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/equipment-categories', [
            'meet_id' => $category->meet_id,
            'name' => $category->name,
        ])
        ->assertSessionHasErrors('name');
});

test('organizers can update and remove a category', function () {
    $category = EquipmentCategory::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put("/equipment-categories/{$category->id}", [
            'name' => 'Renamed',
            'is_consumable' => true,
        ])
        ->assertSessionHasNoErrors();

    expect($category->fresh())->name->toBe('Renamed')->is_consumable->toBeTrue();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/equipment-categories/{$category->id}")
        ->assertSessionHasNoErrors();

    expect(EquipmentCategory::query()->whereKey($category->id)->exists())->toBeFalse();
});

// --- EquipmentItemController ---

test('organizers can add an item to a category', function () {
    $category = EquipmentCategory::factory()->create();
    $venue = Venue::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/equipment-items', [
            'equipment_category_id' => $category->id,
            'venue_id' => $venue->id,
            'quantity' => 20,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('equipment_items', [
        'equipment_category_id' => $category->id,
        'venue_id' => $venue->id,
        'quantity' => 20,
    ]);

    expect(AuditLog::query()->where('action', 'equipment_item.created')->exists())->toBeTrue();
});

test('item update only touches condition and notes, not quantity', function () {
    $item = EquipmentItem::factory()->create(['quantity' => 20]);

    $this->actingAs(User::factory()->admin()->create())
        ->put("/equipment-items/{$item->id}", [
            'condition' => 'damaged',
            'notes' => 'Two nets torn.',
        ])
        ->assertSessionHasNoErrors();

    expect($item->fresh())
        ->quantity->toBe(20)
        ->notes->toBe('Two nets torn.');
});

// --- EquipmentIssueController ---

test('issuing equipment reduces available quantity', function () {
    $item = EquipmentItem::factory()->create(['quantity' => 20]);
    $venue = Venue::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/equipment-issues', [
            'equipment_item_id' => $item->id,
            'venue_id' => $venue->id,
            'quantity' => 5,
            'custodian_name' => 'Coach Dela Cruz',
        ])
        ->assertSessionHasNoErrors();

    expect($item->fresh()->availableQuantity())->toBe(15);
    expect(AuditLog::query()->where('action', 'equipment_issue.created')->exists())->toBeTrue();
});

test('issuing more than the available quantity fails with a field error', function () {
    $item = EquipmentItem::factory()->create(['quantity' => 5]);
    $venue = Venue::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/equipment-issues', [
            'equipment_item_id' => $item->id,
            'venue_id' => $venue->id,
            'quantity' => 6,
        ])
        ->assertSessionHasErrors('quantity');
});

// --- EquipmentReturnController ---

test('returning equipment restores available quantity and updates issue status', function () {
    $item = EquipmentItem::factory()->create(['quantity' => 20]);
    $issue = EquipmentIssue::factory()->create(['equipment_item_id' => $item->id, 'quantity' => 8]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/equipment-returns', [
            'equipment_issue_id' => $issue->id,
            'quantity' => 3,
        ])
        ->assertSessionHasNoErrors();

    expect($issue->fresh()->status)->toBe(EquipmentIssueStatus::PartiallyReturned)
        ->and($item->fresh()->availableQuantity())->toBe(15);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/equipment-returns', [
            'equipment_issue_id' => $issue->id,
            'quantity' => 5,
        ])
        ->assertSessionHasNoErrors();

    expect($issue->fresh()->status)->toBe(EquipmentIssueStatus::Returned)
        ->and($item->fresh()->availableQuantity())->toBe(20);

    expect(AuditLog::query()->where('action', 'equipment_return.created')->count())->toBe(2);
});

test('consumable issues cannot be returned', function () {
    $category = EquipmentCategory::factory()->consumable()->create();
    $item = EquipmentItem::factory()->create(['equipment_category_id' => $category->id, 'quantity' => 20]);
    $issue = EquipmentIssue::factory()->create(['equipment_item_id' => $item->id, 'quantity' => 5]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/equipment-returns', [
            'equipment_issue_id' => $issue->id,
            'quantity' => 5,
        ])
        ->assertSessionHasErrors('equipment_issue_id');

    expect(EquipmentReturn::query()->count())->toBe(0);
});

test('returning more than what is outstanding fails with a field error', function () {
    $issue = EquipmentIssue::factory()->create(['quantity' => 5]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/equipment-returns', [
            'equipment_issue_id' => $issue->id,
            'quantity' => 6,
        ])
        ->assertSessionHasErrors('quantity');
});

// --- EquipmentTransferController ---

test('a full-quantity transfer moves the item to the new venue with no split', function () {
    $fromVenue = Venue::factory()->create();
    $toVenue = Venue::factory()->create();
    $item = EquipmentItem::factory()->create(['venue_id' => $fromVenue->id, 'quantity' => 10]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/equipment-transfers', [
            'equipment_item_id' => $item->id,
            'to_venue_id' => $toVenue->id,
            'quantity' => 10,
        ])
        ->assertSessionHasNoErrors();

    expect($item->fresh()->venue_id)->toBe($toVenue->id)
        ->and(EquipmentItem::query()->count())->toBe(1);

    $transfer = EquipmentTransfer::query()->latest('id')->first();
    expect($transfer->from_venue_id)->toBe($fromVenue->id)
        ->and($transfer->to_venue_id)->toBe($toVenue->id)
        ->and($transfer->quantity)->toBe(10);
});

test('a partial transfer splits the stock line into a new item at the destination venue', function () {
    $fromVenue = Venue::factory()->create();
    $toVenue = Venue::factory()->create();
    $item = EquipmentItem::factory()->create(['venue_id' => $fromVenue->id, 'quantity' => 10]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/equipment-transfers', [
            'equipment_item_id' => $item->id,
            'to_venue_id' => $toVenue->id,
            'quantity' => 4,
        ])
        ->assertSessionHasNoErrors();

    expect($item->fresh())
        ->venue_id->toBe($fromVenue->id)
        ->quantity->toBe(6);

    $newItem = EquipmentItem::query()->where('venue_id', $toVenue->id)->first();
    expect($newItem)->not->toBeNull()
        ->and($newItem->quantity)->toBe(4)
        ->and($newItem->equipment_category_id)->toBe($item->equipment_category_id);

    expect(AuditLog::query()->where('action', 'equipment_transfer.created')->exists())->toBeTrue();
});

test('transferring more than the available quantity fails with a field error', function () {
    $item = EquipmentItem::factory()->create(['quantity' => 10]);
    EquipmentIssue::factory()->create(['equipment_item_id' => $item->id, 'quantity' => 8]);
    $toVenue = Venue::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/equipment-transfers', [
            'equipment_item_id' => $item->id,
            'to_venue_id' => $toVenue->id,
            'quantity' => 5,
        ])
        ->assertSessionHasErrors('quantity');
});

// --- InventoryAdjustmentController ---

test('an inventory adjustment with a reason updates the item quantity directly', function () {
    $item = EquipmentItem::factory()->create(['quantity' => 20]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/inventory-adjustments', [
            'equipment_item_id' => $item->id,
            'type' => InventoryAdjustmentType::Damage->value,
            'quantity_delta' => -3,
            'reason' => 'Three balls punctured during finals.',
        ])
        ->assertSessionHasNoErrors();

    expect($item->fresh()->quantity)->toBe(17);
    expect(AuditLog::query()->where('action', 'inventory_adjustment.created')->exists())->toBeTrue();
});

test('an inventory adjustment requires a reason', function () {
    $item = EquipmentItem::factory()->create(['quantity' => 20]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/inventory-adjustments', [
            'equipment_item_id' => $item->id,
            'type' => InventoryAdjustmentType::Recount->value,
            'quantity_delta' => 2,
            'reason' => '',
        ])
        ->assertSessionHasErrors('reason');

    expect(InventoryAdjustment::query()->count())->toBe(0);
});

test('an adjustment cannot drop the quantity below what is currently issued out', function () {
    $item = EquipmentItem::factory()->create(['quantity' => 10]);
    EquipmentIssue::factory()->create(['equipment_item_id' => $item->id, 'quantity' => 8]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/inventory-adjustments', [
            'equipment_item_id' => $item->id,
            'type' => InventoryAdjustmentType::Loss->value,
            'quantity_delta' => -5,
            'reason' => 'Recount after the meet.',
        ])
        ->assertSessionHasErrors('quantity_delta');

    expect($item->fresh()->quantity)->toBe(10);
});

// --- Authorization sweep ---

test('non-managers cannot mutate equipment', function (User $user) {
    $item = EquipmentItem::factory()->create();

    $this->actingAs($user)
        ->post('/equipment-categories', ['meet_id' => Meet::factory()->create()->id, 'name' => 'X'])
        ->assertForbidden();

    $this->actingAs($user)
        ->post('/equipment-issues', ['equipment_item_id' => $item->id, 'venue_id' => Venue::factory()->create()->id, 'quantity' => 1])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
    'technical official' => fn () => User::factory()->technicalOfficial()->create(),
    'coach' => fn () => User::factory()->coach()->create(),
]);
