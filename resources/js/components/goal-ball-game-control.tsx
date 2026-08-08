import { router } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Settings2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { WhistleIcon } from '@/components/icons/whistle-icon';
import { CorrectionDialog } from '@/components/live-score-display';
import type { GoalBallState, LiveSession } from '@/components/live-score-display';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import {
    pause as pauseRoute,
    penaltyThrow as penaltyThrowRoute,
    period as periodRoute,
    resume as resumeRoute,
    score as scoreRoute,
    settings as settingsRoute,
} from '@/routes/scoring';

type Side = 'a' | 'b';

function SettingsDialog({
    state,
    disabled,
    onSave,
}: {
    state: GoalBallState;
    /** Disabled while play is running — same "settings only change during
     * a stoppage" rule as every other sport's dedicated control. */
    disabled: boolean;
    onSave: (data: { minutes_per_half: number }) => void;
}) {
    const [open, setOpen] = useState(false);
    const [minutesPerHalf, setMinutesPerHalf] = useState(
        String(state.minutes_per_half),
    );

    const submit = (e: FormEvent) => {
        e.preventDefault();
        onSave({ minutes_per_half: Number(minutesPerHalf) });
        setOpen(false);
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    variant="outline"
                    className="h-12 text-base"
                    disabled={disabled}
                    title={
                        disabled
                            ? 'Pause the game to change settings'
                            : undefined
                    }
                >
                    <Settings2 aria-hidden="true" />
                    Settings
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Game settings</DialogTitle>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="minutes-per-half">
                                Minutes per half
                            </Label>
                            <Input
                                id="minutes-per-half"
                                type="number"
                                min={3}
                                max={20}
                                value={minutesPerHalf}
                                onChange={(e) =>
                                    setMinutesPerHalf(e.target.value)
                                }
                                required
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="submit">Save settings</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** Defaults for every key this WP added to sport_state — a session
 * started before this shipped would read every field as `undefined`
 * without this (same fallback convention as every other sport's own
 * state defaults). */
const GOAL_BALL_STATE_DEFAULTS: GoalBallState = {
    penalty_throws_a: 0,
    penalty_throws_b: 0,
    minutes_per_half: 6,
};

export function GoalBallGameControl({
    session,
    state: rawState,
}: {
    session: LiveSession;
    state: GoalBallState;
}) {
    const state: GoalBallState = { ...GOAL_BALL_STATE_DEFAULTS, ...rawState };
    const running = session.status === 'in_progress';
    const isPaused = session.status === 'paused';

    // Goal ball has no structured "current half" column — same free-text
    // `period_label` convention football/futsal's half stepper already
    // reuses (e.g. "2nd Half"), not a new field.
    const currentHalf = session.period_label === '2nd Half' ? 2 : 1;

    const changeHalf = (next: 1 | 2) => {
        router.patch(
            periodRoute(session.id).url,
            {
                period_label: next === 1 ? '1st Half' : '2nd Half',
                status_note: session.status_note,
            },
            { preserveScroll: true },
        );
    };

    const pauseResume = () => {
        router.patch(
            (isPaused ? resumeRoute : pauseRoute)(session.id).url,
            {},
            { preserveScroll: true },
        );
    };

    const scoreGoal = (side: Side) => {
        router.patch(
            scoreRoute(session.id).url,
            { type: 'point', side, delta: 1 },
            { preserveScroll: true },
        );
    };

    const correctScore = (side: Side, delta: number, reason: string) => {
        router.patch(
            scoreRoute(session.id).url,
            { type: 'correction', side, delta, reason },
            { preserveScroll: true },
        );
    };

    const issuePenaltyThrow = (side: Side) => {
        router.patch(
            penaltyThrowRoute(session.id).url,
            { action: 'add', side },
            { preserveScroll: true },
        );
    };

    const resetPenaltyThrows = () => {
        router.patch(
            penaltyThrowRoute(session.id).url,
            { action: 'reset' },
            { preserveScroll: true },
        );
    };

    const saveSettings = (data: { minutes_per_half: number }) => {
        router.patch(settingsRoute(session.id).url, data, {
            preserveScroll: true,
        });
    };

    return (
        <div className="flex w-full flex-col gap-4 print:hidden">
            <div className="flex flex-col gap-2 rounded-xl border bg-muted/20 p-2">
                {/* Row 1: Settings, half stepper, Whistle. */}
                <div className="flex flex-wrap items-center justify-center gap-2">
                    <SettingsDialog
                        state={state}
                        disabled={running}
                        onSave={saveSettings}
                    />

                    <div className="flex h-12 items-center gap-1 rounded-md border bg-background px-2">
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-7"
                            disabled={currentHalf <= 1}
                            aria-label="Previous half"
                            onClick={() => changeHalf(1)}
                        >
                            <ArrowLeft aria-hidden="true" className="size-4" />
                        </Button>
                        <span className="text-base font-semibold tabular-nums">
                            {currentHalf === 1 ? '1st' : '2nd'} Half (
                            {state.minutes_per_half} min)
                        </span>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-7"
                            disabled={currentHalf >= 2}
                            aria-label="Next half"
                            onClick={() => changeHalf(2)}
                        >
                            <ArrowRight aria-hidden="true" className="size-4" />
                        </Button>
                    </div>

                    <Button
                        className={cn(
                            'h-12 text-base font-semibold text-white',
                            isPaused
                                ? 'border-orange-700 bg-orange-600 hover:bg-orange-700'
                                : 'border-emerald-700 bg-emerald-600 hover:bg-emerald-700',
                        )}
                        onClick={pauseResume}
                        aria-label={isPaused ? 'Resume clock' : 'Pause clock'}
                    >
                        <WhistleIcon aria-hidden="true" className="size-5" />
                        {isPaused ? 'Resume' : 'Pause'}
                    </Button>
                </div>

                {/* Row 2: reset the penalty-throw tallies (a rare
                    correction — not a real-world half-time reset, penalty
                    throws carry the whole match like football/futsal's
                    cards do). */}
                <div className="flex flex-wrap items-center justify-center gap-2">
                    <Button
                        variant="outline"
                        className="h-10 text-base"
                        onClick={resetPenaltyThrows}
                    >
                        Reset penalty throws
                    </Button>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="flex flex-col items-center gap-2 rounded-xl border-2 p-3">
                    <span className="text-sm font-medium text-muted-foreground">
                        {session.side_a_label} — {state.penalty_throws_a}{' '}
                        penalty throw{state.penalty_throws_a === 1 ? '' : 's'}
                    </span>
                    <Button
                        className="h-14 min-w-24 border-emerald-700 bg-emerald-600 text-lg font-bold text-white hover:bg-emerald-700"
                        aria-label={`Add a goal, ${session.side_a_label}`}
                        onClick={() => scoreGoal('a')}
                    >
                        Goal
                    </Button>
                    <Button
                        variant="outline"
                        className="h-10 text-sm font-semibold"
                        aria-label={`Penalty throw, ${session.side_a_label}`}
                        onClick={() => issuePenaltyThrow('a')}
                    >
                        Penalty throw
                    </Button>
                    <CorrectionDialog
                        side="a"
                        label={session.side_a_label}
                        onSubmit={(delta, reason) =>
                            correctScore('a', delta, reason)
                        }
                    />
                </div>
                <div className="flex flex-col items-center gap-2 rounded-xl border-2 p-3">
                    <span className="text-sm font-medium text-muted-foreground">
                        {session.side_b_label} — {state.penalty_throws_b}{' '}
                        penalty throw{state.penalty_throws_b === 1 ? '' : 's'}
                    </span>
                    <Button
                        className="h-14 min-w-24 border-emerald-700 bg-emerald-600 text-lg font-bold text-white hover:bg-emerald-700"
                        aria-label={`Add a goal, ${session.side_b_label}`}
                        onClick={() => scoreGoal('b')}
                    >
                        Goal
                    </Button>
                    <Button
                        variant="outline"
                        className="h-10 text-sm font-semibold"
                        aria-label={`Penalty throw, ${session.side_b_label}`}
                        onClick={() => issuePenaltyThrow('b')}
                    >
                        Penalty throw
                    </Button>
                    <CorrectionDialog
                        side="b"
                        label={session.side_b_label}
                        onSubmit={(delta, reason) =>
                            correctScore('b', delta, reason)
                        }
                    />
                </div>
            </div>
        </div>
    );
}
