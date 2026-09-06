<?php

use App\Enums\AgeDivision;
use App\Enums\GenderCategory;
use App\Enums\Sex;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\Sport;
use App\Models\SportRosterMember;
use App\Models\User;
use App\Services\DataIntegrityService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->withoutVite();
});

function integrityContext(): array
{
    $meet = Meet::factory()->active()->create();
    $sport = Sport::factory()->create(['name' => 'Boxing']);
    $event = Event::factory()->create(['sport_id' => $sport->id, 'is_team_event' => false, 'age_division' => AgeDivision::Secondary, 'gender' => GenderCategory::Boys]);
    $meet->events()->attach($event);
    $scope = MeetSport::firstOrCreate(['meet_id' => $meet->id, 'sport_id' => $sport->id]);
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id, 'sex' => Sex::Male, 'age_division' => AgeDivision::Secondary, 'grade_level' => 9]);
    $roster = SportRosterMember::create(['meet_sport_id' => $scope->id, 'delegation_id' => $delegation->id, 'athlete_id' => $athlete->id, 'level' => AgeDivision::Secondary, 'gender' => GenderCategory::Boys]);
    $admin = User::factory()->admin()->create();

    return compact('meet', 'sport', 'event', 'scope', 'delegation', 'athlete', 'roster', 'admin');
}

function integrityNewAthletePayload(array $c): array
{
    return ['first_name' => 'REPAIR', 'middle_name' => 'N/A', 'last_name' => 'TEST ATHLETE', 'name_extension' => 'None',
        'sex' => 'male', 'birthdate' => now()->subYears(15)->toDateString(), 'lrn' => '987654321012',
        'grade_level' => 9, 'age_division' => 'secondary', 'school_id' => $c['athlete']->school_id,
        'reason' => 'Reviewed registration records and missing identity', 'expected_athlete_id' => $c['athlete']->id, 'confirm_create' => true];
}

test('match selectors exclude archived and truly orphaned athletes and keep the page operational', function (bool $orphan) {
    $c = integrityContext();
    if ($orphan) {
        // Simulate a legacy import with FK enforcement absent; no live database is touched.
        DB::statement('PRAGMA defer_foreign_keys = ON');
        DB::table('sport_roster_members')->where('id', $c['roster']->id)->update(['athlete_id' => 999999]);
    } else {
        $c['athlete']->delete();
    }
    $this->actingAs($c['admin'])->get('/matches')->assertOk()->assertInertia(fn ($page) => $page->has('athleteOptions', 0));
    expect(SportRosterMember::count())->toBe(1);
    expect(app(DataIntegrityService::class)->scan($c['meet']->id)->where('type', 'orphan_roster')->count())->toBe(1);
})->with([false, true]);

test('historical match and scoreboard safely serialize a deleted athlete', function () {
    $c = integrityContext();
    $match = EventMatch::factory()->create(['meet_id' => $c['meet']->id, 'event_id' => $c['event']->id]);
    $entry = Entry::factory()->confirmed()->create(['athlete_id' => $c['athlete']->id, 'delegation_id' => $c['delegation']->id, 'event_id' => $c['event']->id]);
    $match->entries()->attach($entry);
    $c['athlete']->delete();
    $this->actingAs($c['admin'])->get('/matches')->assertOk()->assertInertia(fn ($page) => $page->where('matches.data.0.participants.0.school', 'Not provided'));
    $this->get(route('scoring.board', $match))->assertOk();
});

test('integrity screen is read only and detects broken roster', function () {
    $c = integrityContext();
    $c['athlete']->delete();
    $before = $c['roster']->fresh()->getAttributes();
    $this->actingAs($c['admin'])->get('/administration/data-integrity')->assertOk()->assertInertia(fn ($page) => $page->where('counts.orphan_roster', 1));
    $this->artisan('pmms:audit-data', ['--meet' => $c['meet']->id])->assertSuccessful();
    expect($c['roster']->fresh()->getAttributes())->toBe($before)->and(AuditLog::where('action', 'data_integrity.roster_linked')->count())->toBe(0);
});

test('admin explicitly links a roster with audit and without fabricating entries', function () {
    $c = integrityContext();
    $c['athlete']->delete();
    $replacement = Athlete::factory()->create(['delegation_id' => $c['delegation']->id, 'sex' => Sex::Male, 'age_division' => AgeDivision::Secondary]);
    $this->actingAs($c['admin'])->patch(route('data-integrity.link', $c['roster']), ['athlete_id' => $replacement->id, 'expected_athlete_id' => $c['athlete']->id, 'reason' => 'Verified original registration'])->assertSessionHasNoErrors()->assertRedirect();
    expect($c['roster']->fresh()->athlete_id)->toBe($replacement->id)->and(Entry::count())->toBe(0);
    $audit = AuditLog::where('action', 'data_integrity.roster_linked')->sole();
    expect($audit->user_id)->toBe($c['admin']->id)->and($audit->context['before']['athlete_id'])->toBe($c['athlete']->id)->and($audit->context['after']['athlete_id'])->toBe($replacement->id);
});

test('create and link requires a search and explicit confirmation then audits both records', function () {
    $c = integrityContext();
    $c['athlete']->delete();
    $url = route('data-integrity.create', $c['roster']);
    $payload = integrityNewAthletePayload($c);
    $this->actingAs($c['admin'])->post($url, $payload)->assertStatus(422);
    $this->getJson(route('data-integrity.candidates', $c['roster']).'?search=REPAIR')->assertOk();
    $this->post($url, [...$payload, 'confirm_create' => false])->assertSessionHasErrors('confirm_create');
    $this->post($url, $payload)->assertSessionHasNoErrors()->assertRedirect();
    $new = Athlete::where('lrn', $payload['lrn'])->sole();
    expect($c['roster']->fresh()->athlete_id)->toBe($new->id)->and($new->delegation_id)->toBe($c['delegation']->id)
        ->and(Entry::count())->toBe(0)->and(SportRosterMember::count())->toBe(1)
        ->and(AuditLog::whereIn('action', ['data_integrity.athlete_created', 'data_integrity.roster_linked'])->count())->toBe(2);
});

test('duplicate identities including archived LRN cannot be created during repair', function () {
    $c = integrityContext();
    $c['athlete']->delete();
    $this->actingAs($c['admin'])->getJson(route('data-integrity.candidates', $c['roster']).'?search=REPAIR')->assertOk();
    $payload = integrityNewAthletePayload($c);
    $this->post(route('data-integrity.create', $c['roster']), [...$payload, 'lrn' => $c['athlete']->lrn])->assertSessionHasErrors('lrn');
    expect(Athlete::withTrashed()->count())->toBe(1)->and(Entry::count())->toBe(0);
});

test('coaches cannot inspect search link or create integrity records', function () {
    $c = integrityContext();
    $coach = User::factory()->create(['role' => UserRole::Coach]);
    $this->actingAs($coach)->get(route('data-integrity.index'))->assertForbidden();
    $this->get(route('data-integrity.roster', $c['roster']))->assertForbidden();
    $this->getJson(route('data-integrity.candidates', $c['roster']).'?search=REPAIR')->assertForbidden();
    $this->patch(route('data-integrity.link', $c['roster']), [])->assertForbidden();
    $this->post(route('data-integrity.create', $c['roster']), integrityNewAthletePayload($c))->assertForbidden();
});

test('repair rejects foreign delegation duplicate roster and stale preview', function () {
    $c = integrityContext();
    $c['athlete']->delete();
    $other = Athlete::factory()->create();
    $url = route('data-integrity.link', $c['roster']);
    $payload = ['athlete_id' => $other->id, 'expected_athlete_id' => $c['athlete']->id, 'reason' => 'Verified source record'];
    $this->actingAs($c['admin'])->patch($url, $payload)->assertSessionHasErrors('athlete_id');
    $this->patch($url, [...$payload, 'expected_athlete_id' => 99999])->assertSessionHasErrors('athlete_id');
    expect($c['roster']->fresh()->athlete_id)->toBe($c['athlete']->id);
});
