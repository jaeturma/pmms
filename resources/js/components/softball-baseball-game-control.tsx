import { router } from '@inertiajs/react';
import { Settings2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { WhistleIcon } from '@/components/icons/whistle-icon';
import { CorrectionDialog, CountDots } from '@/components/live-score-display';
import type {
    LiveSession,
    SoftballState,
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
    count as countRoute,
    inningRun as inningRunRoute,
    pause as pauseRoute,
    resume as resumeRoute,
    score as scoreRoute,
    settings as settingsRoute,
} from '@/routes/scoring';

type Side = 'a' | 'b';
type CountAction = 'out' | 'ball' | 'strike' | 'reset_count';

const SOFTBALL_BALLS_DISPLAY_MAX = 3;
const SOFTBALL_STRIKES_DISPLAY_MAX = 2;
const SOFTBALL_OUTS_DISPLAY_MAX = 2;

function SettingsDialog({
    state,
    disabled,
    onSave,
}: {
    state: SoftballState;
    /** Disabled while play is running — matches basketball/boxing's own
     * "settings only change during a stoppage" rule. */
    disabled: boolean;
    onSave: (data: {
        team_color_a: string;
        team_color_b: string;
        innings_scheduled: number;
    }) => void;
}) {
    const [open, setOpen] = useState(false);
    const [colorA, setColorA] = useState(state.team_color_a);
    const [colorB, setColorB] = useState(state.team_color_b);
    const [inningsScheduled, setInningsScheduled] = useState(
        String(state.innings_scheduled),
    );

    const submit = (e: FormEvent) => {
        e.preventDefault();
        onSave({
            team_color_a: colorA,
            team_color_b: colorB,
            innings_scheduled: Number(inningsScheduled),
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
                    <div className="grid gap-4 py-4 sm:grid-cols-2">
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="innings-scheduled">
                                Regulation innings
                            </Label>
                            <Input
                                id="innings-scheduled"
                                type="number"
                                min={3}
                                max={15}
                                value={inningsScheduled}
                                onChange={(e) =>
                                    setInningsScheduled(e.target.value)
                                }
                                required
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="team-color-a">Side A color</Label>
                            <Input
                                id="team-color-a"
                                type="color"
                                className="h-10 w-full p-1"
                                value={colorA}
                                onChange={(e) => setColorA(e.target.value)}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="team-color-b">Side B color</Label>
                            <Input
                                id="team-color-b"
                                type="color"
                                className="h-10 w-full p-1"
                                value={colorB}
                                onChange={(e) => setColorB(e.target.value)}
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

function RunDialog({
    side,
    label,
    color,
    onSubmit,
}: {
    side: Side;
    label: string;
    color: string;
    onSubmit: (runs: number) => void;
}) {
    const [open, setOpen] = useState(false);
    const [runs, setRuns] = useState('1');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        onSubmit(Number(runs));
        setOpen(false);
        setRuns('1');
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    className="h-11 text-base font-semibold text-white"
                    style={{ backgroundColor: color, borderColor: color }}
                >
                    Record run(s) — {label}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Record runs — {label}</DialogTitle>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor={`runs-${side}`}>
                                Runs scored this inning
                            </Label>
                            <Input
                                id={`runs-${side}`}
                                type="number"
                                min={1}
                                max={20}
                                value={runs}
                                onChange={(e) => setRuns(e.target.value)}
                                required
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="submit">Save</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** Defaults for every key this WP added to sport_state — a session started
 * before this shipped only has the original count/inning fields, so
 * `innings_scheduled`/team colors read as `undefined` without this (same
 * fallback convention as basketball's/boxing's own state defaults). */
const SOFTBALL_STATE_DEFAULTS: SoftballState = {
    inning: 1,
    half: 'top',
    outs: 0,
    balls: 0,
    strikes: 0,
    innings: [],
    innings_scheduled: 7,
    team_color_a: '#dc2626',
    team_color_b: '#2563eb',
};

export function SoftballBaseballGameControl({
    session,
    state: rawState,
}: {
    session: LiveSession;
    state: SoftballState;
}) {
    const state: SoftballState = { ...SOFTBALL_STATE_DEFAULTS, ...rawState };
    const isPaused = session.status === 'paused';
    const running = session.status === 'in_progress';
    // Standard scoreboard convention: the away side (listed/side A) bats
    // in the top half, the home side (side B) bats in the bottom half —
    // a real, fixed rule (not operator-choosable per half, unlike
    // basketball's possession arrow), used here only to highlight which
    // side is currently up, never to gate an action.
    const battingSide: Side = state.half === 'top' ? 'a' : 'b';

    const pauseResume = () => {
        router.patch(
            (isPaused ? resumeRoute : pauseRoute)(session.id).url,
            {},
            { preserveScroll: true },
        );
    };

    const recordCount = (action: CountAction) => {
        router.patch(
            countRoute(session.id).url,
            { action },
            { preserveScroll: true },
        );
    };

    const recordRun = (side: Side, runs: number) => {
        router.patch(
            inningRunRoute(session.id).url,
            { side, runs },
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

    const saveSettings = (data: {
        team_color_a: string;
        team_color_b: string;
        innings_scheduled: number;
    }) => {
        router.patch(settingsRoute(session.id).url, data, {
            preserveScroll: true,
        });
    };

    return (
        <div className="flex w-full flex-col gap-4 print:hidden">
            <div className="flex flex-col gap-2 rounded-xl border bg-muted/20 p-2">
                {/* Row 1: Settings, then the count controls. */}
                <div className="flex flex-wrap items-center justify-center gap-2">
                    <SettingsDialog
                        state={state}
                        disabled={running}
                        onSave={saveSettings}
                    />

                    <div className="flex h-12 flex-wrap items-center gap-1.5 rounded-md border bg-background px-3">
                        <span className="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <CountDots
                                count={state.balls}
                                max={SOFTBALL_BALLS_DISPLAY_MAX}
                                colorClass="bg-success"
                            />
                            Balls
                        </span>
                        <span className="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <CountDots
                                count={state.strikes}
                                max={SOFTBALL_STRIKES_DISPLAY_MAX}
                                colorClass="bg-warning"
                            />
                            Strikes
                        </span>
                        <span className="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <CountDots
                                count={state.outs}
                                max={SOFTBALL_OUTS_DISPLAY_MAX}
                                colorClass="bg-destructive"
                            />
                            Outs
                        </span>
                    </div>
                </div>

                {/* Row 2: the real count-advancing actions, plus Whistle
                    (pause/resume) — same color convention as basketball/
                    boxing: green while it would Pause, orange while it
                    would Resume. */}
                <div className="flex flex-wrap items-center justify-center gap-2">
                    <Button
                        className="h-11 min-w-20 border-success bg-success text-base font-semibold text-success-foreground hover:bg-success/90"
                        onClick={() => recordCount('ball')}
                    >
                        Ball
                    </Button>
                    <Button
                        className="h-11 min-w-20 border-warning bg-warning text-base font-semibold text-warning-foreground hover:bg-warning/90"
                        onClick={() => recordCount('strike')}
                    >
                        Strike
                    </Button>
                    <Button
                        variant="destructive"
                        className="h-11 min-w-20 text-base font-semibold"
                        onClick={() => recordCount('out')}
                    >
                        Out
                    </Button>
                    <Button
                        variant="outline"
                        className="h-11 text-base"
                        onClick={() => recordCount('reset_count')}
                    >
                        Reset count
                    </Button>

                    <Button
                        className={cn(
                            'h-11 text-base font-semibold text-white',
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

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {(
                    [
                        ['a', session.side_a_label, state.team_color_a],
                        ['b', session.side_b_label, state.team_color_b],
                    ] as const
                ).map(([side, label, color]) => (
                    <div
                        key={side}
                        className="flex flex-col items-center gap-2 rounded-xl border-2 p-3"
                        style={{ borderColor: color }}
                    >
                        <span className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                            <span
                                className="size-2.5 shrink-0 rounded-full"
                                style={{ backgroundColor: color }}
                                aria-hidden="true"
                            />
                            {label}
                            {battingSide === side && (
                                <span className="rounded-full bg-primary px-2 py-0.5 text-xs font-semibold text-primary-foreground">
                                    At bat
                                </span>
                            )}
                        </span>
                        <RunDialog
                            side={side}
                            label={label}
                            color={color}
                            onSubmit={(runs) => recordRun(side, runs)}
                        />
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
