import { Head, router } from '@inertiajs/react';
import { configureEcho, useEcho } from '@laravel/echo-react';
import { Pause, Play, Radio, Square, Users, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { BasketballGameControl } from '@/components/basketball-game-control';
import { BilliardGameControl } from '@/components/billiard-game-control';
import { BocceGameControl } from '@/components/bocce-game-control';
import { BoxingGameControl } from '@/components/boxing-game-control';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import { FootballFutsalGameControl } from '@/components/football-futsal-game-control';
import { GoalBallGameControl } from '@/components/goal-ball-game-control';
import { LiveBadge } from '@/components/live-badge';
import type { LiveSession, Participant, RosterPlayer } from '@/components/live-score-display';
import {
    isBasketballState,
    isBilliardState,
    isBocceState,
    isBoxingState,
    isFootballState,
    isGoalBallState,
    isRacketGamesState,
    isRallySetsState,
    isSoftballState,
    isTennisState,
    isWrestlingState,
    LiveScoreDisplay,
    PlayByPlayList,
} from '@/components/live-score-display';
import { PageHeader } from '@/components/page-header';
import { RacketGamesGameControl } from '@/components/racket-games-game-control';
import { SoftballBaseballGameControl } from '@/components/softball-baseball-game-control';
import { TennisGameControl } from '@/components/tennis-game-control';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
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
import { VolleyballSepakTakrawGameControl } from '@/components/volleyball-sepak-takraw-game-control';
import { WrestlingGameControl } from '@/components/wrestling-game-control';
import { cn } from '@/lib/utils';
import {
    destroy as rosterDestroyRoute,
    show as rosterShowRoute,
    store as rosterStoreRoute,
} from '@/routes/match-roster';
import { index as matchesIndex } from '@/routes/matches';
import {
    end as endRoute,
    participants as participantsRoute,
    pause as pauseRoute,
    period as periodRoute,
    resume as resumeRoute,
    score as scoreRoute,
    show as pollRoute,
    start as startRoute,
} from '@/routes/scoring';
import { destroy as removeEventRoute } from '@/routes/scoring/events';

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
        scoreboard_mode: string | null;
        is_team_event: boolean;
        viewer_url: string;
    };
    suggestedLabels: [string | null, string | null];
    delegationOptions: Array<{ id: number; label: string }>;
    meetDelegationOptions: Array<{ id: number; label: string }>;
    athleteOptions: Array<{ athlete_id: number; label: string; unlinked: boolean }>;
    suggestedBoardType:
        | 'generic'
        | 'basketball'
        | 'boxing'
        | 'softball_baseball'
        | 'volleyball_sepak_takraw'
        | 'football_futsal'
        | 'racket_games'
        | 'combat_rounds'
        | 'wrestling'
        | 'tennis'
        | 'goal_ball'
        | 'billiard'
        | 'bocce';
    session: Session | null;
    channel: string;
    canManage: boolean;
    canOverrideParticipants: boolean;
    participants: [Participant | null, Participant | null];
};

type BoardType = Props['suggestedBoardType'];

type SettingField =
    | {
          key: string;
          label: string;
          kind: 'number';
          min: number;
          max: number;
          defaultValue: number;
      }
    | { key: string; label: string; kind: 'color'; defaultValue: string }
    | {
          key: string;
          label: string;
          kind: 'select';
          options: number[];
          defaultValue: number;
      };

/**
 * Mirrors `ScoringSessionController::sportSettingsRules()` field-for-field
 * so a pre-start choice here validates cleanly against the same backend
 * rules `settings()` already enforces post-start. A few defaults are
 * sport-name-dependent (volleyball vs. sepak takraw, table tennis vs.
 * badminton, football vs. futsal) — mirrors
 * `initialRallySetsState()`/`initialRacketGamesState()`'s own branch
 * exactly rather than picking one static default for both sports sharing
 * a board type.
 */
function settingsFieldsFor(
    boardType: BoardType,
    sportName: string,
): SettingField[] | null {
    const sport = sportName.toLowerCase();

    switch (boardType) {
        case 'basketball':
            return [
                {
                    key: 'minutes_per_period',
                    label: 'Minutes per period',
                    kind: 'number',
                    min: 1,
                    max: 20,
                    defaultValue: 10,
                },
                {
                    key: 'shot_clock_duration',
                    label: 'Shot clock (seconds)',
                    kind: 'number',
                    min: 5,
                    max: 60,
                    defaultValue: 24,
                },
                {
                    key: 'team_color_a',
                    label: 'Side A color',
                    kind: 'color',
                    defaultValue: '#dc2626',
                },
                {
                    key: 'team_color_b',
                    label: 'Side B color',
                    kind: 'color',
                    defaultValue: '#2563eb',
                },
                {
                    key: 'quarters',
                    label: 'Quarters',
                    kind: 'select',
                    options: [2, 4],
                    defaultValue: 4,
                },
            ];
        case 'boxing':
        case 'combat_rounds':
            return [
                {
                    key: 'round_duration_seconds',
                    label: 'Round duration (seconds)',
                    kind: 'number',
                    min: 30,
                    max: 600,
                    defaultValue: 120,
                },
                {
                    key: 'rest_duration_seconds',
                    label: 'Rest duration (seconds)',
                    kind: 'number',
                    min: 15,
                    max: 300,
                    defaultValue: 60,
                },
                {
                    key: 'total_rounds',
                    label: 'Total rounds',
                    kind: 'number',
                    min: 1,
                    max: 12,
                    defaultValue: 3,
                },
            ];
        case 'softball_baseball':
            return [
                {
                    key: 'team_color_a',
                    label: 'Side A color',
                    kind: 'color',
                    defaultValue: '#dc2626',
                },
                {
                    key: 'team_color_b',
                    label: 'Side B color',
                    kind: 'color',
                    defaultValue: '#2563eb',
                },
                {
                    key: 'innings_scheduled',
                    label: 'Innings',
                    kind: 'number',
                    min: 3,
                    max: 15,
                    defaultValue: 7,
                },
            ];
        case 'volleyball_sepak_takraw': {
            const isSepakTakraw = sport === 'sepak takraw';

            return [
                {
                    key: 'set_target_points',
                    label: 'Set target points',
                    kind: 'number',
                    min: 5,
                    max: 50,
                    defaultValue: isSepakTakraw ? 21 : 25,
                },
                {
                    key: 'deciding_set_target_points',
                    label: 'Deciding set target points',
                    kind: 'number',
                    min: 5,
                    max: 50,
                    defaultValue: isSepakTakraw ? 21 : 15,
                },
                {
                    key: 'sets_to_win',
                    label: 'Sets to win',
                    kind: 'number',
                    min: 1,
                    max: 5,
                    defaultValue: isSepakTakraw ? 2 : 3,
                },
            ];
        }
        case 'football_futsal':
            return [
                {
                    key: 'minutes_per_half',
                    label: 'Minutes per half',
                    kind: 'number',
                    min: 5,
                    max: 60,
                    defaultValue: sport === 'futsal' ? 20 : 45,
                },
            ];
        case 'racket_games': {
            const isBadminton = sport === 'badminton';

            return [
                {
                    key: 'game_target_points',
                    label: 'Target points per game',
                    kind: 'number',
                    min: 5,
                    max: 50,
                    defaultValue: isBadminton ? 21 : 11,
                },
                {
                    key: 'hard_cap_points',
                    label: 'Hard cap points (0 = none)',
                    kind: 'number',
                    min: 0,
                    max: 60,
                    defaultValue: isBadminton ? 30 : 0,
                },
                {
                    key: 'games_to_win',
                    label: 'Games to win',
                    kind: 'number',
                    min: 1,
                    max: 5,
                    defaultValue: isBadminton ? 2 : 3,
                },
            ];
        }
        case 'wrestling':
            return [
                {
                    key: 'period_duration_seconds',
                    label: 'Period duration (seconds)',
                    kind: 'number',
                    min: 30,
                    max: 600,
                    defaultValue: 180,
                },
                {
                    key: 'rest_duration_seconds',
                    label: 'Rest duration (seconds)',
                    kind: 'number',
                    min: 10,
                    max: 300,
                    defaultValue: 30,
                },
                {
                    key: 'total_periods',
                    label: 'Total periods',
                    kind: 'number',
                    min: 1,
                    max: 5,
                    defaultValue: 2,
                },
            ];
        case 'tennis':
            return [
                {
                    key: 'sets_to_win',
                    label: 'Sets to win',
                    kind: 'select',
                    options: [2, 3],
                    defaultValue: 2,
                },
            ];
        case 'goal_ball':
            return [
                {
                    key: 'minutes_per_half',
                    label: 'Minutes per half',
                    kind: 'number',
                    min: 3,
                    max: 20,
                    defaultValue: 6,
                },
            ];
        case 'billiard':
            return [
                {
                    key: 'racks_to_win',
                    label: 'Racks to win',
                    kind: 'number',
                    min: 1,
                    max: 15,
                    defaultValue: 5,
                },
            ];
        case 'bocce':
            return [
                {
                    key: 'target_score',
                    label: 'Target score',
                    kind: 'number',
                    min: 1,
                    max: 50,
                    defaultValue: 12,
                },
            ];
        default:
            return null;
    }
}

function GameSettingsFields({
    fields,
    values,
    onChange,
}: {
    fields: SettingField[];
    values: Record<string, string | number>;
    onChange: (key: string, value: string | number) => void;
}) {
    return (
        <div className="grid gap-4 sm:grid-cols-2">
            {fields.map((field) => (
                <div className="grid gap-2" key={field.key}>
                    <Label htmlFor={`setting-${field.key}`}>
                        {field.label}
                    </Label>
                    {field.kind === 'select' ? (
                        <Select
                            value={String(values[field.key])}
                            onValueChange={(value) =>
                                onChange(field.key, Number(value))
                            }
                        >
                            <SelectTrigger id={`setting-${field.key}`}>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {field.options.map((option) => (
                                    <SelectItem
                                        key={option}
                                        value={String(option)}
                                    >
                                        {option}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    ) : (
                        <Input
                            id={`setting-${field.key}`}
                            type={field.kind === 'color' ? 'color' : 'number'}
                            min={field.kind === 'number' ? field.min : undefined}
                            max={field.kind === 'number' ? field.max : undefined}
                            value={values[field.key]}
                            className={
                                field.kind === 'color' ? 'h-10 w-20 p-1' : ''
                            }
                            onChange={(e) =>
                                onChange(
                                    field.key,
                                    field.kind === 'color'
                                        ? e.target.value
                                        : Number(e.target.value),
                                )
                            }
                        />
                    )}
                </div>
            ))}
        </div>
    );
}

type PreStartRosterData = {
    roster: { a: RosterPlayer[]; b: RosterPlayer[] };
    eligibleAthletes: {
        a: { id: number; label: string }[];
        b: { id: number; label: string }[];
    };
};

/**
 * Athlete curation before a session exists — the same `match-roster.*`
 * endpoints `BasketballGameControl`'s in-game "Substitute" modal uses
 * (roster persists independently of any session, so these already work
 * pre-start with zero backend changes), just without the on-court/bench
 * split that only makes sense once a game is actually running. "Manual"
 * here means picking any still-eligible registered entry from the
 * dropdown, not free text — this app never records an athlete without a
 * real Entry row behind them.
 */
function PreStartRosterManager({
    matchId,
    sideALabel,
    sideBLabel,
}: {
    matchId: number;
    sideALabel: string;
    sideBLabel: string;
}) {
    const [open, setOpen] = useState(false);
    const [data, setData] = useState<PreStartRosterData | null>(null);
    const [entryId, setEntryId] = useState<{ a: string; b: string }>({
        a: '',
        b: '',
    });

    const load = () => {
        fetch(rosterShowRoute(matchId).url, {
            headers: { Accept: 'application/json' },
        })
            .then((response) => response.json())
            .then((json: PreStartRosterData) => setData(json));
    };

    useEffect(() => {
        if (open) {
            load();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    const addPlayer = (side: 'a' | 'b') => {
        if (entryId[side] === '') {
            return;
        }

        router.post(
            rosterStoreRoute(matchId).url,
            { entry_id: Number(entryId[side]), side },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setEntryId((current) => ({ ...current, [side]: '' }));
                    load();
                },
            },
        );
    };

    const removePlayer = (rosterPlayerId: number) => {
        router.delete(rosterDestroyRoute(rosterPlayerId).url, {
            preserveScroll: true,
            onSuccess: load,
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button type="button" variant="outline" size="sm">
                    <Users aria-hidden="true" />
                    Manage roster
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Team roster</DialogTitle>
                </DialogHeader>
                {open && data === null ? (
                    <p className="py-6 text-center text-sm text-muted-foreground">
                        Loading roster…
                    </p>
                ) : (
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        {(['a', 'b'] as const).map((side) => (
                            <div key={side}>
                                <p className="mb-1 text-xs font-medium text-muted-foreground uppercase">
                                    {side === 'a' ? sideALabel : sideBLabel} (
                                    {data?.roster[side].length ?? 0})
                                </p>
                                <ul className="divide-y rounded-lg border text-sm">
                                    {(data?.roster[side].length ?? 0) ===
                                        0 && (
                                        <li className="px-3 py-3 text-muted-foreground">
                                            No players yet.
                                        </li>
                                    )}
                                    {data?.roster[side].map((player) => (
                                        <li
                                            key={player.id}
                                            className="flex items-center justify-between gap-2 px-3 py-2.5"
                                        >
                                            <span className="min-w-0 truncate">
                                                {player.jersey_number && (
                                                    <span className="text-muted-foreground">
                                                        #{player.jersey_number}{' '}
                                                    </span>
                                                )}
                                                {player.name}
                                            </span>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-9"
                                                aria-label={`Remove ${player.name}`}
                                                onClick={() =>
                                                    removePlayer(player.id)
                                                }
                                            >
                                                <X
                                                    aria-hidden="true"
                                                    className="size-4"
                                                />
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                                <div className="mt-2 flex gap-2">
                                    <Select
                                        value={entryId[side]}
                                        onValueChange={(value) =>
                                            setEntryId((current) => ({
                                                ...current,
                                                [side]: value,
                                            }))
                                        }
                                    >
                                        <SelectTrigger className="flex-1">
                                            <SelectValue placeholder="Add registered athlete" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {data?.eligibleAthletes[side].map(
                                                (athlete) => (
                                                    <SelectItem
                                                        key={athlete.id}
                                                        value={String(
                                                            athlete.id,
                                                        )}
                                                    >
                                                        {athlete.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <Button
                                        type="button"
                                        disabled={entryId[side] === ''}
                                        onClick={() => addPlayer(side)}
                                    >
                                        Add
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}

export default function ScoringBoard({
    match,
    suggestedLabels,
    delegationOptions,
    meetDelegationOptions,
    athleteOptions,
    suggestedBoardType,
    session: initialSession,
    channel,
    canManage,
    canOverrideParticipants,
    participants,
}: Props) {
    const [session, setSession] = useState(initialSession);
    const [syncedSession, setSyncedSession] = useState(initialSession);
    const [pollFailures, setPollFailures] = useState(0);
    const [lastUpdatedAt, setLastUpdatedAt] = useState(() => Date.now());
    const [fullscreen, setFullscreen] = useState(false);
    const [sideALabel, setSideALabel] = useState(suggestedLabels[0] ?? '');
    const [sideBLabel, setSideBLabel] = useState(suggestedLabels[1] ?? '');
    const [delegationAId, setDelegationAId] = useState('');
    const [delegationBId, setDelegationBId] = useState('');
    const [forceGeneric, setForceGeneric] = useState(false);
    const [overrideParticipants, setOverrideParticipants] = useState(false);
    const [overrideReason, setOverrideReason] = useState('');
    const [gameType, setGameType] = useState(match.scoreboard_mode ?? 'test');
    const [showSettings, setShowSettings] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);
    const canPickCompetingTeams =
        match.is_team_event &&
        suggestedLabels[0] === null &&
        delegationOptions.length > 0 &&
        !overrideParticipants;
    const hasGeneratedParticipants = suggestedLabels[0] !== null;
    const settingsFields = forceGeneric
        ? null
        : settingsFieldsFor(suggestedBoardType, match.sport);
    const [settingsValues, setSettingsValues] = useState<
        Record<string, string | number>
    >(() =>
        Object.fromEntries(
            (settingsFieldsFor(suggestedBoardType, match.sport) ?? []).map(
                (field) => [field.key, field.defaultValue],
            ),
        ),
    );

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
                scoreboard_mode: gameType,
                side_a_label: sideALabel,
                side_b_label: sideBLabel,
                ...(forceGeneric ? { board_type: 'generic' } : {}),
                ...(overrideParticipants
                    ? {
                          override_participants: true,
                          override_reason: overrideReason,
                      }
                    : {}),
                ...(suggestedLabels[0] === null && !canPickCompetingTeams
                    ? { manual_setup: true }
                    : {}),
                ...(canPickCompetingTeams
                    ? {
                          delegation_a_id: delegationAId,
                          delegation_b_id: delegationBId,
                      }
                    : {}),
                ...(settingsFields !== null
                    ? { settings: settingsValues }
                    : {}),
            },
            { preserveScroll: true },
        );
    };

    const selectCompetingDelegation = (side: 'a' | 'b', delegationId: string) => {
        const label =
            delegationOptions.find(
                (option) => String(option.id) === delegationId,
            )?.label ?? '';

        if (side === 'a') {
            setDelegationAId(delegationId);
            setSideALabel(label);
        } else {
            setDelegationBId(delegationId);
            setSideBLabel(label);
        }
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

    const removePlay = (eventId: number) => {
        if (session === null) {
            return;
        }

        const reason = window
            .prompt('Why are you reversing this point or foul?')
            ?.trim();

        if (!reason) {
            return;
        }

        router.delete(
            removeEventRoute({ session: session.id, event: eventId }).url,
            { data: { reason }, preserveScroll: true },
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
    const tennisState =
        session && isTennisState(session.sport_state)
            ? session.sport_state
            : null;
    const goalBallState =
        session && isGoalBallState(session.sport_state)
            ? session.sport_state
            : null;
    const billiardState =
        session && isBilliardState(session.sport_state)
            ? session.sport_state
            : null;
    const bocceState =
        session && isBocceState(session.sport_state)
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

                {match.scoreboard_mode && (
                    <div className="flex flex-wrap items-center gap-3 rounded-xl border bg-muted/20 p-4">
                        <Label htmlFor="game-type">Viewer game label</Label>
                        <select
                            id="game-type"
                            value={gameType}
                            onChange={(e) => setGameType(e.target.value)}
                            className="h-10 rounded-md border bg-background px-3"
                        >
                            <option value="test">Test</option>
                            <option value="finals">Finals Game</option>
                            <option value="championship">
                                Championship Game
                            </option>
                        </select>
                        {isManager && session && (
                            <Button
                                variant="outline"
                                onClick={() =>
                                    router.post(
                                        `/matches/${match.id}/scoreboard/reset`,
                                        { scoreboard_mode: gameType },
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Reset / new run
                            </Button>
                        )}
                        <Button variant="outline" asChild>
                            <a
                                href={match.viewer_url}
                                target="_blank"
                                rel="noreferrer"
                            >
                                Viewer display
                            </a>
                        </Button>
                        <p className="w-full text-sm text-muted-foreground">
                            Reset starts from zero and keeps the previous run in
                            history. Results are submitted separately.
                        </p>
                    </div>
                )}
                {!match.scoreboard_mode &&
                    isManager &&
                    session !== null &&
                    !isActive && (
                        <div className="flex flex-wrap items-center gap-3 rounded-xl border bg-muted/20 p-4">
                            <Button
                                variant="outline"
                                onClick={() => {
                                    if (
                                        window.confirm(
                                            'Restart this scoreboard from zero? The ended run is kept in history, but any unsubmitted draft result from it will be discarded.',
                                        )
                                    ) {
                                        router.post(
                                            `/matches/${match.id}/scoreboard/reset`,
                                            {},
                                            { preserveScroll: true },
                                        );
                                    }
                                }}
                            >
                                Restart / new run
                            </Button>
                            <p className="text-sm text-muted-foreground">
                                Starts a brand-new session at zero — the
                                ended run stays in history, but its draft
                                result (if not yet submitted) is discarded.
                            </p>
                        </div>
                    )}
                {session === null ? (
                    isManager &&
                    (match.is_scheduled || match.scoreboard_mode !== null) ? (
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
                                        {canPickCompetingTeams ? (
                                            <Select
                                                value={delegationAId}
                                                onValueChange={(value) =>
                                                    selectCompetingDelegation(
                                                        'a',
                                                        value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger
                                                    id="side-a"
                                                    aria-label="Side A competing team"
                                                >
                                                    <SelectValue placeholder="Select competing team" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {delegationOptions
                                                        .filter(
                                                            (option) =>
                                                                String(
                                                                    option.id,
                                                                ) !==
                                                                delegationBId,
                                                        )
                                                        .map((option) => (
                                                            <SelectItem
                                                                key={option.id}
                                                                value={String(
                                                                    option.id,
                                                                )}
                                                            >
                                                                {option.label}
                                                            </SelectItem>
                                                        ))}
                                                </SelectContent>
                                            </Select>
                                        ) : (
                                            <Input
                                                id="side-a"
                                                list="participant-label-options"
                                                value={sideALabel}
                                                onChange={(e) =>
                                                    setSideALabel(
                                                        e.target.value,
                                                    )
                                                }
                                                required
                                                readOnly={
                                                    hasGeneratedParticipants &&
                                                    !overrideParticipants
                                                }
                                            />
                                        )}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="side-b">Side B</Label>
                                        {canPickCompetingTeams ? (
                                            <Select
                                                value={delegationBId}
                                                onValueChange={(value) =>
                                                    selectCompetingDelegation(
                                                        'b',
                                                        value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger
                                                    id="side-b"
                                                    aria-label="Side B competing team"
                                                >
                                                    <SelectValue placeholder="Select competing team" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {delegationOptions
                                                        .filter(
                                                            (option) =>
                                                                String(
                                                                    option.id,
                                                                ) !==
                                                                delegationAId,
                                                        )
                                                        .map((option) => (
                                                            <SelectItem
                                                                key={option.id}
                                                                value={String(
                                                                    option.id,
                                                                )}
                                                            >
                                                                {option.label}
                                                            </SelectItem>
                                                        ))}
                                                </SelectContent>
                                            </Select>
                                        ) : (
                                            <Input
                                                id="side-b"
                                                list="participant-label-options"
                                                value={sideBLabel}
                                                onChange={(e) =>
                                                    setSideBLabel(
                                                        e.target.value,
                                                    )
                                                }
                                                required
                                                readOnly={
                                                    hasGeneratedParticipants &&
                                                    !overrideParticipants
                                                }
                                            />
                                        )}
                                    </div>
                                    {canPickCompetingTeams && (
                                        <p className="text-sm text-muted-foreground sm:col-span-2">
                                            Athletes already confirmed on each
                                            team's roster will be added to the
                                            match automatically.
                                        </p>
                                    )}
                                    <datalist id="participant-label-options">
                                        {meetDelegationOptions.map((option) => (
                                            <option
                                                key={`d-${option.id}`}
                                                value={option.label}
                                            />
                                        ))}
                                        {athleteOptions.map((option) => (
                                            <option
                                                key={`a-${option.athlete_id}`}
                                                value={option.label}
                                            />
                                        ))}
                                    </datalist>
                                    {(suggestedLabels[0] === null &&
                                        !canPickCompetingTeams) && (
                                        <p className="text-sm text-muted-foreground sm:col-span-2">
                                            Supporting entry data for this match
                                            is incomplete — type the competing
                                            Side A and Side B exactly as they
                                            should appear on the scoreboard. The
                                            list suggests active delegations and
                                            entered athletes.
                                        </p>
                                    )}
                                    {hasGeneratedParticipants &&
                                        canOverrideParticipants && (
                                            <div className="flex flex-col gap-2 sm:col-span-2">
                                                <div className="flex items-center gap-2">
                                                    <Checkbox
                                                        id="override-participants"
                                                        checked={
                                                            overrideParticipants
                                                        }
                                                        onCheckedChange={(
                                                            checked,
                                                        ) =>
                                                            setOverrideParticipants(
                                                                checked === true,
                                                            )
                                                        }
                                                    />
                                                    <Label
                                                        htmlFor="override-participants"
                                                        className="font-normal"
                                                    >
                                                        Override participants for
                                                        scoreboard — the
                                                        generated Side A / Side B
                                                        are wrong or incomplete
                                                    </Label>
                                                </div>
                                                {overrideParticipants && (
                                                    <>
                                                        <p className="text-sm text-muted-foreground">
                                                            This changes only the
                                                            scoreboard's
                                                            operational
                                                            participant labels
                                                            and is recorded in
                                                            the match history. It
                                                            does not modify
                                                            athlete registration,
                                                            entries, confirmed
                                                            entries, team rosters
                                                            or coach assignments.
                                                        </p>
                                                        <Input
                                                            aria-label="Reason for override"
                                                            placeholder="Reason (optional)"
                                                            value={overrideReason}
                                                            maxLength={500}
                                                            onChange={(e) =>
                                                                setOverrideReason(
                                                                    e.target
                                                                        .value,
                                                                )
                                                            }
                                                        />
                                                    </>
                                                )}
                                            </div>
                                        )}
                                    {match.is_team_event &&
                                        suggestedLabels[0] !== null && (
                                            <div className="sm:col-span-2">
                                                <PreStartRosterManager
                                                    matchId={match.id}
                                                    sideALabel={
                                                        suggestedLabels[0] ??
                                                        'Side A'
                                                    }
                                                    sideBLabel={
                                                        suggestedLabels[1] ??
                                                        'Side B'
                                                    }
                                                />
                                            </div>
                                        )}
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
                                    {settingsFields !== null && (
                                        <div className="sm:col-span-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    setShowSettings(
                                                        (current) => !current,
                                                    )
                                                }
                                            >
                                                {showSettings
                                                    ? 'Hide game settings'
                                                    : 'Game settings (optional)'}
                                            </Button>
                                            {showSettings && (
                                                <div className="mt-3 rounded-lg border p-4">
                                                    <GameSettingsFields
                                                        fields={settingsFields}
                                                        values={settingsValues}
                                                        onChange={(
                                                            key,
                                                            value,
                                                        ) =>
                                                            setSettingsValues(
                                                                (current) => ({
                                                                    ...current,
                                                                    [key]: value,
                                                                }),
                                                            )
                                                        }
                                                    />
                                                </div>
                                            )}
                                        </div>
                                    )}
                                    <Button
                                        type="submit"
                                        className="sm:col-span-2"
                                        disabled={
                                            canPickCompetingTeams &&
                                            (!delegationAId || !delegationBId)
                                        }
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
                                ? 'flex min-h-full flex-col items-center justify-start gap-8 overflow-y-auto bg-background p-8'
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

                        {isManager &&
                            !fullscreen &&
                            (session.operational_remarks?.length ?? 0) > 0 && (
                                <div className="mx-auto w-full max-w-2xl rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200 print:hidden">
                                    <p className="font-medium">
                                        Scoreboard operations note
                                    </p>
                                    <ul className="list-disc pl-5">
                                        {session.operational_remarks?.map(
                                            (remark) => (
                                                <li key={remark}>{remark}</li>
                                            ),
                                        )}
                                    </ul>
                                </div>
                            )}

                        {isManager && isActive && canOverrideParticipants && (
                            <OverrideParticipantsPanel
                                session={session}
                                isTeamEvent={match.is_team_event}
                                delegationOptions={meetDelegationOptions}
                                athleteOptions={athleteOptions}
                            />
                        )}

                        {isManager && isActive && basketballState && (
                            <BasketballGameControl
                                session={session}
                                state={basketballState}
                                onSessionChange={setSession}
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

                        {isManager && isActive && tennisState && (
                            <TennisGameControl
                                session={session}
                                state={tennisState}
                            />
                        )}

                        {isManager && isActive && goalBallState && (
                            <GoalBallGameControl
                                session={session}
                                state={goalBallState}
                            />
                        )}

                        {isManager && isActive && billiardState && (
                            <BilliardGameControl
                                session={session}
                                state={billiardState}
                            />
                        )}

                        {isManager && isActive && bocceState && (
                            <BocceGameControl
                                session={session}
                                state={bocceState}
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
                            !wrestlingState &&
                            !tennisState &&
                            !goalBallState &&
                            !billiardState &&
                            !bocceState && (
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
                                wrestlingState ||
                                tennisState ||
                                goalBallState ||
                                billiardState ||
                                bocceState) && (
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
                            <div
                                className={cn(
                                    'mx-auto w-full print:hidden',
                                    fullscreen ? 'max-w-4xl' : 'max-w-2xl',
                                )}
                            >
                                <PlayByPlayList
                                    playByPlay={session.playByPlay}
                                    onRemove={removePlay}
                                    scrollable
                                />
                            </div>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

/**
 * Spec §6 — lets an authorized Tournament ICT correct the scoreboard's
 * operational Side A / Side B on a running session when the generated
 * Match data is wrong or conflicts with what is actually competing. The
 * optional athlete pick is display-only; nothing in the registration
 * domain is touched, and the change is recorded in the match history.
 */
function OverrideParticipantsPanel({
    session,
    isTeamEvent,
    delegationOptions,
    athleteOptions,
}: {
    session: Session;
    isTeamEvent: boolean;
    delegationOptions: Array<{ id: number; label: string }>;
    athleteOptions: Array<{
        athlete_id: number;
        label: string;
        unlinked: boolean;
    }>;
}) {
    const [open, setOpen] = useState(false);
    const [sideA, setSideA] = useState(session.side_a_label);
    const [sideB, setSideB] = useState(session.side_b_label);
    const [athleteA, setAthleteA] = useState('');
    const [athleteB, setAthleteB] = useState('');
    const [reason, setReason] = useState('');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.patch(
            participantsRoute(session.id).url,
            {
                side_a_label: sideA,
                side_b_label: sideB,
                reason,
                ...(!isTeamEvent && athleteA
                    ? { side_a_athlete_id: Number(athleteA) }
                    : {}),
                ...(!isTeamEvent && athleteB
                    ? { side_b_athlete_id: Number(athleteB) }
                    : {}),
            },
            {
                preserveScroll: true,
                onSuccess: () => setOpen(false),
            },
        );
    };

    return (
        <div className="mx-auto w-full max-w-2xl print:hidden">
            <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setOpen((current) => !current)}
            >
                {open ? 'Hide' : 'Override participants for scoreboard'}
            </Button>
            {open && (
                <form
                    onSubmit={submit}
                    className="mt-3 grid gap-3 rounded-lg border p-4 sm:grid-cols-2"
                >
                    <p className="text-sm text-muted-foreground sm:col-span-2">
                        Sets the operational scoreboard labels only. Recorded in
                        the match history with the previous values. Does not
                        change athlete registration, entries, confirmed entries,
                        team rosters or coach assignments.
                    </p>
                    <datalist id="override-label-options">
                        {delegationOptions.map((option) => (
                            <option key={option.id} value={option.label} />
                        ))}
                        {athleteOptions.map((option) => (
                            <option
                                key={option.athlete_id}
                                value={option.label}
                            />
                        ))}
                    </datalist>
                    <div className="grid gap-2">
                        <Label htmlFor="override-side-a">Side A</Label>
                        <Input
                            id="override-side-a"
                            list="override-label-options"
                            value={sideA}
                            onChange={(e) => setSideA(e.target.value)}
                            required
                        />
                        {!isTeamEvent && athleteOptions.length > 0 && (
                            <select
                                aria-label="Side A athlete (optional)"
                                value={athleteA}
                                onChange={(e) => setAthleteA(e.target.value)}
                                className="h-9 rounded-md border bg-background px-2 text-sm"
                            >
                                <option value="">
                                    Link athlete (optional)
                                </option>
                                {athleteOptions.map((option) => (
                                    <option
                                        key={option.athlete_id}
                                        value={String(option.athlete_id)}
                                    >
                                        {option.label}
                                        {option.unlinked ? ' — unlinked' : ''}
                                    </option>
                                ))}
                            </select>
                        )}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="override-side-b">Side B</Label>
                        <Input
                            id="override-side-b"
                            list="override-label-options"
                            value={sideB}
                            onChange={(e) => setSideB(e.target.value)}
                            required
                        />
                        {!isTeamEvent && athleteOptions.length > 0 && (
                            <select
                                aria-label="Side B athlete (optional)"
                                value={athleteB}
                                onChange={(e) => setAthleteB(e.target.value)}
                                className="h-9 rounded-md border bg-background px-2 text-sm"
                            >
                                <option value="">
                                    Link athlete (optional)
                                </option>
                                {athleteOptions.map((option) => (
                                    <option
                                        key={option.athlete_id}
                                        value={String(option.athlete_id)}
                                    >
                                        {option.label}
                                        {option.unlinked ? ' — unlinked' : ''}
                                    </option>
                                ))}
                            </select>
                        )}
                    </div>
                    <Input
                        aria-label="Reason for override"
                        placeholder="Reason (optional)"
                        value={reason}
                        maxLength={500}
                        onChange={(e) => setReason(e.target.value)}
                        className="sm:col-span-2"
                    />
                    <Button
                        type="submit"
                        variant="outline"
                        className="sm:col-span-2"
                    >
                        Save scoreboard participants
                    </Button>
                </form>
            )}
        </div>
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
