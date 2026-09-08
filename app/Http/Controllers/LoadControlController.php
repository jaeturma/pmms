<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Production Load Controls — System Administrator emergency levers for a
 * live meet, all gated by `can:administer` at the route. Nothing here
 * deletes accounts or scoring data:
 *
 * - suspend/resume flips one `system_settings` flag (the public-scoreboard
 *   gate reads a 60s cached projection of it);
 * - the disconnect actions delete rows from the `sessions` table only —
 *   never cache, queues, locks or any other store — forcing the affected
 *   users to sign in again.
 *
 * The acting administrator's own session is always preserved.
 */
class LoadControlController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function suspendScoreboards(Request $request): RedirectResponse
    {
        return $this->setSuspended($request, true);
    }

    public function resumeScoreboards(Request $request): RedirectResponse
    {
        return $this->setSuspended($request, false);
    }

    private function setSuspended(Request $request, bool $suspended): RedirectResponse
    {
        $settings = Setting::current();

        if ($settings->live_scoreboards_suspended !== $suspended) {
            $settings->forceFill(['live_scoreboards_suspended' => $suspended])->save();
            Setting::forgetLoadControlsCache();

            $this->audit->record(
                $suspended ? 'load_control.scoreboards_suspended' : 'load_control.scoreboards_resumed',
                $settings,
                ['administrator_id' => $request->user()->id],
            );
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $suspended
                ? __('Public live scoreboards suspended.')
                : __('Public live scoreboards resumed.'),
        ]);

        return back();
    }

    /**
     * Disconnect authenticated web sessions that have been idle longer
     * than the configured inactivity window — the lightest-touch recovery
     * action.
     */
    public function disconnectInactive(Request $request): RedirectResponse
    {
        $this->assertDatabaseSessions();

        $cutoff = now()->subMinutes(Setting::current()->inactivityTimeoutMinutes())->getTimestamp();

        $deleted = DB::table(config('session.table', 'sessions'))
            ->whereNotNull('user_id')
            ->where('id', '!=', $request->session()->getId())
            // Never sign the acting administrator out (any of their
            // devices), even an idle one — this is a recovery tool they
            // are actively using.
            ->where('user_id', '!=', $request->user()->id)
            ->where('last_activity', '<', $cutoff)
            ->delete();

        return $this->finishDisconnect($request, 'inactive', $deleted);
    }

    /**
     * Disconnect every authenticated web session except System
     * Administrators' — the harder lever for a genuine load emergency.
     */
    public function disconnectNonAdmin(Request $request): RedirectResponse
    {
        $this->assertDatabaseSessions();

        $adminIds = User::query()
            ->get(['id', 'role', 'additional_roles'])
            ->filter(fn (User $user): bool => $user->isAdmin())
            ->pluck('id')
            ->all();

        $deleted = DB::table(config('session.table', 'sessions'))
            ->whereNotNull('user_id')
            ->where('id', '!=', $request->session()->getId())
            ->whereNotIn('user_id', $adminIds)
            ->delete();

        return $this->finishDisconnect($request, 'non_admin', $deleted);
    }

    private function finishDisconnect(Request $request, string $mode, int $deleted): RedirectResponse
    {
        $this->audit->record('load_control.sessions_disconnected', null, [
            'administrator_id' => $request->user()->id,
            'mode' => $mode,
            'sessions_invalidated' => $deleted,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(
                '{0}No matching sessions were connected.|{1}Disconnected 1 session.|[2,*]Disconnected :count sessions.',
                $deleted,
                ['count' => $deleted],
            ),
        ]);

        return back();
    }

    /**
     * The disconnect actions operate on the `sessions` table directly;
     * they are meaningless (and would silently no-op) on the `array` /
     * `cookie` / `file` drivers. Production runs `database`.
     */
    private function assertDatabaseSessions(): void
    {
        abort_unless(
            config('session.driver') === 'database',
            409,
            __('Session disconnect is only available with the database session driver.'),
        );
    }
}
