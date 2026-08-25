<?php

namespace App\Services;

use App\Enums\GenderCategory;
use App\Enums\Sex;
use App\Models\Athlete;
use App\Models\MeetSport;
use App\Models\SportRosterLimit;
use App\Models\SportRosterMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SportRosterService
{
    public function add(MeetSport $meetSport, Athlete $athlete): SportRosterMember
    {
        $athlete->loadMissing('delegation');
        $level = $athlete->ageDivision();
        $gender = $athlete->sex === Sex::Male ? GenderCategory::Boys : GenderCategory::Girls;

        if ($athlete->delegation->meet_id !== $meetSport->meet_id) {
            throw ValidationException::withMessages(['athlete_id' => __('The athlete belongs to another meet.')]);
        }

        return DB::transaction(function () use ($meetSport, $athlete, $level, $gender): SportRosterMember {
            $limit = SportRosterLimit::query()->where('meet_sport_id', $meetSport->id)
                ->where('level', $level->value)->where('gender', $gender->value)
                ->lockForUpdate()->first();
            if ($limit === null) {
                throw ValidationException::withMessages(['athlete_id' => __('No roster limit is configured for this sport category.')]);
            }
            if (SportRosterMember::query()->where('meet_sport_id', $meetSport->id)->where('athlete_id', $athlete->id)->exists()) {
                throw ValidationException::withMessages(['athlete_id' => __('This athlete is already on the sport roster.')]);
            }
            $count = SportRosterMember::query()->where('meet_sport_id', $meetSport->id)
                ->where('delegation_id', $athlete->delegation_id)->where('level', $level->value)
                ->where('gender', $gender->value)->count();
            if ($count >= $limit->max_athletes) {
                throw ValidationException::withMessages(['athlete_id' => __('This roster is full (:count swimmers). Remove or replace a swimmer before adding another.', ['count' => $limit->max_athletes])]);
            }

            return SportRosterMember::query()->create([
                'meet_sport_id' => $meetSport->id, 'delegation_id' => $athlete->delegation_id,
                'athlete_id' => $athlete->id, 'level' => $level, 'gender' => $gender,
            ]);
        });
    }
}
