<?php

namespace App\Http\Requests;

use App\Enums\DavraaReportDesignation;
use App\Enums\DavraaReportLevel;
use App\Enums\DavraaReportStatus;
use App\Enums\GenderCategory;
use App\Models\DavraaReportGroup;
use App\Models\Event;
use App\Models\Meet;
use App\Services\DavraaReportAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DavraaReportGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $meetId = Meet::current()->id;
        $access = app(DavraaReportAccess::class);
        $group = $this->route('davraaReportGroup');

        if ($group instanceof DavraaReportGroup
            && ! $access->canManage($this->user(), $meetId, $group->sport_id)) {
            return false;
        }

        return $access->canManage($this->user(), $meetId, $this->integer('sport_id'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'sport_id' => ['required', 'integer', Rule::exists('sports', 'id')],
            'division' => ['required', Rule::enum(GenderCategory::class)],
            'level' => ['required', Rule::enum(DavraaReportLevel::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in([DavraaReportStatus::Draft->value, DavraaReportStatus::Final->value])],

            'event_ids' => ['array'],
            'event_ids.*' => ['integer', 'distinct', Rule::exists('events', 'id')],

            'members' => ['array', 'max:200'],
            'members.*.designation' => ['required', Rule::enum(DavraaReportDesignation::class)],
            'members.*.athlete_id' => ['nullable', 'integer', Rule::exists('athletes', 'id')],
            'members.*.coach_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'members.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            // Manual fields — only used when a CHAPERONE has no account.
            'members.*.last_name' => ['nullable', 'string', 'max:120'],
            'members.*.given_names' => ['nullable', 'string', 'max:160'],
            'members.*.middle_initial' => ['nullable', 'string', 'max:8'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $meetId = Meet::current()->id;

            // Selected events must belong to the chosen sport and the meet.
            $eventIds = collect($this->input('event_ids', []))->filter()->map(fn ($id): int => (int) $id);
            if ($eventIds->isNotEmpty()) {
                $valid = Event::query()
                    ->whereKey($eventIds)
                    ->where('sport_id', $this->integer('sport_id'))
                    ->whereHas('meets', fn ($meets) => $meets->whereKey($meetId))
                    ->pluck('id');
                if ($valid->count() !== $eventIds->unique()->count()) {
                    $validator->errors()->add('event_ids', 'Every selected Sports Event must belong to the chosen Sport and the current Meet.');
                }
            }

            foreach ($this->input('members', []) as $index => $member) {
                $designation = DavraaReportDesignation::tryFrom($member['designation'] ?? '');

                if ($designation === DavraaReportDesignation::Athlete && empty($member['athlete_id'])) {
                    $validator->errors()->add("members.$index.athlete_id", 'An ATHLETE row needs a selected Athlete.');
                }

                if (in_array($designation, [DavraaReportDesignation::Coach, DavraaReportDesignation::AssistantCoach], true)
                    && empty($member['coach_user_id'])) {
                    $validator->errors()->add("members.$index.coach_user_id", 'A COACH / ASST. COACH row needs a selected Coach.');
                }

                if ($designation === DavraaReportDesignation::Chaperone
                    && empty($member['coach_user_id'])
                    && blank($member['last_name'] ?? null)) {
                    $validator->errors()->add("members.$index.last_name", 'A CHAPERONE row needs either a selected person or a typed name.');
                }
            }
        });
    }

    /**
     * @return array{name: string, sport_id: int, division: string, level: string, notes: ?string, status: string, event_ids: array<int, int>, members: array<int, array<string, mixed>>}
     */
    public function payload(): array
    {
        return [
            'name' => trim($this->string('name')),
            'sport_id' => $this->integer('sport_id'),
            'division' => $this->string('division')->toString(),
            'level' => $this->string('level')->toString(),
            'notes' => $this->filled('notes') ? trim($this->string('notes')) : null,
            'status' => $this->input('status', DavraaReportStatus::Draft->value),
            'event_ids' => collect($this->input('event_ids', []))->filter()->map(fn ($id): int => (int) $id)->unique()->values()->all(),
            'members' => collect($this->input('members', []))->map(fn (array $member, int $index): array => [
                'designation' => $member['designation'],
                'athlete_id' => ! empty($member['athlete_id']) ? (int) $member['athlete_id'] : null,
                'coach_user_id' => ! empty($member['coach_user_id']) ? (int) $member['coach_user_id'] : null,
                'sort_order' => isset($member['sort_order']) ? (int) $member['sort_order'] : $index,
                'last_name' => $member['last_name'] ?? null,
                'given_names' => $member['given_names'] ?? null,
                'middle_initial' => $member['middle_initial'] ?? null,
            ])->all(),
        ];
    }
}
