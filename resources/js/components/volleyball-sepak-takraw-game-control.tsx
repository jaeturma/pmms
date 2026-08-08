import { router } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Settings2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { WhistleIcon } from '@/components/icons/whistle-icon';
import { CorrectionDialog } from '@/components/live-score-display';
import type {
    LiveSession,
    RallySetsState,
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
    pause as pauseRoute,
    possession as possessionRoute,
    rallyPoint as rallyPointRoute,
    resume as resumeRoute,
    settings as settingsRoute,
} from '@/routes/scoring';

type Side = 'a' | 'b';

function SettingsDialog({
    state,
    disabled,
    onSave,
}: {
    state: RallySetsState;
    /** Disabled while play is running — same "settings only change during
     * a stoppage" rule as every other sport's dedicated control. */
    disabled: boolean;
    onSave: (data: {
        set_target_points: number;
        deciding_set_target_points: number;
        sets_to_win: number;
    }) => void;
}) {
    const [open, setOpen] = useState(false);
    const [setTarget, setSetTarget] = useState(String(state.set_target_points));
    const [decidingTarget, setDecidingTarget] = useState(
        String(state.deciding_set_target_points),
    );
    const [setsToWin, setSetsToWin] = useState(String(state.sets_to_win));

    const submit = (e: FormEvent) => {
        e.preventDefault();
        onSave({
            set_target_points: Number(setTarget),
            deciding_set_target_points: Number(decidingTarget),
            sets_to_win: Number(setsToWin),
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
                        <DialogTitle>Match settings</DialogTitle>
                    </DialogHeader>
                    <div className="grid gap-4 py-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="set-target">
                                Points to win a set
                            </Label>
                            <Input
                                id="set-target"
                                type="number"
                                min={5}
                                max={50}
                                value={setTarget}
                                onChange={(e) => setSetTarget(e.target.value)}
                                required
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="deciding-set-target">
                                Points to win the deciding set
                            </Label>
                            <Input
                                id="deciding-set-target"
                                type="number"
                                min={5}
                                max={50}
                                value={decidingTarget}
                                onChange={(e) =>
                                    setDecidingTarget(e.target.value)
                                }
                                required
                            />
                        </div>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="sets-to-win">
                                Sets needed to win the match
                            </Label>
                            <Input
                                id="sets-to-win"
                                type="number"
                                min={1}
                                max={5}
                                value={setsToWin}
                                onChange={(e) => setSetsToWin(e.target.value)}
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
 * without this (same fallback convention as basketball's/boxing's own
 * state defaults). */
const RALLY_SETS_STATE_DEFAULTS: RallySetsState = {
    sets: [],
    current_set_score_a: 0,
    current_set_score_b: 0,
    sets_won_a: 0,
    sets_won_b: 0,
    set_target_points: 25,
    deciding_set_target_points: 15,
    sets_to_win: 3,
    possession: null,
};

export function VolleyballSepakTakrawGameControl({
    session,
    state: rawState,
}: {
    session: LiveSession;
    state: RallySetsState;
}) {
    const state: RallySetsState = {
        ...RALLY_SETS_STATE_DEFAULTS,
        ...rawState,
    };
    const running = session.status === 'in_progress';
    const isPaused = session.status === 'paused';
    const matchComplete =
        state.sets_won_a >= state.sets_to_win ||
        state.sets_won_b >= state.sets_to_win;

    const pauseResume = () => {
        router.patch(
            (isPaused ? resumeRoute : pauseRoute)(session.id).url,
            {},
            { preserveScroll: true },
        );
    };

    const cycleServe = () => {
        const next =
            state.possession === 'a'
                ? 'b'
                : state.possession === 'b'
                  ? null
                  : 'a';

        router.patch(
            possessionRoute(session.id).url,
            { side: next },
            { preserveScroll: true },
        );
    };

    const addPoint = (side: Side) => {
        router.patch(
            rallyPointRoute(session.id).url,
            { type: 'point', side, delta: 1 },
            { preserveScroll: true },
        );
    };

    const correctSetScore = (side: Side, delta: number, reason: string) => {
        router.patch(
            rallyPointRoute(session.id).url,
            { type: 'correction', side, delta, reason },
            { preserveScroll: true },
        );
    };

    const saveSettings = (data: {
        set_target_points: number;
        deciding_set_target_points: number;
        sets_to_win: number;
    }) => {
        router.patch(settingsRoute(session.id).url, data, {
            preserveScroll: true,
        });
    };

    return (
        <div className="flex w-full flex-col gap-4 print:hidden">
            <div className="flex flex-col gap-2 rounded-xl border bg-muted/20 p-2">
                {/* Row 1: Settings, serve indicator, Whistle. */}
                <div className="flex flex-wrap items-center justify-center gap-2">
                    <SettingsDialog
                        state={state}
                        disabled={running}
                        onSave={saveSettings}
                    />

                    <Button
                        className="h-12 border-sky-700 bg-sky-600 text-base font-semibold text-white hover:bg-sky-700"
                        onClick={cycleServe}
                        aria-label="Toggle serve indicator"
                    >
                        {state.possession === 'a' && (
                            <ArrowLeft aria-hidden="true" className="size-5" />
                        )}
                        {state.possession === 'b' && (
                            <ArrowRight aria-hidden="true" className="size-5" />
                        )}
                        Serve
                        {state.possession === null && ': none'}
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

            {matchComplete && (
                <p className="text-center text-sm text-muted-foreground">
                    {state.sets_won_a > state.sets_won_b
                        ? session.side_a_label
                        : session.side_b_label}{' '}
                    has won the {state.sets_to_win} sets needed for the match.
                </p>
            )}

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="flex flex-col items-center gap-2 rounded-xl border-2 p-3">
                    <span className="text-sm font-medium text-muted-foreground">
                        {session.side_a_label} — {state.sets_won_a} set
                        {state.sets_won_a === 1 ? '' : 's'}
                    </span>
                    <Button
                        className="h-14 min-w-24 border-emerald-700 bg-emerald-600 text-2xl font-bold text-white hover:bg-emerald-700"
                        aria-label={`Add a point, ${session.side_a_label}`}
                        onClick={() => addPoint('a')}
                    >
                        +1
                    </Button>
                    <CorrectionDialog
                        side="a"
                        label={session.side_a_label}
                        onSubmit={(delta, reason) =>
                            correctSetScore('a', delta, reason)
                        }
                    />
                </div>
                <div className="flex flex-col items-center gap-2 rounded-xl border-2 p-3">
                    <span className="text-sm font-medium text-muted-foreground">
                        {session.side_b_label} — {state.sets_won_b} set
                        {state.sets_won_b === 1 ? '' : 's'}
                    </span>
                    <Button
                        className="h-14 min-w-24 border-emerald-700 bg-emerald-600 text-2xl font-bold text-white hover:bg-emerald-700"
                        aria-label={`Add a point, ${session.side_b_label}`}
                        onClick={() => addPoint('b')}
                    >
                        +1
                    </Button>
                    <CorrectionDialog
                        side="b"
                        label={session.side_b_label}
                        onSubmit={(delta, reason) =>
                            correctSetScore('b', delta, reason)
                        }
                    />
                </div>
            </div>
        </div>
    );
}
