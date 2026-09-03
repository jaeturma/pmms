<?php

namespace App\Http\Requests;

use App\Enums\ScoreboardType;
use App\Models\Event;
use App\Models\Meet;
use App\Services\CompetitionAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $access = app(CompetitionAccessService::class);

        return $user !== null && ($user->isAdmin()
            || $access->hasAssignmentRole($user, $access->competitionManagerRoles(), Meet::current()->id));
    }

    /**
     * A match always belongs to `Meet::current()` — this deployment runs
     * one meet, so nobody picks it.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['meet_id' => Meet::current()->id]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'meet_id' => ['required', 'integer', Rule::exists('meets', 'id')],
            'event_id' => ['required', 'integer', Rule::exists('events', 'id')],
            'event_schedule_id' => ['nullable', 'integer', Rule::exists('event_schedules', 'id')],
            'round_label' => ['required', 'string', 'max:60'],
            'sequence' => ['required', 'integer', 'min:1', 'max:999'],
            'competition_area' => ['nullable', 'string', 'max:100'],
            'live_scoring_enabled' => ['sometimes', 'boolean'],
            'awards_medals' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('live_scoring_enabled')) {
                return;
            }

            $sportName = Event::query()->with('sport:id,name')
                ->find($this->integer('event_id'))?->sport?->name;
            if (! ScoreboardType::supportsLiveSport($sportName)) {
                $validator->errors()->add(
                    'live_scoring_enabled',
                    __('Live scoring is available only for Basketball, Softball, Baseball, and Boxing.'),
                );
            }
        });
    }
}
