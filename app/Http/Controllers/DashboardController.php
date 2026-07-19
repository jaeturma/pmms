<?php

namespace App\Http\Controllers;

use App\Enums\MeetStatus;
use App\Enums\ProtestStatus;
use App\Enums\ResultStatus;
use App\Enums\UserRole;
use App\Models\Accreditation;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\Incident;
use App\Models\Meet;
use App\Models\Personnel;
use App\Models\Protest;
use App\Models\School;
use App\Models\User;
use App\Services\MedalTallyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the dashboard with its widget data.
     */
    public function index(Request $request, MedalTallyService $tally): Response
    {
        $currentMeet = Meet::query()
            ->where('status', '!=', MeetStatus::Completed->value)
            ->withCount('events')
            ->orderByDesc('starts_at')
            ->first();

        return Inertia::render('dashboard', [
            'currentMeet' => $currentMeet === null ? null : [
                'name' => $currentMeet->name,
                'school_year' => $currentMeet->school_year,
                'status' => $currentMeet->status->value,
                'status_label' => $currentMeet->status->label(),
                'starts_at' => $currentMeet->starts_at->toDateString(),
                'ends_at' => $currentMeet->ends_at->toDateString(),
                'venue' => $currentMeet->venue,
                'events_count' => $currentMeet->events_count,
            ],
            'operations' => $this->operations($request, $tally),
            'stats' => [
                [
                    'key' => 'schools',
                    'label' => 'Schools',
                    'value' => School::query()->count(),
                ],
                [
                    'key' => 'delegations',
                    'label' => 'Delegations',
                    'value' => Delegation::query()->count(),
                ],
                [
                    'key' => 'athletes',
                    'label' => 'Athletes',
                    'value' => Athlete::query()->count(),
                ],
                [
                    'key' => 'entries',
                    'label' => 'Entries',
                    'value' => Entry::query()->count(),
                ],
                [
                    'key' => 'users',
                    'label' => 'Users',
                    'value' => User::query()->count(),
                ],
                [
                    'key' => 'activity_today',
                    'label' => "Today's Activity",
                    'value' => AuditLog::query()->whereDate('created_at', today())->count(),
                ],
            ],
            'recentActivity' => AuditLog::query()
                ->with('user')
                ->latest('created_at')
                ->limit(10)
                ->get()
                ->map(fn (AuditLog $log): array => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'user' => $log->user?->name,
                    'created_at_human' => $log->created_at?->diffForHumans(),
                ])
                ->values(),
        ]);
    }

    /**
     * Meet-day operations widgets for the active meet (read-side only).
     * Managers get the operational queues, officers their own delegation's
     * protests, viewers the schedule and tally summaries.
     *
     * @return array<string, mixed>|null
     */
    private function operations(Request $request, MedalTallyService $tally): ?array
    {
        $meet = Meet::query()
            ->where('status', MeetStatus::Active->value)
            ->orderByDesc('starts_at')
            ->first();

        if ($meet === null) {
            return null;
        }

        /** @var User $user */
        $user = $request->user();

        $canManage = Gate::allows('manage-meet-data');

        return [
            'meet' => ['id' => $meet->id, 'name' => $meet->name],
            'todaySlots' => EventSchedule::query()
                ->where('meet_id', $meet->id)
                ->whereDate('scheduled_date', today())
                ->with(['venue:id,name', 'event.sport:id,name'])
                ->orderBy('starts_at')
                ->get()
                ->map(fn (EventSchedule $slot): array => [
                    'id' => $slot->id,
                    'starts_at' => substr($slot->starts_at, 0, 5),
                    'ends_at' => substr($slot->ends_at, 0, 5),
                    'event' => sprintf(
                        '%s — %s (%s, %s)',
                        $slot->event->sport->name,
                        $slot->event->name,
                        $slot->event->gender->label(),
                        $slot->event->age_division->label(),
                    ),
                    'venue' => $slot->venue->name,
                ])
                ->values()
                ->all(),
            'tallyTop' => array_slice($tally->standings($meet->id)['schools'], 0, 5),
            'queues' => $canManage ? [
                'pending_results' => EventResult::query()
                    ->where('meet_id', $meet->id)
                    ->where('status', ResultStatus::Encoded->value)
                    ->count(),
                'open_protests' => Protest::query()
                    ->whereIn('status', [ProtestStatus::Filed->value, ProtestStatus::UnderReview->value])
                    ->whereHas('delegation', fn ($delegation) => $delegation->where('meet_id', $meet->id))
                    ->count(),
                'open_incidents' => Incident::query()
                    ->where('meet_id', $meet->id)
                    ->where('status', 'open')
                    ->count(),
                'accredited' => Accreditation::query()
                    ->whereHas('delegation', fn ($delegation) => $delegation->where('meet_id', $meet->id))
                    ->count(),
                'accreditable' => Athlete::query()
                    ->whereHas('delegation', fn ($delegation) => $delegation->where('meet_id', $meet->id))
                    ->count()
                    + Personnel::query()
                        ->whereHas('delegation', fn ($delegation) => $delegation->where('meet_id', $meet->id))
                        ->count(),
            ] : null,
            'myProtests' => $user->role === UserRole::DelegationOfficer
                ? Protest::query()
                    ->whereHas('delegation', fn ($delegation) => $delegation
                        ->where('meet_id', $meet->id)
                        ->whereHas('officers', fn ($officers) => $officers->whereKey($user->getKey())))
                    ->latest('id')
                    ->limit(5)
                    ->get()
                    ->map(fn (Protest $protest): array => [
                        'id' => $protest->id,
                        'grounds' => $protest->grounds,
                        'status_label' => $protest->status->label(),
                    ])
                    ->values()
                    ->all()
                : null,
        ];
    }
}
