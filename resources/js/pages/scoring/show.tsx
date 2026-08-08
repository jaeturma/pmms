import { Head, router } from '@inertiajs/react';
import { configureEcho, useEcho } from '@laravel/echo-react';
import { Pause, Play, Radio, Square } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { BasketballGameControl } from '@/components/basketball-game-control';
import { BoxingGameControl } from '@/components/boxing-game-control';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import { FootballFutsalGameControl } from '@/components/football-futsal-game-control';
import { LiveBadge } from '@/components/live-badge';
import type { LiveSession, Participant } from '@/components/live-score-display';
import {
    CorrectionDialog,
    isBasketballState,
    isBoxingState,
    isFootballState,
    isRacketGamesState,
    isRallySetsState,
    isSoftballState,
    isWrestlingState,
    LiveScoreDisplay,
    PlayByPlayList,
} from '@/components/live-score-display';
import { PageHeader } from '@/components/page-header';
import { RacketGamesGameControl } from '@/components/racket-games-game-control';
import { SoftballBaseballGameControl } from '@/components/softball-baseball-game-control';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { VolleyballSepakTakrawGameControl } from '@/components/volleyball-sepak-takraw-game-control';
import { WrestlingGameControl } from '@/components/wrestling-game-control';
import { index as matchesIndex } from '@/routes/matches';
import {
    end as endRoute,
    pause as pauseRoute,
    period as periodRoute,
    resume as resumeRoute,
    score as scoreRoute,
    show as pollRoute,
    start as startRoute,
} from '@/routes/scoring';

/** Configuring Echo here rather than in `app.tsx` keeps pusher-js out of
 * every other page's bundle — `useEcho` below is the only call site of
 * Echo's realtime hooks app-wide, so there's no reason to pay for it
 * globally. Runs once, the first time this module is imported (Inertia
 * only resolves a page's module when it's actually visited). */
configureEcho({
    broadcaster: 'reverb',
});

type Session = LiveSession;

type Props = {
    match: {
        id: number;
        meet: string;
        event: string;
        sport: string;
        category: string;
        round_label: string;
        venue: string | null;
        scheduled_date: string | null;
        status: string;
        is_scheduled: boolean;
    };
    suggestedLabels: [string | null, string | null];
    suggestedBoardType:
        | 'generic'
        | 'basketball'
        | 'boxing'
        | 'softball_baseball'
        | 'volleyball_sepak_takraw'
        | 'football_futsal'
        | 'racket_games'
        | 'combat_rounds'
        | 'wrestling';
    session: Session | null;
    channel: string;
    canManage: boolean;
    participants: [Participant | null, Participant | null];
};

export default function ScoringBoard({
    match,
    suggestedLabels,
    suggestedBoardType,
    session: initialSession,
    channel,
    canManage,
    participants,
}: Props) {
    const [session, setSession] = useState(initialSession);
    const [syncedSession, setSyncedSession] = useState(initialSession);
    const [pollFailures, setPollFailures] = useState(0);
    const [lastUpdatedAt, setLastUpdatedAt] = useState(() => Date.now());
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
                .then((data: { session: Session | null }) => {
                    setSession(data.session);
                    setPollFailures(0);
                    setLastUpdatedAt(Date.now());
                })
                .catch(() => {
                    // Polling retries on its own next tick — no user
                    // action needed, but the display flags it after a
                    // couple of misses (WP-08-10).
                    setPollFailures((n) => n + 1);
                });
        }, 5000);

        return () => clearInterval(interval);
    }, [match.id]);

    useEcho<{ session: Session }>(
        channel,
        'score.updated',
        (payload) => {
            setSession(payload.session);
            setPollFailures(0);
            setLastUpdatedAt(Date.now());
        },
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
    const rallySetsState =
        session && isRallySetsState(session.sport_state)
            ? session.sport_state
            : null;
    const footballState =
        session && isFootballState(session.sport_state)
            ? session.sport_state
            : null;
    const racketGamesState =
        session && isRacketGamesState(session.sport_state)
            ? session.sport_state
            : null;
    const wrestlingState =
        session && isWrestlingState(session.sport_state)
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

                <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted-foreground">
                    <span className="font-medium text-foreground">
                        {match.sport}
                    </span>
                    <span aria-hidden="true">›</span>
                    <span>{match.category}</span>
                    <span aria-hidden="true">›</span>
                    <span>{match.round_label}</span>
                    {isActive && (
                        <LiveBadge label="Live now" className="ml-1" />
                    )}
                    {(match.scheduled_date || match.venue) && (
                        <span className="w-full text-xs">
                            {[match.scheduled_date, match.venue]
                                .filter(Boolean)
                                .join(' · ')}
                        </span>
                    )}
                </div>

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
                            disconnected={pollFailures >= 2}
                            lastUpdatedAt={lastUpdatedAt}
                            participants={participants}
                            hidePlayByPlay={isManager && isActive}
                        />

                        {isManager && isActive && basketballState && (
                            <BasketballGameControl
                                session={session}
                                state={basketballState}
                            />
                        )}

                        {isManager && isActive && boxingState && (
                            <BoxingGameControl
                                session={session}
                                state={boxingState}
                            />
                        )}

                        {isManager && isActive && softballState && (
                            <SoftballBaseballGameControl
                                session={session}
                                state={softballState}
                            />
                        )}

                        {isManager && isActive && rallySetsState && (
                            <VolleyballSepakTakrawGameControl
                                session={session}
                                state={rallySetsState}
                            />
                        )}

                        {isManager && isActive && footballState && (
                            <FootballFutsalGameControl
                                session={session}
                                state={footballState}
                            />
                        )}

                        {isManager && isActive && racketGamesState && (
                            <RacketGamesGameControl
                                session={session}
                                state={racketGamesState}
                            />
                        )}

                        {isManager && isActive && wrestlingState && (
                            <WrestlingGameControl
                                session={session}
                                state={wrestlingState}
                            />
                        )}

                        {isManager &&
                            isActive &&
                            !basketballState &&
                            !boxingState &&
                            !softballState &&
                            !rallySetsState &&
                            !footballState &&
                            !racketGamesState &&
                            !wrestlingState && (
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
                                                            addPoints(
                                                                'a',
                                                                points,
                                                            )
                                                        }
                                                    >
                                                        +{points}
                                                    </Button>
                                                ))}
                                            </div>
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
                                                            addPoints(
                                                                'b',
                                                                points,
                                                            )
                                                        }
                                                    >
                                                        +{points}
                                                    </Button>
                                                ))}
                                            </div>
                                            <CorrectionDialog
                                                side="b"
                                                label={session.side_b_label}
                                                onSubmit={(delta, reason) =>
                                                    correct('b', delta, reason)
                                                }
                                            />
                                        </div>
                                    </div>

                                    <PeriodForm session={session} />

                                    <div className="flex justify-center gap-2">
                                        {session.status === 'paused' ? (
                                            <Button
                                                variant="outline"
                                                onClick={() =>
                                                    router.patch(
                                                        resumeRoute(session.id)
                                                            .url,
                                                        {},
                                                        {
                                                            preserveScroll: true,
                                                        },
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
                                                        pauseRoute(session.id)
                                                            .url,
                                                        {},
                                                        {
                                                            preserveScroll: true,
                                                        },
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

                        {/* Every sport-specific control above has its own
                            Whistle (pause/resume) but none has an End
                            control of its own — this is the one shared
                            spot every board type ends its session from. */}
                        {isManager &&
                            isActive &&
                            (basketballState ||
                                boxingState ||
                                softballState ||
                                rallySetsState ||
                                footballState ||
                                racketGamesState ||
                                wrestlingState) && (
                                <div className="mx-auto flex w-full max-w-2xl justify-center print:hidden">
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
                            )}

                        {isManager && isActive && (
                            <div className="mx-auto w-full max-w-2xl print:hidden">
                                <PlayByPlayList
                                    playByPlay={session.playByPlay}
                                />
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
