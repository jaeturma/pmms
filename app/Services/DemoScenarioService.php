<?php

namespace App\Services;

use App\Enums\ResultStatus;
use App\Models\DemoScenario;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\FileUpload;
use App\Models\Meet;
use App\Models\ScoringSession;
use App\Models\ResultAttachment;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Facades\DB;

class DemoScenarioService
{
    public function __construct(private readonly AuditLogger $audit, private readonly FileUploadService $uploads) {}

    public function generate(array $data, User $actor): DemoScenario
    {
        return DB::transaction(function () use ($data, $actor): DemoScenario {
            $existing = DemoScenario::query()->where('request_token', $data['request_token'])->first();
            if ($existing !== null) return $existing;

            $meet = Meet::query()->findOrFail($data['meet_id']);
            $sport = Sport::query()->where('active', true)->findOrFail($data['sport_id']);
            $venue = Venue::query()->where('active', true)->findOrFail($data['venue_id']);
            $scenario = DemoScenario::query()->create([
                'meet_id' => $meet->id, 'sport_id' => $sport->id, 'created_by' => $actor->id,
                'request_token' => $data['request_token'], 'name' => $data['name'], 'template' => $data['template'],
            ]);
            $event = Event::query()->create([
                'sport_id' => $sport->id,
                'name' => 'DEMO — '.$data['event_name'].' #'.$scenario->id,
                'gender' => $data['gender'], 'age_division' => $data['age_division'],
                'is_team_event' => $data['template'] !== 'performance',
                'max_entries_per_delegation' => 4,
            ]);
            $event->forceFill(['demo_scenario_id' => $scenario->id, 'active' => true])->save();
            $meet->events()->syncWithoutDetaching([$event->id]);

            $schedule = EventSchedule::query()->create([
                'meet_id' => $meet->id, 'event_id' => $event->id, 'venue_id' => $venue->id,
                'scheduled_date' => $data['scheduled_date'], 'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'], 'note' => 'DEMO DATA — presentation only',
            ]);
            $schedule->forceFill(['demo_scenario_id' => $scenario->id])->save();

            $match = EventMatch::query()->create([
                'meet_id' => $meet->id, 'event_id' => $event->id, 'event_schedule_id' => $schedule->id,
                'round_label' => $data['template'] === 'performance' ? 'Demo Final' : 'Demo Match',
                'sequence' => 1, 'live_scoring_enabled' => $data['template'] !== 'performance', 'awards_medals' => false,
            ]);
            $match->forceFill(['demo_scenario_id' => $scenario->id])->save();

            $session = ScoringSession::query()->create([
                'match_id' => $match->id, 'side_a_label' => $data['side_a_label'], 'side_b_label' => $data['side_b_label'],
            ]);
            $session->forceFill(['started_by' => $actor->id, 'started_at' => now()])->save();

            $result = EventResult::query()->forceCreate([
                'meet_id' => $meet->id, 'event_id' => $event->id, 'match_id' => $match->id,
                'event_schedule_id' => $schedule->id, 'scoring_session_id' => $session->id,
                'result_source' => 'demo', 'result_scope' => 'match',
                'demo_scenario_id' => $scenario->id, 'status' => ResultStatus::Encoded,
                'encoded_by' => $actor->id, 'encoded_at' => now(),
            ]);

            $this->audit->record('demo.scenario_generated', $scenario, [
                'sport' => $sport->name, 'event_id' => $event->id, 'match_id' => $match->id, 'result_id' => $result->id,
            ], $actor);

            return $scenario;
        });
    }

    public function remove(DemoScenario $scenario, User $actor): void
    {
        $counts = $this->counts($scenario);
        DB::transaction(function () use ($scenario): void {
            $results = EventResult::query()->demo()->where('demo_scenario_id', $scenario->id)->with('attachments.file')->get();
            $files = $results->flatMap->attachments->pluck('file')->filter()->unique('id');
            $results->each->delete();
            $files->each(function (FileUpload $file): void {
                if (ResultAttachment::query()->where('file_upload_id', $file->id)->doesntExist()) $this->uploads->delete($file);
            });
            ScoringSession::query()->whereHas('match', fn ($q) => $q->where('demo_scenario_id', $scenario->id))->delete();
            EventMatch::query()->demo()->where('demo_scenario_id', $scenario->id)->delete();
            EventSchedule::query()->demo()->where('demo_scenario_id', $scenario->id)->delete();
            $events = Event::query()->demo()->where('demo_scenario_id', $scenario->id)->get();
            DB::table('meet_events')->whereIn('event_id', $events->modelKeys())->delete();
            $events->each->delete();
            $scenario->delete();
        });
        $this->audit->record('demo.removed', null, $counts, $actor);
    }

    public function counts(DemoScenario $scenario): array
    {
        return ['events' => $scenario->events()->count(), 'schedules' => $scenario->schedules()->count(),
            'matches' => $scenario->matches()->count(), 'results' => $scenario->results()->count()];
    }
}
