<?php

namespace Database\Seeders;

use App\Enums\GenderCategory;
use App\Enums\ResultStatus;
use App\Enums\UserRole;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\ResultPlacement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * DdOPAA 2025 Reference Dataset — WP3: Results & Medal Tally
 * Reconciliation.
 *
 * Local development and testing only. Requires WP1+WP2 to have already
 * run — returns early, idempotently, if the DdOPAA meet or its
 * delegations don't exist yet. Uses the app's own existing encode→
 * validate flow (`EventResult`/`ResultPlacement`) exactly as
 * `ResultController` does — no hardcoded medal tally, no parallel
 * "medal award" mechanism; `MedalTallyService::standings()` derives the
 * tally from these placements at read time, unchanged.
 *
 * Five events' rank-1 (and, for Volleyball Girls, ranks 2–3) placements
 * are PARTIALLY_VERIFIED per docs/data-reference/
 * ddopaa-2025-source-register.md rows 4–6; every other placement, and
 * every "how did the rest of the field finish" detail even within a
 * PARTIALLY_VERIFIED event, is SYNTHETIC_DERIVED or SYNTHETIC_DEMO — see
 * each method's own doc comment for exactly which.
 */
class DdopaaResultsSeeder extends Seeder
{
    /**
     * PARTIALLY_VERIFIED (source register rows 4, 6): event name/gender
     * → winning municipality, `sweep` = true means every entry from that
     * delegation shares rank 1 (Nabunturan's boxing "4 golds" doesn't map
     * onto this catalog's single non-weight-classed Boxing event — see
     * DESIGN-NOTES — approximated as a rank-1 sweep across their entries
     * rather than force-fit into a literal count).
     *
     * @var array<int, array{0: string, 1: GenderCategory, 2: string, 3: bool}>
     */
    private const array KNOWN_WINNERS = [
        ['3x3 Basketball', GenderCategory::Boys, 'Montevista', false],
        ['Artistic Gymnastics', GenderCategory::Boys, 'New Bataan', false],
        ['Boxing', GenderCategory::Boys, 'Nabunturan', true],
    ];

    /**
     * PARTIALLY_VERIFIED (source register row 4, 6): Nabunturan won the
     * title; Mawab beat Maragusan in a semifinal. SYNTHETIC_DERIVED: the
     * exact final placement (gold/silver/bronze order) synthesizing those
     * two fragments into one consistent bracket outcome — no source
     * states Maragusan's final placement explicitly, only that they lost
     * to Mawab before the final.
     */
    private const array VOLLEYBALL_GIRLS_BRACKET = ['Nabunturan', 'Mawab', 'Maragusan'];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $meet = Meet::query()->where('name', 'DdOPAA Meet 2025')->first();

        if ($meet === null || Delegation::query()->where('meet_id', $meet->id)->doesntExist()) {
            // WP1/WP2 haven't run yet.
            return;
        }

        $admin = User::query()->where('role', UserRole::Admin->value)->first();

        foreach ($meet->events()->with('sport')->get() as $event) {
            $entries = Entry::query()
                ->where('event_id', $event->id)
                ->where('status', 'confirmed')
                ->get();

            if ($entries->isEmpty()) {
                continue;
            }

            $placements = $event->is_team_event
                ? $this->teamPlacements($event, $entries)
                : $this->individualPlacements($event, $entries);

            if ($placements === []) {
                continue;
            }

            $this->validatedResult($meet, $event, $admin, $placements);
        }

        $this->guaranteeMunicipalityCoverage($meet);
    }

    /**
     * With 11 municipalities competing across a limited event catalog,
     * deterministic rotation can leave one or more with confirmed
     * entries but zero top-3 placements anywhere — invisible on the
     * medal tally page entirely (not just at zero), since
     * `MedalTallyService::standings()` only lists districts that appear
     * in at least one placement. Found by the project owner reviewing
     * the seeded data before a presentation (Maco, in practice).
     *
     * Every municipality with a confirmed entry is guaranteed at least
     * one placement here: swap a bronze (rank 3) slot in one
     * individual — never team, to avoid disturbing a tied team
     * roster — event they're entered in, taken from whichever
     * delegation currently holds that bronze, only when the donor
     * already has enough medals that losing one bronze can't drop them
     * to zero themselves. All `SYNTHETIC_DEMO` — the four
     * `PARTIALLY_VERIFIED` corroborated events (3x3 Basketball,
     * Volleyball, Artistic Gymnastics, Boxing Boys) are all team events
     * or already the corroborated winner, so this never touches them.
     * Deterministic and idempotent: once a delegation has any
     * placement, it's skipped on every later run.
     */
    private function guaranteeMunicipalityCoverage(Meet $meet): void
    {
        $delegations = Delegation::query()
            ->where('meet_id', $meet->id)
            ->orderBy('district_id')
            ->get();

        foreach ($delegations as $delegation) {
            if ($this->hasAnyPlacement($meet, $delegation)) {
                continue;
            }

            $this->coverDelegation($meet, $delegation);
        }
    }

    private function hasAnyPlacement(Meet $meet, Delegation $delegation): bool
    {
        return ResultPlacement::query()
            ->whereHas('result', fn ($q) => $q->where('meet_id', $meet->id))
            ->whereHas('entry', fn ($q) => $q->where('delegation_id', $delegation->id))
            ->exists();
    }

    private function coverDelegation(Meet $meet, Delegation $delegation): void
    {
        $entries = Entry::query()
            ->where('delegation_id', $delegation->id)
            ->where('status', 'confirmed')
            ->whereHas('event', fn ($q) => $q->where('is_team_event', false))
            ->with('event')
            ->orderBy('id')
            ->get();

        foreach ($entries as $entry) {
            $result = EventResult::query()
                ->where('meet_id', $meet->id)
                ->where('event_id', $entry->event_id)
                ->where('status', ResultStatus::Validated->value)
                ->first();

            if ($result === null) {
                continue;
            }

            $bronze = ResultPlacement::query()
                ->where('event_result_id', $result->id)
                ->where('rank', 3)
                ->first();

            if ($bronze === null || $bronze->entry_id === $entry->id) {
                continue;
            }

            $donorDelegationId = (int) Entry::query()->whereKey($bronze->entry_id)->value('delegation_id');

            // Only swap from a donor with medals to spare — never
            // create a new zero-medal municipality while fixing this
            // one. 2 is the minimum: losing 1 bronze still leaves 1.
            if ($this->delegationMedalCount($meet, $donorDelegationId) < 2) {
                continue;
            }

            $bronze->forceFill([
                'entry_id' => $entry->id,
                'mark' => $this->mark($entry->event, $entry),
            ])->save();

            return;
        }
    }

    private function delegationMedalCount(Meet $meet, int $delegationId): int
    {
        return ResultPlacement::query()
            ->whereIn('rank', [1, 2, 3])
            ->whereHas('result', fn ($q) => $q->where('meet_id', $meet->id)->where('status', ResultStatus::Validated->value))
            ->whereHas('entry', fn ($q) => $q->where('delegation_id', $delegationId))
            ->count();
    }

    /**
     * Groups entries by delegation (a team event's roster) and ranks
     * delegations, not individual entries — every teammate at a given
     * rank shares it with `is_tie = true`, since the medal award is
     * "this team placed Nth," not an individual finishing position.
     *
     * @param  Collection<int, Entry>  $entries
     * @return array<int, array{0: Entry, 1: int, 2: string|null, 3: bool}>
     */
    private function teamPlacements(Event $event, Collection $entries): array
    {
        $groups = $entries->groupBy('delegation_id');
        $order = $this->delegationOrder($event, $groups);

        $placements = [];
        $rank = 1;

        foreach (array_slice($order, 0, 3) as $delegationId) {
            $groupEntries = $groups->get($delegationId);

            if ($groupEntries === null) {
                continue;
            }

            $isTie = $groupEntries->count() > 1;

            foreach ($groupEntries as $entry) {
                $placements[] = [$entry, $rank, null, $isTie];
            }

            $rank++;
        }

        return $placements;
    }

    /**
     * @param  Collection<int, Collection<int, Entry>>  $groups
     * @return array<int, int>
     */
    private function delegationOrder(Event $event, Collection $groups): array
    {
        $ids = $groups->keys()->all();

        if ($event->name === 'Volleyball' && $event->gender === GenderCategory::Girls) {
            $bracketIds = collect(self::VOLLEYBALL_GIRLS_BRACKET)
                ->map(fn (string $municipality) => $this->delegationId($event, $municipality))
                ->filter(fn (?int $id) => $id !== null && in_array($id, $ids, true));

            $rest = $this->rotated(collect($ids)->diff($bracketIds), $event->id);

            return $bracketIds->concat($rest)->values()->all();
        }

        $winner = $this->knownWinner($event);

        if ($winner !== null) {
            $winnerId = $this->delegationId($event, $winner);

            if ($winnerId !== null && in_array($winnerId, $ids, true)) {
                $rest = $this->rotated(collect($ids)->reject(fn (int $id) => $id === $winnerId), $event->id);

                return collect([$winnerId])->concat($rest)->values()->all();
            }
        }

        return $this->rotated(collect($ids), $event->id)->all();
    }

    /**
     * Rotates a sorted list of IDs by a seed (the event's own ID) so
     * different events favor different delegations in the fallback,
     * generic-ranking path — without this, whichever delegation happens
     * to have the lowest ID (alphabetically first, i.e. always the same
     * one) would win every event with no corroborated result, an
     * obviously artificial-looking sweep rather than realistic variety.
     * Deterministic — never `random_int` — so idempotency still holds.
     *
     * @param  Collection<int, int>  $ids
     * @return Collection<int, int>
     */
    private function rotated(Collection $ids, int $seed): Collection
    {
        $sorted = $ids->sort()->values();

        if ($sorted->isEmpty()) {
            return $sorted;
        }

        $offset = $seed % $sorted->count();

        return $sorted->slice($offset)->concat($sorted->slice(0, $offset))->values();
    }

    /**
     * Same rotation as `rotated()`, applied to a Collection of Entry
     * models (individual events) rather than raw delegation IDs.
     *
     * @param  Collection<int, Entry>  $entries
     * @return Collection<int, Entry>
     */
    private function rotatedEntries(Collection $entries, int $seed): Collection
    {
        $byId = $entries->keyBy('id');
        $order = $this->rotated($entries->pluck('id'), $seed);

        return $order->map(fn (int $id) => $byId->get($id))->filter()->values();
    }

    /**
     * Individual events rank entries directly, one per placement — except
     * a `sweep` KNOWN_WINNERS entry (Boxing Boys), where every entry from
     * the winning delegation shares rank 1.
     *
     * @param  Collection<int, Entry>  $entries
     * @return array<int, array{0: Entry, 1: int, 2: string|null, 3: bool}>
     */
    private function individualPlacements(Event $event, Collection $entries): array
    {
        [, , $winner, $sweep] = $this->knownWinnerRow($event) ?? [null, null, null, false];
        $winnerId = $winner === null ? null : $this->delegationId($event, $winner);
        $winnerEntries = $winnerId === null ? collect() : $entries->where('delegation_id', $winnerId);

        if ($sweep && $winnerEntries->isNotEmpty()) {
            $placements = $winnerEntries
                ->sortBy('id')
                ->map(fn (Entry $entry) => [$entry, 1, $this->mark($event, $entry), $winnerEntries->count() > 1])
                ->values()
                ->all();

            $rest = $this->rotatedEntries(
                $entries->reject(fn (Entry $entry) => $entry->delegation_id === $winnerId),
                $event->id,
            );
            $rank = 2;

            foreach ($rest->take(3 - 1) as $entry) {
                $placements[] = [$entry, $rank++, $this->mark($event, $entry), false];
            }

            return $placements;
        }

        $ordered = $this->rotatedEntries($entries, $event->id);

        if ($winnerId !== null) {
            $winnerEntry = $ordered->firstWhere('delegation_id', $winnerId);

            if ($winnerEntry !== null) {
                $rest = $ordered->reject(fn (Entry $entry) => $entry->id === $winnerEntry->id);
                $ordered = collect([$winnerEntry])->concat($rest)->values();
            }
        }

        return $ordered->take(3)
            ->map(fn (Entry $entry, int $i) => [$entry, $i + 1, $this->mark($event, $entry), false])
            ->values()
            ->all();
    }

    private function knownWinner(Event $event): ?string
    {
        return $this->knownWinnerRow($event)[2] ?? null;
    }

    /**
     * @return array{0: string, 1: GenderCategory, 2: string, 3: bool}|null
     */
    private function knownWinnerRow(Event $event): ?array
    {
        foreach (self::KNOWN_WINNERS as $row) {
            if ($row[0] === $event->name && $row[1] === $event->gender) {
                return $row;
            }
        }

        return null;
    }

    private function delegationId(Event $event, string $municipality): ?int
    {
        $district = District::query()->where('name', $municipality)->first();

        if ($district === null) {
            return null;
        }

        return Delegation::query()
            ->where('district_id', $district->id)
            ->whereHas('meet.events', fn ($q) => $q->whereKey($event->id))
            ->value('id');
    }

    /**
     * SYNTHETIC_DEMO: no source has a real score/time/method for any
     * individual-event placement — plausible, deterministic (never
     * `random_int`) text so re-seeding produces identical marks.
     */
    private function mark(Event $event, Entry $entry): ?string
    {
        return match ($event->sport->name) {
            'Swimming' => sprintf('%d.%02ds', 26 + ($entry->id % 8), ($entry->id * 3) % 100),
            'Gymnastics' => sprintf('%.2f', 7.5 + (($entry->id % 20) / 10)),
            'Boxing' => $entry->id % 2 === 0 ? 'Decision' : 'TKO',
            default => null,
        };
    }

    /**
     * @param  array<int, array{0: Entry, 1: int, 2: string|null, 3: bool}>  $placements
     */
    private function validatedResult(Meet $meet, Event $event, ?User $admin, array $placements): void
    {
        $result = EventResult::query()
            ->where('meet_id', $meet->id)
            ->where('event_id', $event->id)
            ->first();

        if ($result !== null && $result->status === ResultStatus::Validated) {
            return;
        }

        if ($result === null) {
            $result = new EventResult(['meet_id' => $meet->id, 'event_id' => $event->id]);
            $result->forceFill([
                'encoded_by' => $admin?->id,
                'encoded_at' => now(),
            ])->save();
        }

        foreach ($placements as [$entry, $rank, $mark, $isTie]) {
            ResultPlacement::query()->firstOrCreate(
                ['event_result_id' => $result->id, 'entry_id' => $entry->id],
                ['rank' => $rank, 'mark' => $mark, 'is_tie' => $isTie],
            );
        }

        $result->forceFill([
            'status' => ResultStatus::Validated,
            'validated_by' => $admin?->id,
            'validated_at' => now(),
        ])->save();
    }
}
