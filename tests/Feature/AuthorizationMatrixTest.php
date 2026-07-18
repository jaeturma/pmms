<?php

use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\District;
use App\Models\EligibilityReview;
use App\Models\Entry;
use App\Models\Event;
use App\Models\Meet;
use App\Models\Personnel;
use App\Models\School;
use App\Models\Sport;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * WP-02-11 sweep: the enforced behavior for every forbidden role × action
 * combination must match the documented matrix in docs/authorization.md.
 */
function forbiddenActor(string $role): User
{
    return match ($role) {
        'delegation officer' => User::factory()->delegationOfficer()->create(),
        default => User::factory()->create(),
    };
}

test('meet-data management is denied to viewers and delegation officers', function (string $role, array $case) {
    [$method, $uri] = $case;

    $this->actingAs(forbiddenActor($role))
        ->{$method}($uri)
        ->assertForbidden();
})
    ->with(['viewer', 'delegation officer'])
    ->with([
        'district create' => fn (): array => ['post', '/districts'],
        'district update' => fn (): array => ['put', '/districts/'.District::factory()->create()->id],
        'district archive' => fn (): array => ['patch', '/districts/'.District::factory()->create()->id.'/archive'],
        'district restore' => fn (): array => ['patch', '/districts/'.District::factory()->archived()->create()->id.'/restore'],
        'district delete' => fn (): array => ['delete', '/districts/'.District::factory()->create()->id],
        'school create' => fn (): array => ['post', '/schools'],
        'school update' => fn (): array => ['put', '/schools/'.School::factory()->create()->id],
        'school archive' => fn (): array => ['patch', '/schools/'.School::factory()->create()->id.'/archive'],
        'school restore' => fn (): array => ['patch', '/schools/'.School::factory()->create()->id.'/restore'],
        'school delete' => fn (): array => ['delete', '/schools/'.School::factory()->create()->id],
        'sport create' => fn (): array => ['post', '/sports'],
        'sport update' => fn (): array => ['put', '/sports/'.Sport::factory()->create()->id],
        'sport archive' => fn (): array => ['patch', '/sports/'.Sport::factory()->create()->id.'/archive'],
        'sport restore' => fn (): array => ['patch', '/sports/'.Sport::factory()->create()->id.'/restore'],
        'sport delete' => fn (): array => ['delete', '/sports/'.Sport::factory()->create()->id],
        'event create' => fn (): array => ['post', '/events'],
        'event update' => fn (): array => ['put', '/events/'.Event::factory()->create()->id],
        'event archive' => fn (): array => ['patch', '/events/'.Event::factory()->create()->id.'/archive'],
        'event restore' => fn (): array => ['patch', '/events/'.Event::factory()->create()->id.'/restore'],
        'event delete' => fn (): array => ['delete', '/events/'.Event::factory()->create()->id],
        'meet create' => fn (): array => ['post', '/meets'],
        'meet update' => fn (): array => ['put', '/meets/'.Meet::factory()->create()->id],
        'meet status' => fn (): array => ['patch', '/meets/'.Meet::factory()->create()->id.'/status'],
        'meet events sync' => fn (): array => ['put', '/meets/'.Meet::factory()->create()->id.'/events'],
        'meet delete' => fn (): array => ['delete', '/meets/'.Meet::factory()->create()->id],
        'delegation create' => fn (): array => ['post', '/delegations'],
        'delegation delete' => fn (): array => ['delete', '/delegations/'.Delegation::factory()->create()->id],
    ]);

test('viewers cannot reach minor-related data', function (string $uri) {
    $this->actingAs(User::factory()->create())
        ->get($uri)
        ->assertForbidden();
})->with([
    'athlete registry' => fn (): string => '/athletes',
    'athlete profile' => fn (): string => '/athletes/'.Athlete::factory()->create()->id,
    'athlete photo' => fn (): string => '/athletes/'.Athlete::factory()->create()->id.'/photo',
    'personnel registry' => fn (): string => '/personnel',
    'personnel photo' => fn (): string => '/personnel/'.Personnel::factory()->create()->id.'/photo',
    'entry list' => fn (): string => '/entries',
    'eligibility list' => fn (): string => '/eligibility',
]);

test('delegation officers cannot make manager-only decisions', function (User $user, string $method, string $uri) {
    $this->actingAs($user)->{$method}($uri)->assertForbidden();
})->with([
    'approve their own delegation' => function (): array {
        $delegation = Delegation::factory()->create();
        $officer = User::factory()->delegationOfficer()->create();
        $delegation->officers()->attach($officer);

        return [$officer, 'patch', "/delegations/{$delegation->id}/approve"];
    },
    'return their own delegation' => function (): array {
        $delegation = Delegation::factory()->create();
        $officer = User::factory()->delegationOfficer()->create();
        $delegation->officers()->attach($officer);

        return [$officer, 'patch', "/delegations/{$delegation->id}/return"];
    },
    'assign delegation officers' => function (): array {
        $delegation = Delegation::factory()->create();
        $officer = User::factory()->delegationOfficer()->create();
        $delegation->officers()->attach($officer);

        return [$officer, 'put', "/delegations/{$delegation->id}/officers"];
    },
    'submit a foreign delegation' => function (): array {
        return [
            User::factory()->delegationOfficer()->create(),
            'patch',
            '/delegations/'.Delegation::factory()->create()->id.'/submit',
        ];
    },
    'confirm their own entry' => function (): array {
        $entry = Entry::factory()->create();
        $officer = User::factory()->delegationOfficer()->create();
        $entry->delegation->officers()->attach($officer);

        return [$officer, 'patch', "/entries/{$entry->id}/confirm"];
    },
    'approve an eligibility review' => function (): array {
        $review = EligibilityReview::factory()->create();
        $officer = User::factory()->delegationOfficer()->create();
        $review->athlete->delegation->officers()->attach($officer);

        return [$officer, 'patch', "/eligibility/reviews/{$review->id}/approve"];
    },
    'return an eligibility review' => function (): array {
        $review = EligibilityReview::factory()->create();
        $officer = User::factory()->delegationOfficer()->create();
        $review->athlete->delegation->officers()->attach($officer);

        return [$officer, 'patch', "/eligibility/reviews/{$review->id}/return"];
    },
]);

test('forbidden module access renders the permission denied page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/athletes')
        ->assertForbidden()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('error')
            ->where('status', 403));
});
