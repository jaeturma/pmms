import { router } from '@inertiajs/react';
import { Settings2, Undo2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { WhistleIcon } from '@/components/icons/whistle-icon';
import type { BilliardState, LiveSession } from '@/components/live-score-display';
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
    billiardRack as billiardRackRoute,
    billiardUndoRack as billiardUndoRackRoute,
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
    state: BilliardState;
    /** Disabled while play is running — same "settings only change during
     * a stoppage" rule as every other sport's dedicated control. */
    disabled: boolean;
    onSave: (data: { racks_to_win: number }) => void;
}) {
    const [open, setOpen] = useState(false);
    const [racksToWin, setRacksToWin] = useState(String(state.racks_to_win));

    const submit = (e: FormEvent) => {
        e.preventDefault();
        onSave({ racks_to_win: Number(racksToWin) });
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
                            <Label htmlFor="racks-to-win">
                                Racks needed to win the match
                            </Label>
                            <Input
                                id="racks-to-win"
                                type="number"
                                min={1}
                                max={15}
                                value={racksToWin}
                                onChange={(e) =>
                                    setRacksToWin(e.target.value)
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
const BILLIARD_STATE_DEFAULTS: BilliardState = {
    racks: [],
    racks_won_a: 0,
    racks_won_b: 0,
    racks_to_win: 5,
};

export function BilliardGameControl({
    session,
    state: rawState,
}: {
    session: LiveSession;
    state: BilliardState;
}) {
    const state: BilliardState = { ...BILLIARD_STATE_DEFAULTS, ...rawState };
    const running = session.status === 'in_progress';
    const isPaused = session.status === 'paused';
    const matchComplete =
        state.racks_won_a >= state.racks_to_win ||
        state.racks_won_b >= state.racks_to_win;

    const pauseResume = () => {
        router.patch(
            (isPaused ? resumeRoute : pauseRoute)(session.id).url,
            {},
            { preserveScroll: true },
        );
    };

    const winRack = (side: Side) => {
        router.patch(
            billiardRackRoute(session.id).url,
            { side },
            { preserveScroll: true },
        );
    };

    const undoRack = () => {
        router.patch(billiardUndoRackRoute(session.id).url, {}, {
            preserveScroll: true,
        });
    };

    const saveSettings = (data: { racks_to_win: number }) => {
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
                    {state.racks_won_a > state.racks_won_b
                        ? session.side_a_label
                        : session.side_b_label}{' '}
                    has won the {state.racks_to_win} racks needed for the
                    match.
                </p>
            )}

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="flex flex-col items-center gap-2 rounded-xl border-2 p-3">
                    <span className="text-sm font-medium text-muted-foreground">
                        {session.side_a_label} — {state.racks_won_a} rack
                        {state.racks_won_a === 1 ? '' : 's'}
                    </span>
                    <Button
                        className="h-14 min-w-24 border-emerald-700 bg-emerald-600 text-lg font-bold text-white hover:bg-emerald-700"
                        aria-label={`Win rack, ${session.side_a_label}`}
                        onClick={() => winRack('a')}
                        disabled={matchComplete}
                    >
                        Win rack
                    </Button>
                </div>
                <div className="flex flex-col items-center gap-2 rounded-xl border-2 p-3">
                    <span className="text-sm font-medium text-muted-foreground">
                        {session.side_b_label} — {state.racks_won_b} rack
                        {state.racks_won_b === 1 ? '' : 's'}
                    </span>
                    <Button
                        className="h-14 min-w-24 border-emerald-700 bg-emerald-600 text-lg font-bold text-white hover:bg-emerald-700"
                        aria-label={`Win rack, ${session.side_b_label}`}
                        onClick={() => winRack('b')}
                        disabled={matchComplete}
                    >
                        Win rack
                    </Button>
                </div>
            </div>

            <div className="flex justify-center">
                <Button variant="outline" size="sm" onClick={undoRack}>
                    <Undo2 aria-hidden="true" />
                    Undo last rack
                </Button>
            </div>
        </div>
    );
}
