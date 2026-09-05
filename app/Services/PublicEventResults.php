<?php

namespace App\Services;

use App\Enums\ResultStatus;
use App\Models\EventResult;
use App\Models\ResultAttachment;
use App\Models\ResultPlacement;
use Illuminate\Database\Eloquent\Builder;

class PublicEventResults
{
    public function visible(Builder $query): Builder
    {
        return $query->real()->where('status', ResultStatus::Official->value);
    }

    public function withMedals(Builder $query): Builder
    {
        return $this->visible($query)->whereHas('medalAwards', fn (Builder $awards) => $awards->where('tally_quantity', '>', 0));
    }

    public function publishedResults(Builder $query): Builder
    {
        return $this->visible($query)->where(fn ($q) => $q->where('result_type', 'versus')
            ->orWhereHas('medalAwards', fn ($awards) => $awards->where('tally_quantity', '>', 0)));
    }

    public function row(EventResult $result): array
    {
        $result->loadMissing(['event.sport', 'placements.entry.athlete.school', 'placements.entry.delegation.school',
            'placements.entry.delegation.district', 'placements.teamEntry.delegation.school',
            'placements.teamEntry.delegation.district', 'placements.delegation.school', 'placements.delegation.district',
            'placements.medalAward', 'placements.athlete', 'attachments.file']);

        return [
            ...($result->result_type === 'versus' ? ['result_type' => 'versus', 'measurement_type' => $result->measurement_type] : []),
            'id' => $result->id,
            'event' => sprintf('%s — %s (%s, %s)', $result->event->sport->name, $result->event->name,
                $result->event->gender->label(), $result->event->age_division->label()),
            'age_division' => $result->event->age_division->value,
            'status' => $result->status->value,
            'status_label' => $result->status === ResultStatus::Official ? 'Accepted' : $result->status->label(),
            'official_as_of' => ($result->official_at ?? $result->submitted_at)?->format('M j, Y g:i A'),
            'placements' => $result->placements->sortBy('rank')->map(function (ResultPlacement $placement): array {
                $delegation = $placement->delegation ?? $placement->teamEntry?->delegation ?? $placement->entry?->delegation;

                return [
                    'id' => $placement->id, 'rank' => $placement->rank,
                    'delegation_id' => $delegation?->id,
                    'athlete' => $placement->athlete?->fullName() ?? $placement->entry?->athlete?->fullName() ?? $delegation?->registrantName() ?? 'Participant pending',
                    'school' => $placement->entry?->athlete?->school?->name ?? $delegation?->school?->name ?? '',
                    'delegation' => $delegation?->registrantName() ?? 'Delegation pending',
                    'mark' => $placement->mark, 'is_tie' => $placement->is_tie,
                    'medal' => ($placement->medalAward?->tally_quantity ?? 0) > 0 ? $placement->medalAward->medal_type : null,
                ];
            })->values()->all(),
            'documents' => $result->attachments->filter(fn (ResultAttachment $attachment) => $attachment->is_current
                && ($attachment->result_version === $result->version || $attachment->attachment_type === ResultAttachment::DIRECT_RESULT_EVIDENCE)
                && $attachment->file !== null)->map(fn (ResultAttachment $attachment): array => [
                    'id' => $attachment->id, 'name' => $attachment->file->original_name,
                    'mime_type' => $attachment->file->mime_type,
                    'url' => route('public.result-document', [$result, $attachment]),
                ])->values()->all(),
        ];
    }
}
