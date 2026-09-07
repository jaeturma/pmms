import type { FormDataConvertible } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import { Bell, Gavel, Minus, Play, Settings2, TimerReset } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { WhistleIcon } from '@/components/icons/whistle-icon';
import { CorrectionDialog, CountdownClock } from '@/components/live-score-display';
import type { BoxingDecision, BoxingState, LiveSession } from '@/components/live-score-display';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import {
    bell as bellRoute,
    boxingDecision as boxingDecisionRoute,
    boxingDeduction as boxingDeductionRoute,
    pause as pauseRoute,
    resume as resumeRoute,
    round as roundRoute,
    roundClock as roundClockRoute,
    score as scoreRoute,
    settings as settingsRoute,
} from '@/routes/scoring';

type Side = 'a' | 'b';
type Margin = 1 | 2 | 3;

const MARGIN_LABEL: Record<Margin, string> = {
    1: '10–9 (close)',
    2: '10–8 (clear)',
    3: '10–7 (dominant)',
};

const DECISION_METHODS: { value: BoxingDecision['method']; label: string }[] = [
    { value: 'points', label: 'Points — use the judges’ cards' },
    { value: 'rsc', label: 'RSC — referee stopped contest' },
    { value: 'rsc_i', label: 'RSC-I — injury' },
    { value: 'ko', label: 'KO — knockout' },
    { value: 'dsq', label: 'DSQ — disqualification' },
    { value: 'wo', label: 'WO — walkover' },
    { value: 'abd', label: 'ABD — abandoned' },
    { value: 'nc', label: 'NC — no contest' },
];

function SettingsDialog({
    state,
    isBoxing,
    disabled,
    judgePanelLocked,
    onSave,
}: {
    state: BoxingState;
    isBoxing: boolean;
    /** Disabled while the clock is running — round/rest duration and the
     * round count only change during a stoppage. */
    disabled: boolean;
    /** The judge panel size can't change once a round has been scored. */
    judgePanelLocked: boolean;
    onSave: (data: Record<string, number | boolean>) => void;
}) {
    const [open, setOpen] = useState(false);
    const [roundSeconds, setRoundSeconds] = useState(String(state.round_duration_seconds));
    const [restSeconds, setRestSeconds] = useState(String(state.rest_duration_seconds));
    const [totalRounds, setTotalRounds] = useState(String(state.total_rounds));
    const [judgeCount, setJudgeCount] = useState(String(state.judge_count ?? 5));
    const [showJudges, setShowJudges] = useState(Boolean(state.show_live_judge_scores));

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const data: Record<string, number | boolean> = {
            round_duration_seconds: Number(roundSeconds),
            rest_duration_seconds: Number(restSeconds),
            total_rounds: Number(totalRounds),
        };

        if (isBoxing) {
            if (!judgePanelLocked) {
                data.judge_count = Number(judgeCount);
            }

            data.show_live_judge_scores = showJudges;
        }

        onSave(data);
        setOpen(false);
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    variant="outline"
                    className="h-12 text-base"
                    disabled={disabled}
                    title={disabled ? 'Pause the bout to change settings' : undefined}
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
                            <Label htmlFor="round-duration">Round duration (seconds)</Label>
                            <Input
                                id="round-duration"
                                type="number"
                                min={30}
                                max={600}
                                value={roundSeconds}
                                onChange={(e) => setRoundSeconds(e.target.value)}
                                required
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="rest-duration">Rest duration (seconds)</Label>
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
                        {isBoxing && (
                            <div className="grid gap-2">
                                <Label htmlFor="judge-count">Judges</Label>
                                <Select
                                    value={judgeCount}
                                    onValueChange={setJudgeCount}
                                    disabled={judgePanelLocked}
                                >
                                    <SelectTrigger id="judge-count">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="3">3 judges</SelectItem>
                                        <SelectItem value="5">5 judges</SelectItem>
                                    </SelectContent>
                                </Select>
                                {judgePanelLocked && (
                                    <p className="text-xs text-muted-foreground">
                                        Locked — a round has already been scored.
                                    </p>
                                )}
                            </div>
                        )}
                    </div>
                    {isBoxing && (
                        <label className="flex items-start gap-2 pb-2 text-sm">
                            <Checkbox
                                checked={showJudges}
                                onCheckedChange={(v) => setShowJudges(v === true)}
                                className="mt-0.5"
                            />
                            <span>
                                Show the judges’ round scores on the public board while the bout
                                is live.
                                <span className="block text-xs text-muted-foreground">
                                    Off by default — the operator console always shows them.
                                </span>
                            </span>
                        </label>
                    )}
                    <DialogFooter>
                        <Button type="submit">Save settings</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** Combat-rounds (taekwondo/wushu/pencak silat/arnis) keep the single
 * judged-pair-per-round entry. Boxing uses `JudgeScorecardPanel` instead. */
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
                    title={disabled ? 'Every scheduled round has already been judged' : undefined}
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

type JudgeEntry = { winner: Side | null; margin: Margin };

function JudgeScorecardPanel({
    session,
    judgeCount,
    nextRound,
    disabled,
    onSubmit,
}: {
    session: LiveSession;
    judgeCount: number;
    nextRound: number;
    disabled: boolean;
    onSubmit: (cards: { judge: number; red: number; blue: number }[]) => void;
}) {
    const blank = (): JudgeEntry[] =>
        Array.from({ length: judgeCount }, () => ({ winner: null, margin: 1 }));
    const [entries, setEntries] = useState<JudgeEntry[]>(blank);

    const setEntry = (index: number, patch: Partial<JudgeEntry>) => {
        setEntries((prev) => prev.map((e, i) => (i === index ? { ...e, ...patch } : e)));
    };

    const complete = entries.every((e) => e.winner !== null);

    const save = () => {
        if (!complete || disabled) {
            return;
        }

        onSubmit(
            entries.map((e, i) => ({
                judge: i + 1,
                red: e.winner === 'a' ? 10 : 10 - e.margin,
                blue: e.winner === 'b' ? 10 : 10 - e.margin,
            })),
        );
        setEntries(blank());
    };

    return (
        <div className="flex w-full flex-col gap-3 rounded-xl border bg-muted/20 p-3">
            <div className="flex items-center justify-between">
                <span className="text-sm font-semibold">
                    Round {nextRound} — judges’ scorecards
                </span>
                <span className="text-xs text-muted-foreground">10-point must, no even round</span>
            </div>

            {disabled ? (
                <p className="text-sm text-muted-foreground">
                    Every scheduled round has been scored.
                </p>
            ) : (
                <>
                    <div className="flex flex-col gap-2">
                        {entries.map((entry, index) => (
                            <div
                                key={index}
                                className="grid grid-cols-[auto_1fr_1fr] items-center gap-2 rounded-lg border bg-background px-2 py-1.5 sm:grid-cols-[auto_1fr_1fr_180px]"
                            >
                                <span className="text-xs font-semibold text-muted-foreground">
                                    Judge {index + 1}
                                </span>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant={entry.winner === 'a' ? 'default' : 'outline'}
                                    className={cn(
                                        'h-9 justify-start truncate',
                                        entry.winner === 'a' &&
                                            'border-red-700 bg-red-600 text-white hover:bg-red-700',
                                    )}
                                    onClick={() => setEntry(index, { winner: 'a' })}
                                >
                                    {session.side_a_label}
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant={entry.winner === 'b' ? 'default' : 'outline'}
                                    className={cn(
                                        'h-9 justify-start truncate',
                                        entry.winner === 'b' &&
                                            'border-blue-700 bg-blue-600 text-white hover:bg-blue-700',
                                    )}
                                    onClick={() => setEntry(index, { winner: 'b' })}
                                >
                                    {session.side_b_label}
                                </Button>
                                <Select
                                    value={String(entry.margin)}
                                    onValueChange={(v) =>
                                        setEntry(index, { margin: Number(v) as Margin })
                                    }
                                >
                                    <SelectTrigger
                                        className="col-span-3 h-9 sm:col-span-1"
                                        aria-label={`Judge ${index + 1} margin`}
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {([1, 2, 3] as Margin[]).map((m) => (
                                            <SelectItem key={m} value={String(m)}>
                                                {MARGIN_LABEL[m]}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        ))}
                    </div>
                    <Button
                        className="h-11 border-emerald-700 bg-emerald-600 font-semibold text-white hover:bg-emerald-700"
                        disabled={!complete}
                        onClick={save}
                        title={complete ? undefined : 'Set a winner for every judge first'}
                    >
                        Save round {nextRound}
                    </Button>
                </>
            )}
        </div>
    );
}

function DecisionDialog({
    session,
    onSubmit,
}: {
    session: LiveSession;
    onSubmit: (data: { method: string; winner: Side | null; note: string }) => void;
}) {
    const [open, setOpen] = useState(false);
    const [method, setMethod] = useState<string>('points');
    const [winner, setWinner] = useState<Side | ''>('');
    const [note, setNote] = useState('');

    const needsWinner = method !== 'points' && method !== 'nc';

    const submit = (e: FormEvent) => {
        e.preventDefault();

        if (needsWinner && winner === '') {
            return;
        }

        onSubmit({ method, winner: winner === '' ? null : winner, note });
        setOpen(false);
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" className="h-12 text-base">
                    <Gavel aria-hidden="true" className="size-4" />
                    Bout decision
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Record the bout decision</DialogTitle>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="decision-method">Method</Label>
                            <Select value={method} onValueChange={setMethod}>
                                <SelectTrigger id="decision-method">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {DECISION_METHODS.map((m) => (
                                        <SelectItem key={m.value} value={m.value}>
                                            {m.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <p className="text-xs text-muted-foreground">
                                “Points” with no corner selected reverts the board to the
                                computed result.
                            </p>
                        </div>
                        <div className="grid gap-2">
                            <Label>Winning corner{needsWinner ? '' : ' (optional)'}</Label>
                            <div className="grid grid-cols-2 gap-2">
                                <Button
                                    type="button"
                                    variant={winner === 'a' ? 'default' : 'outline'}
                                    className={cn(
                                        winner === 'a' &&
                                            'border-red-700 bg-red-600 text-white hover:bg-red-700',
                                    )}
                                    onClick={() => setWinner(winner === 'a' ? '' : 'a')}
                                >
                                    {session.side_a_label}
                                </Button>
                                <Button
                                    type="button"
                                    variant={winner === 'b' ? 'default' : 'outline'}
                                    className={cn(
                                        winner === 'b' &&
                                            'border-blue-700 bg-blue-600 text-white hover:bg-blue-700',
                                    )}
                                    onClick={() => setWinner(winner === 'b' ? '' : 'b')}
                                >
                                    {session.side_b_label}
                                </Button>
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="decision-note">Note (optional)</Label>
                            <Input
                                id="decision-note"
                                value={note}
                                maxLength={255}
                                onChange={(e) => setNote(e.target.value)}
                                placeholder="e.g. Referee stopped the contest in round 2"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={needsWinner && winner === ''}>
                            Save decision
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DecisionSummary({
    session,
    decision,
}: {
    session: LiveSession;
    decision: BoxingDecision;
}) {
    const corner =
        decision.winner === 'a'
            ? session.side_a_label
            : decision.winner === 'b'
              ? session.side_b_label
              : null;
    const tally = decision.tally;
    const score = tally ? ` ${Math.max(tally.a, tally.b)}–${Math.min(tally.a, tally.b)}` : '';
    const methodLabel =
        decision.method === 'points'
            ? decision.type
                ? `${decision.type[0].toUpperCase()}${decision.type.slice(1)} decision`
                : 'Decision'
            : decision.method.replace('_', '-').toUpperCase();

    return (
        <div
            className={cn(
                'rounded-lg border px-3 py-2 text-center text-sm',
                decision.status === 'final'
                    ? 'border-emerald-600 bg-emerald-50 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200'
                    : 'border-muted bg-muted/40 text-muted-foreground',
            )}
        >
            {corner ? (
                <>
                    <span className="font-semibold">{corner}</span>{' '}
                    {decision.status === 'final' ? 'wins' : 'leads'} — {methodLabel}
                    {score}
                    {decision.status === 'provisional' && (
                        <span className="ml-1">
                            (after {decision.rounds_scored}/{decision.total_rounds} rounds)
                        </span>
                    )}
                </>
            ) : (
                <span>{decision.method === 'nc' ? 'No contest' : 'Judges’ cards level'}</span>
            )}
            {decision.note && (
                <span className="mt-0.5 block text-xs">{decision.note}</span>
            )}
        </div>
    );
}

/** Defaults for every key this scoreboard reads — a session started before
 * a given field shipped only has a partial `sport_state`, so every other
 * field must read cleanly as its default (same fallback convention as
 * basketball's `BASKETBALL_STATE_DEFAULTS`). */
const BOXING_STATE_DEFAULTS: BoxingState = {
    rounds: [],
    round_duration_seconds: 120,
    rest_duration_seconds: 60,
    total_rounds: 3,
    clock_seconds: 120,
    clock_updated_at: null,
    clock_phase: 'round',
    bell_sounded_at: null,
    judge_count: 5,
    judge_rounds: [],
    deductions_a: 0,
    deductions_b: 0,
    decision: null,
    show_live_judge_scores: false,
};

export function BoxingGameControl({
    session,
    state: rawState,
}: {
    session: LiveSession;
    state: BoxingState;
}) {
    const state: BoxingState = { ...BOXING_STATE_DEFAULTS, ...rawState };
    const isBoxing = session.board_type === 'boxing';
    const running = session.status === 'in_progress';
    const isPaused = session.status === 'paused';

    const judgeCount = state.judge_count ?? 5;
    const judgeRounds = state.judge_rounds ?? [];
    const roundsJudged = isBoxing ? judgeRounds.length : state.rounds.length;
    const boutComplete = roundsJudged >= state.total_rounds;
    const decision = state.decision ?? null;

    const patch = (url: string, data: Record<string, FormDataConvertible> = {}) => {
        router.patch(url, data, { preserveScroll: true });
    };

    const pauseResume = () =>
        patch((isPaused ? resumeRoute : pauseRoute)(session.id).url);
    const ringBell = () => patch(bellRoute(session.id).url);
    const startPhase = (phase: 'round' | 'rest') =>
        patch(roundClockRoute(session.id).url, { phase });
    const adjustClock = (deltaSeconds: number) =>
        patch(roundClockRoute(session.id).url, {
            seconds: Math.max(0, state.clock_seconds + deltaSeconds),
        });
    const resetClock = () =>
        patch(roundClockRoute(session.id).url, { phase: state.clock_phase });

    const saveSettings = (data: Record<string, number | boolean>) =>
        patch(settingsRoute(session.id).url, data);

    const recordRound = (scoreA: number, scoreB: number) =>
        patch(roundRoute(session.id).url, { score_a: scoreA, score_b: scoreB });

    const recordJudgeRound = (cards: { judge: number; red: number; blue: number }[]) =>
        patch(roundRoute(session.id).url, { cards });

    const deductPoint = (side: Side, points: number) =>
        patch(boxingDeductionRoute(session.id).url, { side, points });
    const resetDeductions = () =>
        patch(boxingDeductionRoute(session.id).url, { action: 'reset' });

    const recordDecision = (data: { method: string; winner: Side | null; note: string }) =>
        patch(boxingDecisionRoute(session.id).url, data);

    const correctScore = (side: Side, delta: number, reason: string) =>
        patch(scoreRoute(session.id).url, { type: 'correction', side, delta, reason });

    return (
        <div className="flex w-full flex-col gap-4 print:hidden">
            <div className="flex flex-col gap-2 rounded-xl border bg-muted/20 p-2">
                {/* Row 1: Settings, phase buttons, clock readout. */}
                <div className="flex flex-wrap items-center justify-center gap-2">
                    <SettingsDialog
                        state={state}
                        isBoxing={isBoxing}
                        disabled={running}
                        judgePanelLocked={roundsJudged > 0}
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
                            state.clock_phase === 'rest' && 'border-sky-600 text-sky-700',
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
                        <Button variant="ghost" size="sm" className="h-8" onClick={resetClock}>
                            Reset
                        </Button>
                    </div>
                </div>

                {/* Row 2: Bell, Whistle (pause/resume), and — boxing only —
                    the bout decision. */}
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

                    {isBoxing && (
                        <DecisionDialog session={session} onSubmit={recordDecision} />
                    )}
                </div>
            </div>

            {isBoxing ? (
                <>
                    <JudgeScorecardPanel
                        session={session}
                        judgeCount={judgeCount}
                        nextRound={roundsJudged + 1}
                        disabled={boutComplete}
                        onSubmit={recordJudgeRound}
                    />

                    {decision && <DecisionSummary session={session} decision={decision} />}

                    {/* Referee point deductions — bout-wide, applied to the
                        judges' cards, never to a raw round score. */}
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        {(['a', 'b'] as Side[]).map((side) => {
                            const label =
                                side === 'a' ? session.side_a_label : session.side_b_label;
                            const count =
                                side === 'a'
                                    ? (state.deductions_a ?? 0)
                                    : (state.deductions_b ?? 0);

                            return (
                                <div
                                    key={side}
                                    className={cn(
                                        'flex flex-col items-center gap-2 rounded-xl border-2 p-3',
                                        side === 'a'
                                            ? 'border-red-500/40'
                                            : 'border-blue-500/40',
                                    )}
                                >
                                    <span className="text-sm font-medium text-muted-foreground">
                                        {label} — deductions: {count}
                                    </span>
                                    <div className="flex flex-wrap justify-center gap-1.5">
                                        {[1, 2, 3].map((p) => (
                                            <Button
                                                key={p}
                                                variant="outline"
                                                size="sm"
                                                onClick={() => deductPoint(side, p)}
                                                aria-label={`Deduct ${p} point${p > 1 ? 's' : ''} from ${label}`}
                                            >
                                                <Minus aria-hidden="true" className="size-3" />
                                                {p}
                                            </Button>
                                        ))}
                                    </div>
                                    <CorrectionDialog
                                        side={side}
                                        label={label}
                                        onSubmit={(delta, reason) =>
                                            correctScore(side, delta, reason)
                                        }
                                    />
                                </div>
                            );
                        })}
                    </div>
                    {((state.deductions_a ?? 0) > 0 || (state.deductions_b ?? 0) > 0) && (
                        <div className="flex justify-center">
                            <Button variant="ghost" size="sm" onClick={resetDeductions}>
                                Reset all deductions
                            </Button>
                        </div>
                    )}
                </>
            ) : (
                <>
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
                                onSubmit={(delta, reason) => correctScore('a', delta, reason)}
                            />
                        </div>
                        <div className="flex flex-col items-center gap-2 rounded-xl border-2 border-blue-500/40 p-3">
                            <span className="text-sm font-medium text-muted-foreground">
                                {session.side_b_label} (Blue corner)
                            </span>
                            <CorrectionDialog
                                side="b"
                                label={session.side_b_label}
                                onSubmit={(delta, reason) => correctScore('b', delta, reason)}
                            />
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}
