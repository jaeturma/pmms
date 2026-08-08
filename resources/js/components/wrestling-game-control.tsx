import { router } from '@inertiajs/react';
import { Bell, Play, Settings2, TimerReset } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { WhistleIcon } from '@/components/icons/whistle-icon';
import {
    CorrectionDialog,
    CountdownClock,
} from '@/components/live-score-display';
import type {
    LiveSession,
    WrestlingState,
} from '@/components/live-score-display';
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
    fall as fallRoute,
    horn as hornRoute,
    pause as pauseRoute,
    periodClock as periodClockRoute,
    resume as resumeRoute,
    score as scoreRoute,
    settings as settingsRoute,
    wrestlingPoint as wrestlingPointRoute,
} from '@/routes/scoring';

type Side = 'a' | 'b';
type Move = 'takedown' | 'escape' | 'reversal' | 'near_fall' | 'penalty';

/** Common default point values — real, but not the only possible values
 * across every wrestling style/rule set (freestyle/folkstyle/greco vary).
 * Tapping a button submits this default; a wrong value is corrected the
 * same way as any other board type, through the generic score()
 * correction endpoint. */
const MOVES: { move: Move; label: string; points: number }[] = [
    { move: 'takedown', label: 'Takedown', points: 2 },
    { move: 'escape', label: 'Escape', points: 1 },
    { move: 'reversal', label: 'Reversal', points: 2 },
    { move: 'near_fall', label: 'Near fall', points: 2 },
    { move: 'penalty', label: 'Penalty', points: 1 },
];

function SettingsDialog({
    state,
    disabled,
    onSave,
}: {
    state: WrestlingState;
    /** Disabled while play is running — same "settings only change during
     * a stoppage" rule as every other sport's dedicated control. */
    disabled: boolean;
    onSave: (data: {
        period_duration_seconds: number;
        rest_duration_seconds: number;
        total_periods: number;
    }) => void;
}) {
    const [open, setOpen] = useState(false);
    const [periodSeconds, setPeriodSeconds] = useState(
        String(state.period_duration_seconds),
    );
    const [restSeconds, setRestSeconds] = useState(
        String(state.rest_duration_seconds),
    );
    const [totalPeriods, setTotalPeriods] = useState(
        String(state.total_periods),
    );

    const submit = (e: FormEvent) => {
        e.preventDefault();
        onSave({
            period_duration_seconds: Number(periodSeconds),
            rest_duration_seconds: Number(restSeconds),
            total_periods: Number(totalPeriods),
        });
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
                            ? 'Pause the match to change settings'
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
                        <DialogTitle>Match settings</DialogTitle>
                    </DialogHeader>
                    <div className="grid gap-4 py-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="period-duration">
                                Period duration (seconds)
                            </Label>
                            <Input
                                id="period-duration"
                                type="number"
                                min={30}
                                max={600}
                                value={periodSeconds}
                                onChange={(e) =>
                                    setPeriodSeconds(e.target.value)
                                }
                                required
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="rest-duration">
                                Rest duration (seconds)
                            </Label>
                            <Input
                                id="rest-duration"
                                type="number"
                                min={10}
                                max={300}
                                value={restSeconds}
                                onChange={(e) => setRestSeconds(e.target.value)}
                                required
                            />
                        </div>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="total-periods">Total periods</Label>
                            <Input
                                id="total-periods"
                                type="number"
                                min={1}
                                max={5}
                                value={totalPeriods}
                                onChange={(e) =>
                                    setTotalPeriods(e.target.value)
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

export function WrestlingGameControl({
    session,
    state,
}: {
    session: LiveSession;
    state: WrestlingState;
}) {
    const running = session.status === 'in_progress';
    const isPaused = session.status === 'paused';

    const pauseResume = () => {
        router.patch(
            (isPaused ? resumeRoute : pauseRoute)(session.id).url,
            {},
            { preserveScroll: true },
        );
    };

    const soundHorn = () => {
        router.patch(hornRoute(session.id).url, {}, { preserveScroll: true });
    };

    const startPhase = (phase: 'period' | 'rest') => {
        router.patch(
            periodClockRoute(session.id).url,
            { phase },
            { preserveScroll: true },
        );
    };

    const adjustClock = (deltaSeconds: number) => {
        router.patch(
            periodClockRoute(session.id).url,
            { seconds: Math.max(0, state.clock_seconds + deltaSeconds) },
            { preserveScroll: true },
        );
    };

    const resetClock = () => {
        router.patch(
            periodClockRoute(session.id).url,
            { phase: state.clock_phase },
            { preserveScroll: true },
        );
    };

    const saveSettings = (data: {
        period_duration_seconds: number;
        rest_duration_seconds: number;
        total_periods: number;
    }) => {
        router.patch(settingsRoute(session.id).url, data, {
            preserveScroll: true,
        });
    };

    const scoreMove = (side: Side, move: Move, points: number) => {
        router.patch(
            wrestlingPointRoute(session.id).url,
            { side, move, points },
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

    const declareFall = (side: Side) => {
        router.patch(
            fallRoute(session.id).url,
            { action: 'declare', side },
            { preserveScroll: true },
        );
    };

    const clearFall = () => {
        router.patch(
            fallRoute(session.id).url,
            { action: 'clear' },
            { preserveScroll: true },
        );
    };

    return (
        <div className="flex w-full flex-col gap-4 print:hidden">
            <div className="flex flex-col gap-2 rounded-xl border bg-muted/20 p-2">
                {/* Row 1: Settings, phase buttons, clock readout. */}
                <div className="flex flex-wrap items-center justify-center gap-2">
                    <SettingsDialog
                        state={state}
                        disabled={running}
                        onSave={saveSettings}
                    />

                    <Button
                        variant="outline"
                        className={cn(
                            'h-12 text-base',
                            state.clock_phase === 'period' &&
                                'border-emerald-600 text-emerald-700',
                        )}
                        onClick={() => startPhase('period')}
                    >
                        <Play aria-hidden="true" className="size-4" />
                        Start period
                    </Button>
                    <Button
                        variant="outline"
                        className={cn(
                            'h-12 text-base',
                            state.clock_phase === 'rest' &&
                                'border-sky-600 text-sky-700',
                        )}
                        onClick={() => startPhase('rest')}
                    >
                        <TimerReset aria-hidden="true" className="size-4" />
                        Start rest
                    </Button>

                    <div className="flex h-12 items-center gap-1.5 rounded-md border bg-background px-3">
                        <span className="text-sm text-muted-foreground">
                            {state.clock_phase === 'period' ? 'Period' : 'Rest'}
                        </span>
                        <span className="font-mono text-lg font-semibold tabular-nums">
                            <CountdownClock
                                anchor={state.clock_updated_at}
                                baseSeconds={state.clock_seconds}
                                running={running}
                            />
                        </span>
                        <Button
                            variant="ghost"
                            size="sm"
                            className="h-8"
                            onClick={() => adjustClock(-10)}
                        >
                            -10s
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            className="h-8"
                            onClick={resetClock}
                        >
                            Reset
                        </Button>
                    </div>
                </div>

                {/* Row 2: Horn, Whistle (pause/resume), clear fall. */}
                <div className="flex flex-wrap items-center justify-center gap-2">
                    <Button
                        className="h-12 border-orange-700 bg-orange-600 text-base font-semibold text-white hover:bg-orange-700"
                        onClick={soundHorn}
                        aria-label="Sound horn"
                    >
                        <Bell aria-hidden="true" className="size-5" />
                        Horn
                    </Button>

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

                    {state.fall_side && (
                        <Button
                            variant="outline"
                            className="h-12 text-base"
                            onClick={clearFall}
                        >
                            Clear fall
                        </Button>
                    )}
                </div>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {(
                    [
                        ['a', session.side_a_label],
                        ['b', session.side_b_label],
                    ] as const
                ).map(([side, label]) => (
                    <div
                        key={side}
                        className="flex flex-col items-center gap-2 rounded-xl border-2 p-3"
                    >
                        <span className="text-sm font-medium text-muted-foreground">
                            {label}
                        </span>
                        <div className="flex flex-wrap justify-center gap-1.5">
                            {MOVES.map(({ move, label: moveLabel, points }) => (
                                <Button
                                    key={move}
                                    className="h-10 border-emerald-700 bg-emerald-600 text-sm font-semibold text-white hover:bg-emerald-700"
                                    aria-label={`${moveLabel} +${points}, ${label}`}
                                    onClick={() =>
                                        scoreMove(side, move, points)
                                    }
                                >
                                    {moveLabel} +{points}
                                </Button>
                            ))}
                        </div>
                        <Button
                            variant="destructive"
                            className="h-10 text-sm font-semibold"
                            disabled={state.fall_side === side}
                            onClick={() => declareFall(side)}
                        >
                            Declare fall
                        </Button>
                        <CorrectionDialog
                            side={side}
                            label={label}
                            onSubmit={(delta, reason) =>
                                correctScore(side, delta, reason)
                            }
                        />
                    </div>
                ))}
            </div>
        </div>
    );
}
