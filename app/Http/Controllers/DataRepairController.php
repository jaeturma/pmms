<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Services\AuditLogger;
use App\Services\RegistrationDataConsistencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DataRepairController extends Controller
{
    public function __construct(private readonly RegistrationDataConsistencyService $consistency, private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);
        $issues = $this->consistency->issues();
        return Inertia::render('data-repair/index', [
            'issues' => $issues,
            'summary' => ['total' => $issues->count(), 'athletes' => $issues->where('type', 'athlete')->count(), 'coaches' => $issues->whereIn('type', ['coach', 'coach_account'])->count(), 'games' => $issues->where('type', 'game')->count(), 'automatic' => $issues->whereNotNull('repair')->count()],
        ]);
    }

    public function repair(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request);
        $data = $request->validate(['type' => ['required', 'in:athlete,coach'], 'id' => ['required', 'integer'], 'code' => ['required', 'string', 'max:80']]);
        $this->consistency->repair($data['type'], $data['id'], $data['code']);
        $this->audit->record('registration_data.repaired', $request->user(), $data);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('The record was repaired and checked again.')]);
        return back();
    }

    private function authorizeAccess(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && ($user->isAdmin() || $user->hasRole(UserRole::TournamentICT) || $user->canManageProductionAccounts()), 403);
    }
}
