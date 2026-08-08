import { router } from '@inertiajs/react';
import { Bell, Play, Settings2, TimerReset } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { WhistleIcon } from '@/components/icons/whistle-icon';
import {
    CorrectionDialog,
    CountdownClock,
} from '@/components/live-score-display';
import type { BoxingState, LiveSession } from '@/components/live-score-display';
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
    bell as bellRoute,
    pause as pauseRoute,
    resume as resumeRoute,
    round as roundRoute,
    roundClock as roundClockRoute,
    score as scoreRoute,
    settings as settingsRoute,
} from '@/routes/scoring';

type Side = 'a' | 'b';

function SettingsDialog({
    state,
    disabled,
    onSave,
}: {
    state: BoxingState;
    /** Disabled while the clock is running (matches the basketball
     * control's own rule) — round/rest duration and the round count only
     * change during a stoppage. */
    disabled: boolean;
    onSave: (data: {
        round_duration_seconds: number;
        rest_duration_seconds: number;
        total_rounds: number;
    }) => void;
}) {
    const [open, setOpen] = useState(false);
    const [roundSeconds, setRoundSeconds] = useState(
        String(state.round_duration_seconds),
    );
    const [restSeconds, setRestSeconds] = useState(
        String(state.rest_duration_seconds),
    );
    const [totalRounds, setTotalRounds] = useState(String(state.total_rounds));

    const submit = (e: FormEvent) => {
        e.preventDefault();
        onSave({
            round_duration_seconds: Number(roundSeconds),
            rest_duration_seconds: Number(restSeconds),
            total_rounds: Number(totalRounds),
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
                            ? 'Pause the bout to change settings'
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
                        <DialogTitle>Bout settings</DialogTitle>
                    </DialogHeader>
                    <div className="grid gap-4 py-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="round-duration">
                                Round duration (seconds)
                            </Label>
                            <Input
                                id="round-duration"
                                type="number"
                                min={30}
                                max={600}
                                value={roundSeconds}
                                onChange={(e) =>
                                    setRoundSeconds(e.target.value)
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
                                min={15}
                                max={300}
                                value={restSeconds}
                                onChange={(e) => setRestSeconds(e.target.value)}
                                required
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="total-rounds">Total rounds</Label>
                            <Input
                                id="total-rounds"
                                type="number"
                                min={1}
                                max={12}
                                value={totalRounds}
                                onChange={(e) => setTotalRounds(e.target.value)}
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

function RoundScoreDialog({
    labelA,
    labelB,
    nextRound,
    disabled,
    onSubmit,
}: {
    labelA: string;
    labelB: string;
    nextRound: number;
    disabled: boolean;
    onSubmit: (scoreA: number, scoreB: number) => void;
}) {
    const [open, setOpen] = useState(false);
    const [scoreA, setScoreA] = useState('10');
    const [scoreB, setScoreB] = useState('9');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        onSubmit(Number(scoreA), Number(scoreB));
        setOpen(false);
        setScoreA('10');
        setScoreB('9');
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    className="h-12 border-emerald-700 bg-emerald-600 text-base font-semibold text-white hover:bg-emerald-700"
                    disabled={disabled}
                    title={
                        disabled
                            ? 'Every scheduled round has already been judged'
                            : undefined
                    }
                >
                    Record round {nextRound}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Round {nextRound} score</DialogTitle>
                    </DialogHeader>
                    <div className="grid gap-4 py-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="round-score-a">{labelA}</Label>
                            <Input
                                id="round-score-a"
                                type="number"
                                min={0}
                                max={10}
                                value={scoreA}
                                onChange={(e) => setScoreA(e.target.value)}
                                required
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="round-score-b">{labelB}</Label>
                            <Input
                                id="round-score-b"
                                type="number"
                                min={0}
                                max={10}
                                value={scoreB}
                                onChange={(e) => setScoreB(e.target.value)}
                                required
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="submit">Save round score</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** Defaults for every key this WP added to sport_state — a session started
 * before this shipped only has `{rounds: []}`, so every other field reads
 * as `undefined` without this (same fallback convention as basketball's
 * `BASKETBALL_STATE_DEFAULTS`). */
const BOXING_STATE_DEFAULTS: BoxingState = {
    rounds: [],
    round_duration_seconds: 120,
    rest_duration_seconds: 60,
    total_rounds: 3,
    clock_seconds: 120,
    clock_updated_at: null,
    clock_phase: 'round',
    bell_sounded_at: null,
};

export function BoxingGameControl({
    session,
    state: rawState,
}: {
    session: LiveSession;
    state: BoxingState;
}) {
    const state: BoxingState = { ...BOXING_STATE_DEFAULTS, ...rawState };
    const running = session.status === 'in_progress';
    const isPaused = session.status === 'paused';
    const roundsJudged = state.rounds.length;
    const boutComplete = roundsJudged >= state.total_rounds;

    const pauseResume = () => {
        router.patch(
            (isPaused ? resumeRoute : pauseRoute)(session.id).url,
            {},
            { preserveScroll: true },
        );
    };

    const ringBell = () => {
        router.patch(bellRoute(session.id).url, {}, { preserveScroll: true });
    };

    const startPhase = (phase: 'round' | 'rest') => {
        router.patch(
            roundClockRoute(session.id).url,
            { phase },
            { preserveScroll: true },
        );
    };

    const adjustClock = (deltaSeconds: number) => {
        router.patch(
            roundClockRoute(session.id).url,
            { seconds: Math.max(0, state.clock_seconds + deltaSeconds) },
            { preserveScroll: true },
        );
    };

    const resetClock = () => {
        router.patch(
            roundClockRoute(session.id).url,
            { phase: state.clock_phase },
            { preserveScroll: true },
        );
    };

    const saveSettings = (data: {
        round_duration_seconds: number;
        rest_duration_seconds: number;
        total_rounds: number;
    }) => {
        router.patch(settingsRoute(session.id).url, data, {
            preserveScroll: true,
        });
    };

    const recordRound = (scoreA: number, scoreB: number) => {
        router.patch(
            roundRoute(session.id).url,
            { score_a: scoreA, score_b: scoreB },
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
                            state.clock_phase === 'round' &&
                                'border-emerald-600 text-emerald-700',
                        )}
                        onClick={() => startPhase('round')}
                    >
                        <Play aria-hidden="true" className="size-4" />
                        Start round
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
                            {state.clock_phase === 'round' ? 'Round' : 'Rest'}
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

                {/* Row 2: Bell, then Whistle (pause/resume) — color reflects
                    what the click will do, same convention as basketball. */}
                <div className="flex flex-wrap items-center justify-center gap-2">
                    <Button
                        className="h-12 border-orange-700 bg-orange-600 text-base font-semibold text-white hover:bg-orange-700"
                        onClick={ringBell}
                        aria-label="Ring bell"
                    >
                        <Bell aria-hidden="true" className="size-5" />
                        Bell
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
                </div>
            </div>

            <div className="flex justify-center">
                <RoundScoreDialog
                    labelA={session.side_a_label}
                    labelB={session.side_b_label}
                    nextRound={roundsJudged + 1}
                    disabled={boutComplete}
                    onSubmit={recordRound}
                />
            </div>
            {boutComplete && (
                <p className="text-center text-sm text-muted-foreground">
                    All {state.total_rounds} scheduled rounds have been judged.
                </p>
            )}

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="flex flex-col items-center gap-2 rounded-xl border-2 border-red-500/40 p-3">
                    <span className="text-sm font-medium text-muted-foreground">
                        {session.side_a_label} (Red corner)
                    </span>
                    <CorrectionDialog
                        side="a"
                        label={session.side_a_label}
                        onSubmit={(delta, reason) =>
                            correctScore('a', delta, reason)
                        }
                    />
                </div>
                <div className="flex flex-col items-center gap-2 rounded-xl border-2 border-blue-500/40 p-3">
                    <span className="text-sm font-medium text-muted-foreground">
                        {session.side_b_label} (Blue corner)
                    </span>
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
