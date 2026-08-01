<?php

use App\Models\Accreditation;
use App\Models\Announcement;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\District;
use App\Models\EligibilityReview;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\Incident;
use App\Models\Meet;
use App\Models\Personnel;
use App\Models\Protest;
use App\Models\School;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use Inertia\Testing\AssertableInertia;

/**
 * WP-02-11 sweep: the enforced behavior for every forbidden role × action
 * combination must match the documented matrix in docs/authorization.md.
 */
function forbiddenActor(string $role): User
{
    return match ($role) {
        'delegation officer' => User::factory()->delegationOfficer()->create(),
        'technical official' => User::factory()->technicalOfficial()->create(),
        default => User::factory()->create(),
    };
}

/**
 * A Technical Official is scoped only to live scoring for their assigned
 * sport (see ScoringSessionTest.php for that behavior) — every action
 * below is still denied to them, same as a viewer or delegation officer.
 */
test('meet-data management is denied to viewers, delegation officers, and technical officials', function (string $role, array $case) {
    [$method, $uri] = $case;

    $this->actingAs(forbiddenActor($role))
        ->{$method}($uri)
        ->assertForbidden();
})
    ->with(['viewer', 'delegation officer', 'technical official'])
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
        'sport technical officials' => fn (): array => ['put', '/sports/'.Sport::factory()->create()->id.'/technical-officials'],
        'event create' => fn (): array => ['post', '/events'],
        'event update' => fn (): array => ['put', '/events/'.Event::factory()->create()->id],
        'event archive' => fn (): array => ['patch', '/events/'.Event::factory()->create()->id.'/archive'],
        'event restore' => fn (): array => ['patch', '/events/'.Event::factory()->create()->id.'/restore'],
        'event delete' => fn (): array => ['delete', '/events/'.Event::factory()->create()->id],
        'venue create' => fn (): array => ['post', '/venues'],
        'venue update' => fn (): array => ['put', '/venues/'.Venue::factory()->create()->id],
        'venue archive' => fn (): array => ['patch', '/venues/'.Venue::factory()->create()->id.'/archive'],
        'venue restore' => fn (): array => ['patch', '/venues/'.Venue::factory()->archived()->create()->id.'/restore'],
        'venue delete' => fn (): array => ['delete', '/venues/'.Venue::factory()->create()->id],
        'schedule create' => fn (): array => ['post', '/schedule'],
        'schedule update' => fn (): array => ['put', '/schedule/'.EventSchedule::factory()->create()->id],
        'schedule delete' => fn (): array => ['delete', '/schedule/'.EventSchedule::factory()->create()->id],
        'meet create' => fn (): array => ['post', '/meets'],
        'meet update' => fn (): array => ['put', '/meets/'.Meet::factory()->create()->id],
        'meet status' => fn (): array => ['patch', '/meets/'.Meet::factory()->create()->id.'/status'],
        'meet events sync' => fn (): array => ['put', '/meets/'.Meet::factory()->create()->id.'/events'],
        'meet delete' => fn (): array => ['delete', '/meets/'.Meet::factory()->create()->id],
        'meet publish' => fn (): array => ['patch', '/meets/'.Meet::factory()->active()->create()->id.'/publish'],
        'meet unpublish' => fn (): array => ['patch', '/meets/'.Meet::factory()->active()->published()->create()->id.'/unpublish'],
        'delegation create' => fn (): array => ['post', '/delegations'],
        'delegation delete' => fn (): array => ['delete', '/delegations/'.Delegation::factory()->create()->id],
        'accreditation grant' => fn (): array => ['post', '/accreditations'],
        'accreditation revoke' => fn (): array => ['delete', '/accreditations/'.Accreditation::factory()->create()->id],
        'accreditation view (unassigned)' => fn (): array => ['get', '/delegations/'.Delegation::factory()->approved()->create()->id.'/accreditation'],
        'accreditation batch cards (unassigned)' => fn (): array => ['get', '/delegations/'.Delegation::factory()->approved()->create()->id.'/accreditation/cards'],
        'accreditation card (unassigned)' => fn (): array => ['get', '/accreditations/'.Accreditation::factory()->create()->id.'/card'],
        'match create' => fn (): array => ['post', '/matches'],
        'match update' => fn (): array => ['put', '/matches/'.EventMatch::factory()->create()->id],
        'match participants' => fn (): array => ['put', '/matches/'.EventMatch::factory()->create()->id.'/participants'],
        'match status' => fn (): array => ['patch', '/matches/'.EventMatch::factory()->create()->id.'/status'],
        'match delete' => fn (): array => ['delete', '/matches/'.EventMatch::factory()->create()->id],
        // Not 'result encode'/'result update': since Phase 16 those are
        // no longer flatly forbidden for a technical official (allowed
        // for their own assigned sport) — this shared sweep sends no
        // body, which would 422 rather than 403 for that role and break
        // the assertion below. Viewer/delegation-officer coverage for
        // those two actions, and the technical-official scoping nuance,
        // both live in ResultTest.php instead.
        'result validate' => fn (): array => ['patch', '/results/'.EventResult::factory()->create()->id.'/validate'],
        'result correct' => fn (): array => ['patch', '/results/'.EventResult::factory()->validated()->create()->id.'/correct'],
        'result delete' => fn (): array => ['delete', '/results/'.EventResult::factory()->create()->id],
        'protest review' => fn (): array => ['patch', '/protests/'.Protest::factory()->create()->id.'/review'],
        'protest decide' => fn (): array => ['patch', '/protests/'.Protest::factory()->underReview()->create()->id.'/decide'],
        'incident list' => fn (): array => ['get', '/incidents'],
        'incident create' => fn (): array => ['post', '/incidents'],
        'incident update' => fn (): array => ['put', '/incidents/'.Incident::factory()->create()->id],
        'incident resolve' => fn (): array => ['patch', '/incidents/'.Incident::factory()->create()->id.'/resolve'],
        'incident reopen' => fn (): array => ['patch', '/incidents/'.Incident::factory()->resolved()->create()->id.'/reopen'],
        'incident delete' => fn (): array => ['delete', '/incidents/'.Incident::factory()->create()->id],
        'announcement list' => fn (): array => ['get', '/announcements'],
        'announcement create' => fn (): array => ['post', '/announcements'],
        'announcement update' => fn (): array => ['put', '/announcements/'.Announcement::factory()->create()->id],
        'announcement publish' => fn (): array => ['patch', '/announcements/'.Announcement::factory()->create()->id.'/publish'],
        'announcement unpublish' => fn (): array => ['patch', '/announcements/'.Announcement::factory()->published()->create()->id.'/unpublish'],
        'announcement delete' => fn (): array => ['delete', '/announcements/'.Announcement::factory()->create()->id],
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
