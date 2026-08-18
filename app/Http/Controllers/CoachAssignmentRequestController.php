<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\CoachAssignmentRequest;
use App\Models\Delegation;
use App\Models\MeetSport;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CoachAssignmentRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === UserRole::Coach, 403);
        $data = $request->validate([
            'meet_sport_id' => ['required', Rule::exists('meet_sports', 'id')],
            'delegation_id' => ['required', Rule::exists('delegations', 'id')],
            'school_id' => ['required', Rule::exists('schools', 'id')->where('active', true)],
        ]);
        $delegation = Delegation::query()->findOrFail($data['delegation_id']);
        $school = School::query()->findOrFail($data['school_id']);
        $meetSport = MeetSport::query()->findOrFail($data['meet_sport_id']);

        abort_unless($delegation->hasCoach($request->user()), 403);
        abort_unless($meetSport->meet_id === $delegation->meet_id, 422);
        abort_unless(($delegation->district_id !== null && $school->district_id === $delegation->district_id)
            || ($delegation->school_id !== null && $school->id === $delegation->school_id), 403);

        CoachAssignmentRequest::query()->firstOrCreate(['user_id' => $request->user()->id, ...$data]);

        return back()->with('success', __('Sport assignment request submitted.'));
    }
}
