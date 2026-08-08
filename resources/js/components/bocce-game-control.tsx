import { router } from '@inertiajs/react';
import { Settings2, Undo2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { WhistleIcon } from '@/components/icons/whistle-icon';
import type { BocceState, LiveSession } from '@/components/live-score-display';
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
    bocceEnd as bocceEndRoute,
    bocceUndoEnd as bocceUndoEndRoute,
    pause as pauseRoute,
    resume as resumeRoute,
    settings as settingsRoute,
} from '@/routes/scoring';

type Side = 'a' | 'b';

function SettingsDialog({
    state,
    disabled,
    onSave,
}: {
    state: BocceState;
    /** Disabled while play is running — same "settings only change during
     * a stoppage" rule as every other sport's dedicated control. */
    disabled: boolean;
    onSave: (data: { target_score: number }) => void;
}) {
    const [open, setOpen] = useState(false);
    const [targetScore, setTargetScore] = useState(
        String(state.target_score),
    );

    const submit = (e: FormEvent) => {
        e.preventDefault();
        onSave({ target_score: Number(targetScore) });
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
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="target-score">
                                Points needed to win the match
                            </Label>
                            <Input
                                id="target-score"
                                type="number"
                                min={1}
                                max={50}
                                value={targetScore}
                                onChange={(e) =>
                                    setTargetScore(e.target.value)
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

/**
 * Awards the points from a completed end to one side — a real end always
 * awards points to exactly one side (the other scores 0), and how many
 * points varies by local rules/ball count, so the operator enters it
 * rather than this app asserting a fixed number.
 */
function AwardEndDialog({
    side,
    label,
    onSubmit,
}: {
    side: Side;
    label: string;
    onSubmit: (points: number) => void;
}) {
    const [open, setOpen] = useState(false);
    const [points, setPoints] = useState('1');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        onSubmit(Number(points));
        setOpen(false);
        setPoints('1');
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    className="h-14 min-w-24 border-emerald-700 bg-emerald-600 text-lg font-bold text-white hover:bg-emerald-700"
                    aria-label={`Award end, ${label}`}
                >
                    Award end
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>
                            Award this end to {label}
                        </DialogTitle>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor={`points-${side}`}>Points</Label>
                            <Input
                                id={`points-${side}`}
                                type="number"
                                min={1}
                                max={20}
                                value={points}
                                onChange={(e) => setPoints(e.target.value)}
                                required
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="submit">Award end</Button>
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
const BOCCE_STATE_DEFAULTS: BocceState = {
    ends: [],
    ends_played: 0,
    target_score: 12,
};

export function BocceGameControl({
    session,
    state: rawState,
}: {
    session: LiveSession;
    state: BocceState;
}) {
    const state: BocceState = { ...BOCCE_STATE_DEFAULTS, ...rawState };
    const running = session.status === 'in_progress';
    const isPaused = session.status === 'paused';
    const matchComplete =
        session.score_a >= state.target_score ||
        session.score_b >= state.target_score;

    const pauseResume = () => {
        router.patch(
            (isPaused ? resumeRoute : pauseRoute)(session.id).url,
            {},
            { preserveScroll: true },
        );
    };

    const awardEnd = (side: Side, points: number) => {
        router.patch(
            bocceEndRoute(session.id).url,
            { side, points },
            { preserveScroll: true },
        );
    };

    const undoEnd = () => {
        router.patch(bocceUndoEndRoute(session.id).url, {}, {
            preserveScroll: true,
        });
    };

    const saveSettings = (data: { target_score: number }) => {
        router.patch(settingsRoute(session.id).url, data, {
            preserveScroll: true,
        });
    };

    return (
        <div className="flex w-full flex-col gap-4 print:hidden">
            <div className="flex flex-col gap-2 rounded-xl border bg-muted/20 p-2">
                <div className="flex flex-wrap items-center justify-center gap-2">
                    <SettingsDialog
                        state={state}
                        disabled={running}
                        onSave={saveSettings}
                    />

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
                    {session.score_a > session.score_b
                        ? session.side_a_label
                        : session.side_b_label}{' '}
                    has reached the {state.target_score} points needed for
                    the match.
                </p>
            )}

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="flex flex-col items-center gap-2 rounded-xl border-2 p-3">
                    <span className="text-sm font-medium text-muted-foreground">
                        {session.side_a_label} — {session.score_a} point
                        {session.score_a === 1 ? '' : 's'}
                    </span>
                    {!matchComplete && (
                        <AwardEndDialog
                            side="a"
                            label={session.side_a_label}
                            onSubmit={(points) => awardEnd('a', points)}
                        />
                    )}
                </div>
                <div className="flex flex-col items-center gap-2 rounded-xl border-2 p-3">
                    <span className="text-sm font-medium text-muted-foreground">
                        {session.side_b_label} — {session.score_b} point
                        {session.score_b === 1 ? '' : 's'}
                    </span>
                    {!matchComplete && (
                        <AwardEndDialog
                            side="b"
                            label={session.side_b_label}
                            onSubmit={(points) => awardEnd('b', points)}
                        />
                    )}
                </div>
            </div>

            <div className="flex justify-center">
                <Button variant="outline" size="sm" onClick={undoEnd}>
                    <Undo2 aria-hidden="true" />
                    Undo last end
                </Button>
            </div>
        </div>
    );
}
