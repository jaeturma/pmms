<?php

namespace App\Http\Controllers;

use App\Enums\MeetStatus;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Meet;
use App\Models\School;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the dashboard with its widget data.
     */
    public function index(): Response
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
}
