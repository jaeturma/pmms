import { Head, router } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { Pause, Play, Radio, Square } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import type { LiveSession } from '@/components/live-score-display';
import {
    isBasketballState,
    isBoxingState,
    isSoftballState,
    LiveScoreDisplay,
} from '@/components/live-score-display';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { index as matchesIndex } from '@/routes/matches';
import {
    count as countRoute,
    end as endRoute,
    foul as foulRoute,
    inningRun as inningRunRoute,
    pause as pauseRoute,
    period as periodRoute,
    resume as resumeRoute,
    round as roundRoute,
    score as scoreRoute,
    show as pollRoute,
    start as startRoute,
} from '@/routes/scoring';

type Session = LiveSession;

type Props = {
    match: {
        id: number;
        meet: string;
        event: string;
        round_label: string;
        status: string;
        is_scheduled: boolean;
    };
    suggestedLabels: [string | null, string | null];
    suggestedBoardType:
        'generic' | 'basketball' | 'boxing' | 'softball_baseball';
    session: Session | null;
    channel: string;
    canManage: boolean;
};

function CorrectionDialog({
    side,
    label,
    onSubmit,
}: {
    side: 'a' | 'b';
    label: string;
    onSubmit: (delta: number, reason: string) => void;
}) {
    const [open, setOpen] = useState(false);
    const [delta, setDelta] = useState('0');
    const [reason, setReason] = useState('');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        onSubmit(Number(delta), reason);
        setOpen(false);
        setDelta('0');
        setReason('');
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    Correct {label}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Correct {label}'s score</DialogTitle>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor={`delta-${side}`}>
                                Adjustment (use a negative number to subtract)
                            </Label>
                            <Input
                                id={`delta-${side}`}
                                type="number"
                                value={delta}
                                onChange={(e) => setDelta(e.target.value)}
                                required
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor={`reason-${side}`}>Reason</Label>
                            <Input
                                id={`reason-${side}`}
                                value={reason}
                                onChange={(e) => setReason(e.target.value)}
                                required
                                maxLength={500}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="submit">Save correction</Button>
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
    onSubmit,
}: {
    labelA: string;
    labelB: string;
    nextRound: number;
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
                <Button variant="outline" size="sm">
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

function RunDialog({
    side,
    label,
    onSubmit,
}: {
    side: 'a' | 'b';
    label: string;
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
                <Button variant="outline" size="sm">
                    Record run(s)
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

export default function ScoringBoard({
    match,
    suggestedLabels,
    suggestedBoardType,
    session: initialSession,
    channel,
    canManage,
}: Props) {
    const [session, setSession] = useState(initialSession);
    const [syncedSession, setSyncedSession] = useState(initialSession);
    const [fullscreen, setFullscreen] = useState(false);
    const [sideALabel, setSideALabel] = useState(suggestedLabels[0] ?? '');
    const [sideBLabel, setSideBLabel] = useState(suggestedLabels[1] ?? '');
    const [forceGeneric, setForceGeneric] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    // Adjust local state during render when a fresh Inertia prop arrives
    // (e.g. after the operator's own action redirects back) — the
    // React-recommended alternative to syncing props into state via an
    // effect. Between such visits, polling/Echo updates session locally.
    if (initialSession !== syncedSession) {
        setSyncedSession(initialSession);
        setSession(initialSession);
    }

    // Baseline: always poll, so the page is correct even if Reverb never
    // connects. The Echo subscription below just makes updates feel instant
    // when it's available — this page never depends on it.
    useEffect(() => {
        const interval = setInterval(() => {
            fetch(pollRoute(match.id).url, {
                headers: { Accept: 'application/json' },
            })
                .then((response) => response.json())
                .then((data: { session: Session | null }) =>
                    setSession(data.session),
                )
                .catch(() => {
                    // Polling retries on its own next tick — no user action needed.
                });
        }, 5000);

        return () => clearInterval(interval);
    }, [match.id]);

    useEcho<{ session: Session }>(
        channel,
        'score.updated',
        (payload) => setSession(payload.session),
        [channel],
    );

    useEffect(() => {
        const onFullscreenChange = () =>
            setFullscreen(document.fullscreenElement !== null);
        document.addEventListener('fullscreenchange', onFullscreenChange);

        return () =>
            document.removeEventListener(
                'fullscreenchange',
                onFullscreenChange,
            );
    }, []);

    const toggleFullscreen = () => {
        if (document.fullscreenElement) {
            void document.exitFullscreen();
        } else {
            void containerRef.current?.requestFullscreen();
        }
    };

    const startSession = (e: FormEvent) => {
        e.preventDefault();
        router.post(
            startRoute(match.id).url,
            {
                side_a_label: sideALabel,
                side_b_label: sideBLabel,
                ...(forceGeneric ? { board_type: 'generic' } : {}),
            },
            { preserveScroll: true },
        );
    };

    const addPoints = (side: 'a' | 'b', delta: number) => {
        if (session === null) {
            return;
        }

        router.patch(
            scoreRoute(session.id).url,
            { type: 'point', side, delta },
            { preserveScroll: true },
        );
    };

    const correct = (side: 'a' | 'b', delta: number, reason: string) => {
        if (session === null) {
            return;
        }

        router.patch(
            scoreRoute(session.id).url,
            { type: 'correction', side, delta, reason },
            { preserveScroll: true },
        );
    };

    const recordFoul = (side: 'a' | 'b') => {
        if (session === null) {
            return;
        }

        router.patch(
            foulRoute(session.id).url,
            { action: 'add', side },
            { preserveScroll: true },
        );
    };

    const resetFouls = () => {
        if (session === null) {
            return;
        }

        router.patch(
            foulRoute(session.id).url,
            { action: 'reset' },
            { preserveScroll: true },
        );
    };

    const recordRound = (scoreA: number, scoreB: number) => {
        if (session === null) {
            return;
        }

        router.patch(
            roundRoute(session.id).url,
            { score_a: scoreA, score_b: scoreB },
            { preserveScroll: true },
        );
    };

    const recordRun = (side: 'a' | 'b', runs: number) => {
        if (session === null) {
            return;
        }

        router.patch(
            inningRunRoute(session.id).url,
            { side, runs },
            { preserveScroll: true },
        );
    };

    const recordCount = (action: 'out' | 'ball' | 'strike' | 'reset_count') => {
        if (session === null) {
            return;
        }

        router.patch(
            countRoute(session.id).url,
            { action },
            { preserveScroll: true },
        );
    };

    const isManager = canManage;
    const isActive = session !== null && session.status !== 'ended';
    const basketballState =
        session && isBasketballState(session.sport_state)
            ? session.sport_state
            : null;
    const boxingState =
        session && isBoxingState(session.sport_state)
            ? session.sport_state
            : null;
    const softballState =
        session && isSoftballState(session.sport_state)
            ? session.sport_state
            : null;

    return (
        <>
            <Head title={`Live scoring — ${match.event}`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={`Live scoring — ${match.event}`}
                    description={`${match.meet} · ${match.round_label}`}
                    actions={
                        <Button variant="outline" asChild>
                            <a href={matchesIndex().url}>Back to matches</a>
                        </Button>
                    }
                />

                {session === null ? (
                    isManager && match.is_scheduled ? (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Start live scoring
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form
                                    onSubmit={startSession}
                                    className="grid gap-4 sm:grid-cols-2"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="side-a">Side A</Label>
                                        <Input
                                            id="side-a"
                                            value={sideALabel}
                                            onChange={(e) =>
                                                setSideALabel(e.target.value)
                                            }
                                            required
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="side-b">Side B</Label>
                                        <Input
                                            id="side-b"
                                            value={sideBLabel}
                                            onChange={(e) =>
                                                setSideBLabel(e.target.value)
                                            }
                                            required
                                        />
                                    </div>
                                    {suggestedBoardType !== 'generic' && (
                                        <div className="flex items-center gap-2 sm:col-span-2">
                                            <Checkbox
                                                id="force-generic"
                                                checked={forceGeneric}
                                                onCheckedChange={(checked) =>
                                                    setForceGeneric(
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            <Label
                                                htmlFor="force-generic"
                                                className="font-normal"
                                            >
                                                Use the generic scoreboard
                                                instead of the automatic{' '}
                                                {suggestedBoardType} board (e.g.
                                                for an exhibition or
                                                non-standard match)
                                            </Label>
                                        </div>
                                    )}
                                    <Button
                                        type="submit"
                                        className="sm:col-span-2"
                                    >
                                        Start scoring
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>
                    ) : (
                        <EmptyState
                            icon={Radio}
                            title="No live session"
                            description={
                                match.is_scheduled
                                    ? 'Live scoring has not started for this match yet.'
                                    : 'This match is not scheduled, so live scoring is not available.'
                            }
                        />
                    )
                ) : (
                    <div
                        ref={containerRef}
                        className={
                            fullscreen
                                ? 'flex flex-1 flex-col items-center justify-center gap-8 bg-background p-8'
                                : 'flex flex-col gap-4'
                        }
                    >
                        <LiveScoreDisplay
                            session={session}
                            fullscreen={fullscreen}
                            onToggleFullscreen={toggleFullscreen}
                        />

                        {isManager && isActive && (
                            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 print:hidden">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="flex flex-col items-center gap-2">
                                        <div className="flex flex-wrap justify-center gap-2">
                                            {[1, 2, 3].map((points) => (
                                                <Button
                                                    key={points}
                                                    variant="outline"
                                                    aria-label={`Add ${points} point${points > 1 ? 's' : ''}, ${session.side_a_label}`}
                                                    onClick={() =>
                                                        addPoints('a', points)
                                                    }
                                                >
                                                    +{points}
                                                </Button>
                                            ))}
                                        </div>
                                        {basketballState && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                aria-label={`Add team foul, ${session.side_a_label}`}
                                                onClick={() => recordFoul('a')}
                                            >
                                                Foul ({basketballState.fouls_a})
                                            </Button>
                                        )}
                                        {softballState && (
                                            <RunDialog
                                                side="a"
                                                label={session.side_a_label}
                                                onSubmit={(runs) =>
                                                    recordRun('a', runs)
                                                }
                                            />
                                        )}
                                        <CorrectionDialog
                                            side="a"
                                            label={session.side_a_label}
                                            onSubmit={(delta, reason) =>
                                                correct('a', delta, reason)
                                            }
                                        />
                                    </div>
                                    <div className="flex flex-col items-center gap-2">
                                        <div className="flex flex-wrap justify-center gap-2">
                                            {[1, 2, 3].map((points) => (
                                                <Button
                                                    key={points}
                                                    variant="outline"
                                                    aria-label={`Add ${points} point${points > 1 ? 's' : ''}, ${session.side_b_label}`}
                                                    onClick={() =>
                                                        addPoints('b', points)
                                                    }
                                                >
                                                    +{points}
                                                </Button>
                                            ))}
                                        </div>
                                        {basketballState && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                aria-label={`Add team foul, ${session.side_b_label}`}
                                                onClick={() => recordFoul('b')}
                                            >
                                                Foul ({basketballState.fouls_b})
                                            </Button>
                                        )}
                                        {softballState && (
                                            <RunDialog
                                                side="b"
                                                label={session.side_b_label}
                                                onSubmit={(runs) =>
                                                    recordRun('b', runs)
                                                }
                                            />
                                        )}
                                        <CorrectionDialog
                                            side="b"
                                            label={session.side_b_label}
                                            onSubmit={(delta, reason) =>
                                                correct('b', delta, reason)
                                            }
                                        />
                                    </div>
                                </div>

                                {basketballState && (
                                    <div className="flex justify-center">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={resetFouls}
                                        >
                                            Reset fouls
                                        </Button>
                                    </div>
                                )}

                                {boxingState && (
                                    <div className="flex justify-center">
                                        <RoundScoreDialog
                                            labelA={session.side_a_label}
                                            labelB={session.side_b_label}
                                            nextRound={
                                                boxingState.rounds.length + 1
                                            }
                                            onSubmit={recordRound}
                                        />
                                    </div>
                                )}

                                {softballState && (
                                    <div className="flex flex-wrap justify-center gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => recordCount('out')}
                                        >
                                            +1 Out
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => recordCount('ball')}
                                        >
                                            +1 Ball
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                recordCount('strike')
                                            }
                                        >
                                            +1 Strike
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                recordCount('reset_count')
                                            }
                                        >
                                            Reset count
                                        </Button>
                                    </div>
                                )}

                                <PeriodForm session={session} />

                                <div className="flex justify-center gap-2">
                                    {session.status === 'paused' ? (
                                        <Button
                                            variant="outline"
                                            onClick={() =>
                                                router.patch(
                                                    resumeRoute(session.id).url,
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <Play aria-hidden="true" />
                                            Resume
                                        </Button>
                                    ) : (
                                        <Button
                                            variant="outline"
                                            onClick={() =>
                                                router.patch(
                                                    pauseRoute(session.id).url,
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <Pause aria-hidden="true" />
                                            Pause
                                        </Button>
                                    )}
                                    <ConfirmDialog
                                        trigger={
                                            <Button variant="destructive">
                                                <Square aria-hidden="true" />
                                                End
                                            </Button>
                                        }
                                        title="End live scoring?"
                                        description="This ends the live session only. You'll still need to encode the official result separately."
                                        confirmLabel="End"
                                        destructive
                                        onConfirm={() =>
                                            router.patch(
                                                endRoute(session.id).url,
                                                {},
                                                { preserveScroll: true },
                                            )
                                        }
                                    />
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

function PeriodForm({ session }: { session: Session }) {
    const [periodLabel, setPeriodLabel] = useState(session.period_label ?? '');
    const [statusNote, setStatusNote] = useState(session.status_note ?? '');
    const [synced, setSynced] = useState([
        session.period_label,
        session.status_note,
    ]);

    // Same render-time adjustment as the parent component — reflect an
    // external change (another operator, or a poll/Echo update) without
    // fighting local edits between syncs.
    if (
        session.period_label !== synced[0] ||
        session.status_note !== synced[1]
    ) {
        setSynced([session.period_label, session.status_note]);
        setPeriodLabel(session.period_label ?? '');
        setStatusNote(session.status_note ?? '');
    }

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.patch(
            periodRoute(session.id).url,
            { period_label: periodLabel, status_note: statusNote },
            { preserveScroll: true },
        );
    };

    return (
        <form onSubmit={submit} className="flex flex-wrap items-end gap-2">
            <div className="grid gap-2">
                <Label htmlFor="period-label">Period / round</Label>
                <Input
                    id="period-label"
                    value={periodLabel}
                    onChange={(e) => setPeriodLabel(e.target.value)}
                    maxLength={100}
                    className="w-40"
                />
            </div>
            <div className="grid flex-1 gap-2">
                <Label htmlFor="status-note">Status note</Label>
                <Input
                    id="status-note"
                    value={statusNote}
                    onChange={(e) => setStatusNote(e.target.value)}
                    maxLength={500}
                />
            </div>
            <Button type="submit" variant="outline">
                Update
            </Button>
        </form>
    );
}

ScoringBoard.layout = {
    breadcrumbs: [
        {
            title: 'Matches',
            href: matchesIndex(),
        },
    ],
};
