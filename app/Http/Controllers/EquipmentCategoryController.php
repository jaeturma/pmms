<?php

namespace App\Http\Controllers;

use App\Enums\EquipmentCondition;
use App\Enums\EquipmentIssueStatus;
use App\Enums\InventoryAdjustmentType;
use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\UserRole;
use App\Models\EquipmentCategory;
use App\Models\EquipmentIssue;
use App\Models\EquipmentItem;
use App\Models\ManagementTeamMember;
use App\Models\Meet;
use App\Models\User;
use App\Models\Venue;
use App\Policies\SupplyPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Management UI for the Supply Team's equipment catalog (WP-REALIGN-10) —
 * see docs/equipment-management.md. `EquipmentCategory` is this page's
 * primary entity, same role `ManagementTeam` plays on its own page;
 * items/issues/returns/transfers/adjustments are managed inline from
 * here via their own single-purpose controllers.
 */
class EquipmentCategoryController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly SupplyPolicy $policy,
    ) {}

    /**
     * All categories the user can see — Admin/Organizer see every meet
     * (optionally filtered), everyone else only meets where they hold an
     * Active Supply Team membership. Unlike `ManagementTeamController`,
     * this page has no read-only mode: reaching it at all already means
     * `canManage` is true for everything shown (see `SupplyPolicy`'s own
     * docblock).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless($this->policy->viewAny($user), 403);

        $accessibleMeetIds = $this->accessibleMeetIds($user);

        $query = EquipmentCategory::query()
            ->with([
                'items.venue:id,name',
                'items.issues.venue:id,name',
                'items.issues.returns',
            ])
            ->when($accessibleMeetIds !== null, fn ($q) => $q->whereIn('meet_id', $accessibleMeetIds))
            ->orderBy('name');

        return Inertia::render('equipment/index', [
            'categories' => $query->get()->map(fn (EquipmentCategory $category): array => $this->categoryRow($category)),
            'venueOptions' => Venue::query()->where('active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn (Venue $venue): array => ['id' => $venue->id, 'label' => $venue->name]),
            'conditionOptions' => array_map(
                fn (EquipmentCondition $condition): array => ['value' => $condition->value, 'label' => $condition->label()],
                EquipmentCondition::cases(),
            ),
            'adjustmentTypeOptions' => array_map(
                fn (InventoryAdjustmentType $type): array => ['value' => $type->value, 'label' => $type->label()],
                InventoryAdjustmentType::cases(),
            ),
        ]);
    }

    /**
     * @return array{id: int, name: string, description: string|null, is_consumable: bool, items: array<int, array<string, mixed>>}
     */
    private function categoryRow(EquipmentCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'is_consumable' => $category->is_consumable,
            'items' => $category->items->map(fn (EquipmentItem $item): array => $this->itemRow($item))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function itemRow(EquipmentItem $item): array
    {
        return [
            'id' => $item->id,
            'venue' => $item->venue?->name,
            'venue_id' => $item->venue_id,
            'quantity' => $item->quantity,
            'available' => $item->availableQuantity(),
            'condition' => $item->condition?->value,
            'condition_label' => $item->condition?->label(),
            'notes' => $item->notes,
            'open_issues' => $item->issues
                ->filter(fn (EquipmentIssue $issue): bool => $issue->status !== EquipmentIssueStatus::Returned)
                ->map(fn (EquipmentIssue $issue): array => [
                    'id' => $issue->id,
                    'venue' => $issue->venue->name,
                    'quantity' => $issue->quantity,
                    'outstanding' => $issue->outstandingQuantity(),
                    'custodian_name' => $issue->custodian_name,
                    'status' => $issue->status->value,
                    'status_label' => $issue->status->label(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return Collection<int, int>|null Null means unrestricted (Admin/Organizer).
     */
    private function accessibleMeetIds(User $user): ?Collection
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return null;
        }

        return ManagementTeamMember::query()
            ->where('user_id', $user->id)
            ->where('status', ManagementTeamMemberStatus::Active)
            ->whereHas('managementTeam', fn ($q) => $q->where('team_type', ManagementTeamType::Supply))
            ->with('managementTeam:id,meet_id')
            ->get()
            ->pluck('managementTeam.meet_id')
            ->unique()
            ->values();
    }

    /**
     * Create a category for a meet (at most one per (meet_id, name)).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_consumable' => ['boolean'],
        ]);

        $meet = Meet::current();
        abort_unless($this->policy->manage($request->user(), $meet), 403);

        if (EquipmentCategory::query()
            ->where('meet_id', $meet->id)
            ->where('name', $validated['name'])
            ->exists()) {
            throw ValidationException::withMessages([
                'name' => __('This meet already has a category with that name.'),
            ]);
        }

        $category = EquipmentCategory::create([
            'meet_id' => $meet->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_consumable' => $request->boolean('is_consumable'),
        ]);

        $this->audit->record('equipment_category.created', $category, [
            'meet' => $meet->name,
            'name' => $category->name,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return back();
    }

    /**
     * Update a category's name, description, or consumable flag.
     */
    public function update(Request $request, EquipmentCategory $equipmentCategory): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $equipmentCategory->meet), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_consumable' => ['boolean'],
        ]);

        $equipmentCategory->fill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_consumable' => $request->boolean('is_consumable'),
        ])->save();

        $this->audit->record('equipment_category.updated', $equipmentCategory, [
            'meet' => $equipmentCategory->meet->name,
            'name' => $equipmentCategory->name,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return back();
    }

    /**
     * Remove a category outright — cascades to its items and every
     * item's issues/returns/transfers/adjustments.
     */
    public function destroy(Request $request, EquipmentCategory $equipmentCategory): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $equipmentCategory->meet), 403);

        $context = [
            'meet' => $equipmentCategory->meet->name,
            'name' => $equipmentCategory->name,
        ];

        $equipmentCategory->delete();

        $this->audit->record('equipment_category.deleted', $equipmentCategory, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category removed.')]);

        return back();
    }
}
