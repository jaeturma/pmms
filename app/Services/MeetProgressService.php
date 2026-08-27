<?php

namespace App\Services;

use App\Enums\ResultStatus;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\MedalAward;
use App\Models\Meet;
use Illuminate\Support\Collection;

class MeetProgressService
{
    /** @return array<string, mixed> */
    public function summary(Meet $meet): array
    {
        $events = $this->events($meet);
        $configured = $events->filter(fn (Event $event): bool => $event->medalConfig?->awards_medals === true
            && $event->medalConfig->isComplete());
        $issues = $events->filter(fn (Event $event): bool => $event->is_medal_event
            && ($event->medalConfig === null || ! $event->medalConfig->isComplete()));

        $expected = $this->expected($configured);
        $awardedBySport = $this->awardedBySport($meet);
        $resultsBySport = EventResult::query()
            ->join('events', 'events.id', '=', 'event_results.event_id')
            ->where('event_results.meet_id', $meet->id)
            ->get(['event_results.event_id', 'event_results.status', 'events.sport_id'])
            ->groupBy('sport_id');
        $awarded = $this->sumMedals($awardedBySport->values());
        $mismatch = collect(['gold', 'silver', 'bronze'])->contains(
            fn (string $medal): bool => $awarded[$medal] > $expected[$medal],
        );

        $sports = $configured
            ->groupBy('sport_id')
            ->map(function (Collection $sportEvents, int $sportId) use ($awardedBySport, $resultsBySport): array {
                $expected = $this->expected($sportEvents);
                $awarded = $awardedBySport->get($sportId, $this->emptyMedals());
                $results = $resultsBySport->get($sportId, collect());
                $percentage = $this->percentage($awarded['total'], $expected['total']);
                $hasMismatch = $awarded['total'] > $expected['total'];

                return [
                    'sport_id' => $sportId,
                    'sport' => $sportEvents->first()->sport->name,
                    'expected' => $expected,
                    'awarded' => $awarded,
                    'remaining' => $this->remaining($expected, $awarded),
                    'percentage' => $percentage,
                    'status' => $this->status($percentage, $hasMismatch),
                    'official_events' => $results->where('status', ResultStatus::Official)->pluck('event_id')->unique()->count(),
                    'pending_results' => $results->whereIn('status', [ResultStatus::Encoded, ResultStatus::Submitted, ResultStatus::Returned, ResultStatus::Validated])->count(),
                ];
            })
            ->sortBy('sport')
            ->values()
            ->all();

        $percentage = $this->percentage($awarded['total'], $expected['total']);

        return [
            'expected' => $expected,
            'awarded' => $awarded,
            'remaining' => $this->remaining($expected, $awarded),
            'percentage' => $percentage,
            'status' => $this->status($percentage, $mismatch || $issues->isNotEmpty()),
            'data_review_required' => $mismatch,
            'configuration' => [
                'configured_events' => $configured->count(),
                'missing_events' => $issues->count(),
                'complete' => $issues->isEmpty(),
                'issues' => $issues->map(fn (Event $event): array => [
                    'event_id' => $event->id,
                    'sport' => $event->sport->name,
                    'event' => $event->name,
                    'issue' => $event->medalConfig === null ? 'Missing medal configuration' : 'Incomplete medal configuration',
                ])->values()->all(),
            ],
            'sports' => $sports,
            'results' => $this->resultCounts($meet),
            'last_official_result' => ($lastOfficial = EventResult::query()
                ->where('meet_id', $meet->id)->where('status', ResultStatus::Official->value)
                ->with(['event:id,sport_id,name', 'event.sport:id,name'])
                ->latest('official_at')->first()) === null ? null : [
                    'id' => $lastOfficial->id,
                    'sport' => $lastOfficial->event->sport->name,
                    'event' => $lastOfficial->event->name,
                    'official_at' => $lastOfficial->official_at?->toDayDateTimeString(),
                    'reference' => $lastOfficial->referenceNumber(),
                ],
        ];
    }

    /** @return Collection<int, Event> */
    private function events(Meet $meet): Collection
    {
        return $meet->events()->where('events.active', true)
            ->with(['sport:id,name', 'medalConfig'])->get();
    }

    /** @param Collection<int, Event> $events @return array<string, int> */
    private function expected(Collection $events): array
    {
        $medals = $this->emptyMedals();
        foreach ($events as $event) {
            $medals['gold'] += (int) $event->medalConfig->gold_tally_quantity;
            $medals['silver'] += (int) $event->medalConfig->silver_tally_quantity;
            $medals['bronze'] += (int) $event->medalConfig->bronze_tally_quantity;
        }
        $medals['total'] = $medals['gold'] + $medals['silver'] + $medals['bronze'];

        return $medals;
    }

    /** @return Collection<int, array<string, int>> */
    private function awardedBySport(Meet $meet): Collection
    {
        return MedalAward::query()
            ->join('event_results', 'event_results.id', '=', 'medal_awards.event_result_id')
            ->join('events', 'events.id', '=', 'event_results.event_id')
            ->where('event_results.meet_id', $meet->id)
            ->where('event_results.status', ResultStatus::Official->value)
            ->selectRaw('events.sport_id, medal_awards.medal_type, SUM(medal_awards.tally_quantity) as quantity')
            ->groupBy('events.sport_id', 'medal_awards.medal_type')
            ->get()
            ->groupBy('sport_id')
            ->map(function (Collection $rows): array {
                $medals = $this->emptyMedals();
                foreach ($rows as $row) {
                    $medal = strtolower((string) $row->medal_type);
                    if (array_key_exists($medal, $medals)) {
                        $medals[$medal] += (int) $row->quantity;
                    }
                }
                $medals['total'] = $medals['gold'] + $medals['silver'] + $medals['bronze'];

                return $medals;
            });
    }

    /** @param Collection<int, array<string, int>> $rows @return array<string, int> */
    private function sumMedals(Collection $rows): array
    {
        $medals = $this->emptyMedals();
        foreach (['gold', 'silver', 'bronze'] as $medal) {
            $medals[$medal] = (int) $rows->sum($medal);
        }
        $medals['total'] = $medals['gold'] + $medals['silver'] + $medals['bronze'];

        return $medals;
    }

    /** @return array<string, int> */
    private function remaining(array $expected, array $awarded): array
    {
        $remaining = $this->emptyMedals();
        foreach (['gold', 'silver', 'bronze'] as $medal) {
            $remaining[$medal] = max(0, $expected[$medal] - $awarded[$medal]);
        }
        $remaining['total'] = $remaining['gold'] + $remaining['silver'] + $remaining['bronze'];

        return $remaining;
    }

    private function percentage(int $awarded, int $expected): float
    {
        return $expected > 0 ? round(min(100, ($awarded / $expected) * 100), 1) : 0.0;
    }

    private function status(float $percentage, bool $needsAttention): string
    {
        if ($needsAttention) {
            return 'NEEDS ATTENTION';
        }
        if ($percentage === 0.0) {
            return 'NOT STARTED';
        }
        if ($percentage < 90.0) {
            return 'ONGOING';
        }
        if ($percentage < 100.0) {
            return 'NEAR COMPLETION';
        }

        return 'COMPLETED';
    }

    /** @return array<string, int> */
    private function emptyMedals(): array
    {
        return ['gold' => 0, 'silver' => 0, 'bronze' => 0, 'total' => 0];
    }

    /** @return array<string, int> */
    private function resultCounts(Meet $meet): array
    {
        $counts = EventResult::query()->where('meet_id', $meet->id)
            ->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        return collect(ResultStatus::cases())->mapWithKeys(
            fn (ResultStatus $status): array => [$status->value => (int) ($counts[$status->value] ?? 0)],
        )->all();
    }
}
