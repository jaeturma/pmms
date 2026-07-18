<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FileUpload;
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
        return Inertia::render('dashboard', [
            'stats' => [
                [
                    'key' => 'users',
                    'label' => 'Users',
                    'value' => User::query()->count(),
                ],
                [
                    'key' => 'uploads',
                    'label' => 'Uploaded Files',
                    'value' => FileUpload::query()->count(),
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
