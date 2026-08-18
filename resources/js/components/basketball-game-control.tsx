import { router } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    Bell,
    LogIn,
    LogOut,
    Settings2,
    Users,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { WhistleIcon } from '@/components/icons/whistle-icon';
import { CountdownClock } from '@/components/live-score-display';
import type { BasketballState, LiveSession, RosterPlayer } from '@/components/live-score-display';
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
    destroy as rosterDestroyRoute,
    show as rosterShowRoute,
    store as rosterStoreRoute,
} from '@/routes/match-roster';
import {
    foul as foulRoute,
    gameClock as gameClockRoute,
    horn as hornRoute,
    lineup as lineupRoute,
    pause as pauseRoute,
    period as periodRoute,
    possession as possessionRoute,
    resume as resumeRoute,
    score as scoreRoute,
    settings as settingsRoute,
    shotClock as shotClockRoute,
} from '@/routes/scoring';

export type EligibleAthlete = { id: number; label: string };

type Side = 'a' | 'b';

type RosterData = {
    roster: { a: RosterPlayer[]; b: RosterPlayer[] };
    eligibleAthletes: { a: EligibleAthlete[]; b: EligibleAthlete[] };
};

function SettingsDialog({
    state,
    disabled,
    onSave,
}: {
    state: BasketballState;
    /** Disabled while the clock is running (owner instruction) — settings
     * only change during a stoppage, the same reasoning as the
     * substitution modal below. */
    disabled: boolean;
    onSave: (data: {
        minutes_per_period: number;
        shot_clock_duration: number;
        team_color_a: string;
        team_color_b: string;
        quarters: number;
    }) => void;
}) {
    const [open, setOpen] = useState(false);
    const [minutes, setMinutes] = useState(String(state.minutes_per_period));
    const [shotClock, setShotClock] = useState(
        String(state.shot_clock_duration),
    );
    const [colorA, setColorA] = useState(state.team_color_a);
    const [colorB, setColorB] = useState(state.team_color_b);
    const [quarters, setQuarters] = useState(String(state.quarters));

    const submit = (e: FormEvent) => {
        e.preventDefault();
        onSave({
            minutes_per_period: Number(minutes),
            shot_clock_duration: Number(shotClock),
            team_color_a: colorA,
            team_color_b: colorB,
            quarters: Number(quarters),
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
                        <DialogTitle>Game control settings</DialogTitle>
                    </DialogHeader>
                    <div className="grid gap-4 py-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="minutes-per-period">
                                Minutes per period
                            </Label>
                            <Input
                                id="minutes-per-period"
                                type="number"
                                min={1}
                                max={20}
                                value={minutes}
                                onChange={(e) => setMinutes(e.target.value)}
                                required
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="shot-clock-duration">
                                Shot clock (seconds)
                            </Label>
                            <Input
                                id="shot-clock-duration"
                                type="number"
                                min={5}
                                max={60}
                                value={shotClock}
                                onChange={(e) => setShotClock(e.target.value)}
                                required
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="quarters">Quarters</Label>
                            <Select value={quarters} onValueChange={setQuarters}>
                                <SelectTrigger id="quarters">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="2">2</SelectItem>
                                    <SelectItem value="4">4</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="team-color-a">
                                Side A color
                            </Label>
                            <Input
                                id="team-color-a"
                                type="color"
                                className="h-10 w-full p-1"
                                value={colorA}
                                onChange={(e) => setColorA(e.target.value)}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="team-color-b">
                                Side B color
                            </Label>
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

/**
 * Substitutions + roster management for one side, in a single modal.
 * Deliberately loads nothing until opened: the full roster (bench
 * included) and the eligible-athletes pool are real data an operator only
 * needs while actively managing the lineup, not on every 5s poll tick — so
 * this fetches them itself, on demand, via a plain `fetch()` to the
 * dedicated `match-roster.show` endpoint rather than riding along on the
 * page's Inertia props or the live-polled session payload. Because that
 * fetch is outside Inertia's prop system, a mutation made from inside this
 * modal (sub, add, remove) won't auto-refresh it the way an Inertia visit
 * refreshes `session` — so each action re-fetches afterward.
 */
function TeamModal({
    side,
    label,
    matchId,
    onCourtIds,
    isPaused,
    onToggleCourt,
    onAddPlayer,
    onRemovePlayer,
}: {
    side: Side;
    label: string;
    matchId: number;
    onCourtIds: number[];
    isPaused: boolean;
    onToggleCourt: (rosterPlayerId: number, onCourt: boolean) => void;
    onAddPlayer: (entryId: number, jerseyNumber: string, isStarter: boolean) => void;
    onRemovePlayer: (rosterPlayerId: number) => void;
}) {
    const [open, setOpen] = useState(false);
    const [data, setData] = useState<RosterData | null>(null);
    const [entryId, setEntryId] = useState('');
    const [jerseyNumber, setJerseyNumber] = useState('');
    const [isStarter, setIsStarter] = useState(false);

    // setState only happens inside the fetch's own callback (never
    // synchronously in the effect body) — the React-recommended shape for
    // an effect that fetches, per this file's own established convention
    // (see live-score-display.tsx's useTicks: setState only from a timer
    // callback, never synchronously during the effect itself).
    const load = () => {
        fetch(rosterShowRoute(matchId).url, {
            headers: { Accept: 'application/json' },
        })
            .then((response) => response.json())
            .then((json: RosterData) => setData(json));
    };

    useEffect(() => {
        if (open) {
            load();
        }
        // Only load when the modal opens — never on mount, never on a
        // background timer. This effect intentionally doesn't depend on
        // `matchId` changing (it never does for a mounted scoreboard).
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    const roster = data?.roster[side] ?? [];
    const eligible = data?.eligibleAthletes[side] ?? [];
    const onCourt = roster.filter((p) => onCourtIds.includes(p.id));
    const bench = roster.filter((p) => !onCourtIds.includes(p.id));
    const courtFull = onCourt.length >= 5;

    // The mutation itself is an Inertia visit (updates `session` normally);
    // this modal's own fetched copy needs a follow-up refetch since it
    // isn't wired into Inertia's prop refresh.
    const refetchSoon = () => window.setTimeout(load, 400);

    const submitAdd = (e: FormEvent) => {
        e.preventDefault();

        if (entryId === '') {
            return;
        }

        onAddPlayer(Number(entryId), jerseyNumber, isStarter);
        setEntryId('');
        setJerseyNumber('');
        setIsStarter(false);
        refetchSoon();
    };

    const handleToggle = (rosterPlayerId: number, nextOnCourt: boolean) => {
        onToggleCourt(rosterPlayerId, nextOnCourt);
        refetchSoon();
    };

    const handleRemove = (rosterPlayerId: number) => {
        onRemovePlayer(rosterPlayerId);
        refetchSoon();
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    variant="outline"
                    className="h-10 shrink-0 border-indigo-600 bg-indigo-600 text-base text-white hover:bg-indigo-700"
                    disabled={!isPaused}
                    title={
                        !isPaused
                            ? 'Pause the game to substitute players'
                            : undefined
                    }
                >
                    <Users aria-hidden="true" />
                    Substitute
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>{label}</DialogTitle>
                </DialogHeader>

                {open && data === null ? (
                    <p className="py-6 text-center text-sm text-muted-foreground">
                        Loading roster…
                    </p>
                ) : (
                    <>
                        {/* On court and Bench side by side — the modal is
                            wide enough now that stacking them just forces
                            scrolling for no reason. */}
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <p className="mb-1 text-xs font-medium text-muted-foreground uppercase">
                                    On court ({onCourt.length}/5)
                                </p>
                                <ul className="divide-y rounded-lg border text-sm">
                                    {onCourt.length === 0 && (
                                        <li className="px-3 py-3 text-muted-foreground">
                                            No one on court yet.
                                        </li>
                                    )}
                                    {onCourt.map((player) => (
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
                                                size="sm"
                                                className="h-9 shrink-0"
                                                disabled={!isPaused}
                                                aria-label={`Bench ${player.name}`}
                                                onClick={() =>
                                                    handleToggle(
                                                        player.id,
                                                        false,
                                                    )
                                                }
                                            >
                                                <LogOut
                                                    aria-hidden="true"
                                                    className="size-4"
                                                />
                                                Bench
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            <div>
                                <p className="mb-1 text-xs font-medium text-muted-foreground uppercase">
                                    Bench ({bench.length})
                                </p>
                                <ul className="divide-y rounded-lg border text-sm">
                                    {bench.length === 0 && (
                                        <li className="px-3 py-3 text-muted-foreground">
                                            No one on the bench.
                                        </li>
                                    )}
                                    {bench.map((player) => (
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
                                                {player.is_starter && (
                                                    <span className="ml-1 text-xs text-muted-foreground">
                                                        (starter)
                                                    </span>
                                                )}
                                            </span>
                                            <span className="flex shrink-0 items-center gap-1">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-9"
                                                    disabled={
                                                        !isPaused || courtFull
                                                    }
                                                    aria-label={`Send ${player.name} to court`}
                                                    onClick={() =>
                                                        handleToggle(
                                                            player.id,
                                                            true,
                                                        )
                                                    }
                                                >
                                                    <LogIn
                                                        aria-hidden="true"
                                                        className="size-4"
                                                    />
                                                    Send in
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-9"
                                                    aria-label={`Remove ${player.name} from roster`}
                                                    onClick={() =>
                                                        handleRemove(player.id)
                                                    }
                                                >
                                                    <X
                                                        aria-hidden="true"
                                                        className="size-4"
                                                    />
                                                </Button>
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>

                        <form
                            onSubmit={submitAdd}
                            className="grid grid-cols-1 items-end gap-3 pt-2 sm:grid-cols-[1fr_auto_auto_auto]"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor={`add-athlete-${side}`}>
                                    Add registered athlete
                                </Label>
                                <Select
                                    value={entryId}
                                    onValueChange={setEntryId}
                                >
                                    <SelectTrigger id={`add-athlete-${side}`}>
                                        <SelectValue placeholder="Select an athlete" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {eligible.map((athlete) => (
                                            <SelectItem
                                                key={athlete.id}
                                                value={String(athlete.id)}
                                            >
                                                {athlete.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`jersey-${side}`}>
                                    Jersey #
                                </Label>
                                <Input
                                    id={`jersey-${side}`}
                                    className="sm:w-24"
                                    value={jerseyNumber}
                                    onChange={(e) =>
                                        setJerseyNumber(e.target.value)
                                    }
                                    maxLength={10}
                                />
                            </div>
                            <div className="flex items-center gap-2 pb-2">
                                <Checkbox
                                    id={`starter-${side}`}
                                    checked={isStarter}
                                    onCheckedChange={(checked) =>
                                        setIsStarter(checked === true)
                                    }
                                />
                                <Label
                                    htmlFor={`starter-${side}`}
                                    className="font-normal"
                                >
                                    Starter
                                </Label>
                            </div>
                            <Button type="submit" disabled={entryId === ''}>
                                Add to roster
                            </Button>
                        </form>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}

function OnCourtRow({
    player,
    onScore,
    onFoul,
    points,
    fouls,
}: {
    player: RosterPlayer;
    onScore: (delta: number) => void;
    onFoul: () => void;
    points: number;
    fouls: number;
}) {
    return (
        <li className="flex flex-col gap-2 px-3 py-2.5 sm:flex-row sm:items-center sm:justify-between">
            <span className="min-w-0 truncate text-base">
                {player.jersey_number && (
                    <span className="font-mono text-muted-foreground">
                        #{player.jersey_number}{' '}
                    </span>
                )}
                {player.name}{' '}
                <span className="text-sm text-muted-foreground">
                    ({points} pts - {fouls} PF)
                </span>
            </span>
            <span className="flex items-center gap-1.5">
                {[1, 2, 3].map((points) => (
                    <Button
                        key={points}
                        className="h-10 min-w-10 border-emerald-700 bg-emerald-600 text-lg font-bold text-white hover:bg-emerald-700"
                        aria-label={`Add ${points} point${points > 1 ? 's' : ''}, ${player.name}`}
                        onClick={() => onScore(points)}
                    >
                        +{points}
                    </Button>
                ))}
                <Button
                    variant="destructive"
                    className="h-10 text-base font-semibold"
                    aria-label={`Foul, ${player.name}`}
                    onClick={onFoul}
                >
                    Foul
                </Button>
            </span>
        </li>
    );
}

function SidePanel({
    side,
    label,
    color,
    fouls,
    onCourt,
    matchId,
    onCourtIds,
    isPaused,
    state,
    onScore,
    onFoul,
    onToggleCourt,
    onAddPlayer,
    onRemovePlayer,
    onResetFouls,
}: {
    side: Side;
    label: string;
    color: string;
    fouls: number;
    onCourt: RosterPlayer[];
    matchId: number;
    onCourtIds: number[];
    isPaused: boolean;
    state: BasketballState;
    onScore: (rosterPlayerId: number, delta: number) => void;
    onFoul: (rosterPlayerId: number) => void;
    onToggleCourt: (rosterPlayerId: number, onCourt: boolean) => void;
    onAddPlayer: (
        entryId: number,
        jerseyNumber: string,
        isStarter: boolean,
    ) => void;
    onRemovePlayer: (rosterPlayerId: number) => void;
    onResetFouls: () => void;
}) {
    return (
        <div
            className="flex flex-col gap-3 rounded-xl border-2 p-3 sm:p-4"
            style={{ borderColor: color }}
        >
            <div className="flex items-center justify-between gap-2">
                <span className="flex min-w-0 items-center gap-2 text-lg font-medium">
                    <span
                        className="size-3 shrink-0 rounded-full"
                        style={{ backgroundColor: color }}
                        aria-hidden="true"
                    />
                    <span className="truncate">
                        {label}{' '}
                        <span className="text-sm font-normal text-muted-foreground">
                            (Fouls: {fouls})
                        </span>
                    </span>
                    <Button
                        variant="outline"
                        size="sm"
                        className="h-8 shrink-0"
                        onClick={onResetFouls}
                    >
                        Reset fouls
                    </Button>
                </span>
                <TeamModal
                    side={side}
                    label={label}
                    matchId={matchId}
                    onCourtIds={onCourtIds}
                    isPaused={isPaused}
                    onToggleCourt={onToggleCourt}
                    onAddPlayer={onAddPlayer}
                    onRemovePlayer={onRemovePlayer}
                />
            </div>

            <div>
                <p className="mb-1 text-xs font-medium text-muted-foreground uppercase">
                    On court ({onCourt.length}/5)
                </p>
                <ul className="divide-y rounded-lg border">
                    {onCourt.length === 0 && (
                        <li className="px-3 py-3 text-sm text-muted-foreground">
                            No one on court yet — open "Substitute" to send
                            players in.
                        </li>
                    )}
                    {onCourt.map((player) => (
                        <OnCourtRow
                            key={player.id}
                            player={player}
                            points={state.player_points[String(player.id)] ?? 0}
                            fouls={state.player_fouls[String(player.id)] ?? 0}
                            onScore={(delta) => onScore(player.id, delta)}
                            onFoul={() => onFoul(player.id)}
                        />
                    ))}
                </ul>
            </div>

        </div>
    );
}

/** Defaults for every key WP live-basketball added to sport_state — a
 * session started before this shipped (e.g. seeded demo data) only has the
 * original {fouls_a, fouls_b}, so every other field reads as `undefined`
 * without this. Real fallback values, not just crash prevention: an
 * operator picking up an old session sees a sane starting clock/possession
 * exactly as if it had just started. */
const BASKETBALL_STATE_DEFAULTS: BasketballState = {
    fouls_a: 0,
    fouls_b: 0,
    on_court_a: [],
    on_court_b: [],
    possession: null,
    player_points: {},
    player_fouls: {},
    game_clock_seconds: 600,
    game_clock_updated_at: null,
    shot_clock_seconds: 24,
    shot_clock_updated_at: null,
    minutes_per_period: 10,
    shot_clock_duration: 24,
    team_color_a: '#dc2626',
    team_color_b: '#2563eb',
    horn_sounded_at: null,
    quarters: 4,
};

export function BasketballGameControl({
    session,
    state: rawState,
    onSessionChange,
}: {
    session: LiveSession;
    state: BasketballState;
    onSessionChange: (session: LiveSession) => void;
}) {
    // Older showcase sessions stored per-quarter score rows under the
    // `quarters` key. The interactive controller later standardized that
    // key as the configured number of periods (2 or 4). Never pass the
    // legacy object array into JSX or numeric comparisons: React cannot
    // render those objects and the whole scoreboard becomes blank.
    const normalizedQuarters =
        rawState.quarters === 2 || rawState.quarters === 4
            ? rawState.quarters
            : 4;
    const state: BasketballState = {
        ...BASKETBALL_STATE_DEFAULTS,
        ...rawState,
        quarters: normalizedQuarters,
    };
    const running = session.status === 'in_progress';
    const isPaused = session.status === 'paused';
    const optimisticRequest = useRef(0);

    // Basketball doesn't track a structured "current quarter" column —
    // it reuses the same free-text `period_label` every board type has
    // (e.g. "Q2"), same as boxing's round label and softball's inning
    // label already do. Parsed here only for the stepper's display/
    // bounds-checking; the write path is the plain `scoring.period`
    // endpoint every sport already uses, not a new basketball-only one.
    const currentQuarter = Math.min(
        Math.max(1, Number(session.period_label?.match(/\d+/)?.[0]) || 1),
        state.quarters,
    );

    const changeQuarter = (next: number) => {
        if (next < 1 || next > state.quarters) {
            return;
        }

        router.patch(
            periodRoute(session.id).url,
            { period_label: `Q${next}`, status_note: session.status_note },
            { preserveScroll: true },
        );
    };

    const patchSessionOptimistically = (
        optimisticSession: LiveSession,
        url: string,
        data: Record<string, unknown> = {},
    ) => {
        const previousSession = session;
        const requestId = ++optimisticRequest.current;
        onSessionChange(optimisticSession);

        const csrfToken = document
            .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.content;
        const xsrfToken = document.cookie
            .split('; ')
            .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
            ?.slice('XSRF-TOKEN='.length);

        void fetch(url, {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                ...(xsrfToken
                    ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfToken) }
                    : {}),
            },
            body: JSON.stringify(data),
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Background save failed: ${response.status}`);
                }
            })
            .catch(() => {
                // A newer optimistic action owns the current display and
                // must not be undone by an older request failing later.
                if (optimisticRequest.current === requestId) {
                    onSessionChange(previousSession);
                }
            });
    };

    const pauseResume = () => {
        const now = new Date();
        const sportState = { ...state };

        if (isPaused) {
            const anchor = now.toISOString();
            sportState.game_clock_updated_at = anchor;
            sportState.shot_clock_updated_at = anchor;
        } else {
            const freezeClock = (
                seconds: number,
                anchor: string | null,
            ): number => {
                if (!anchor) {
                    return seconds;
                }

                const elapsed = Math.max(
                    0,
                    Math.floor((now.getTime() - Date.parse(anchor)) / 1000),
                );

                return Math.max(0, seconds - elapsed);
            };

            sportState.game_clock_seconds = freezeClock(
                state.game_clock_seconds,
                state.game_clock_updated_at,
            );
            sportState.shot_clock_seconds = freezeClock(
                state.shot_clock_seconds,
                state.shot_clock_updated_at,
            );
            sportState.game_clock_updated_at = null;
            sportState.shot_clock_updated_at = null;
        }

        patchSessionOptimistically(
            {
                ...session,
                status: isPaused ? 'in_progress' : 'paused',
                status_label: isPaused ? 'In Progress' : 'Paused',
                clock_running: isPaused,
                sport_state: sportState,
            },
            (isPaused ? resumeRoute : pauseRoute)(session.id).url,
        );
    };

    const soundHorn = () => {
        router.patch(hornRoute(session.id).url, {}, { preserveScroll: true });
    };

    const setPossession = (side: Side | null) => {
        patchSessionOptimistically(
            {
                ...session,
                sport_state: { ...state, possession: side },
            },
            possessionRoute(session.id).url,
            { side },
        );
    };

    useEffect(() => {
        const handleKeyDown = (event: KeyboardEvent) => {
            const target = event.target as HTMLElement | null;
            const isTyping =
                target?.isContentEditable ||
                target?.tagName === 'INPUT' ||
                target?.tagName === 'TEXTAREA' ||
                target?.tagName === 'SELECT';

            if (event.code !== 'Space' || event.repeat || isTyping) {
                return;
            }

            event.preventDefault();
            pauseResume();
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    });

    const adjustGameClock = (deltaSeconds: number) => {
        router.patch(
            gameClockRoute(session.id).url,
            { seconds: Math.max(0, state.game_clock_seconds + deltaSeconds) },
            { preserveScroll: true },
        );
    };

    const resetGameClockToPeriod = () => {
        router.patch(
            gameClockRoute(session.id).url,
            { seconds: state.minutes_per_period * 60 },
            { preserveScroll: true },
        );
    };

    const resetShotClock = () => {
        router.patch(
            shotClockRoute(session.id).url,
            {},
            { preserveScroll: true },
        );
    };

    const saveSettings = (data: {
        minutes_per_period: number;
        shot_clock_duration: number;
        team_color_a: string;
        team_color_b: string;
        quarters: number;
    }) => {
        router.patch(settingsRoute(session.id).url, data, {
            preserveScroll: true,
        });
    };

    const scorePlayer = (side: Side, rosterPlayerId: number, delta: number) => {
        router.patch(
            scoreRoute(session.id).url,
            { type: 'point', side, delta, roster_player_id: rosterPlayerId },
            { preserveScroll: true },
        );
    };

    const foulPlayer = (side: Side, rosterPlayerId: number) => {
        router.patch(
            foulRoute(session.id).url,
            { action: 'add', side, roster_player_id: rosterPlayerId },
            { preserveScroll: true },
        );
    };

    const resetTeamFouls = () => {
        router.patch(
            foulRoute(session.id).url,
            { action: 'reset' },
            { preserveScroll: true },
        );
    };

    const toggleCourt = (side: Side, rosterPlayerId: number, onCourt: boolean) => {
        router.patch(
            lineupRoute(session.id).url,
            { side, roster_player_id: rosterPlayerId, on_court: onCourt },
            { preserveScroll: true },
        );
    };

    const addPlayer = (
        side: Side,
        entryId: number,
        jerseyNumber: string,
        isStarter: boolean,
    ) => {
        router.post(
            rosterStoreRoute(session.match_id).url,
            {
                entry_id: entryId,
                side,
                jersey_number: jerseyNumber,
                is_starter: isStarter,
            },
            { preserveScroll: true },
        );
    };

    const removePlayer = (rosterPlayerId: number) => {
        router.delete(rosterDestroyRoute(rosterPlayerId).url, {
            preserveScroll: true,
        });
    };

    return (
        <div className="flex w-full flex-col gap-4 print:hidden">
            <div className="flex flex-col gap-2 rounded-xl border bg-muted/20 p-2">
                {/* Row 1: Settings, quarter stepper, then the two clock readouts. */}
                <div className="flex flex-wrap items-center justify-center gap-2">
                    <SettingsDialog
                        state={state}
                        disabled={running}
                        onSave={saveSettings}
                    />

                    <div className="flex h-10 items-center gap-1 rounded-md border bg-background px-2">
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-7"
                            disabled={currentQuarter <= 1}
                            aria-label="Previous quarter"
                            onClick={() => changeQuarter(currentQuarter - 1)}
                        >
                            <ArrowLeft aria-hidden="true" className="size-4" />
                        </Button>
                        <span className="text-base font-semibold tabular-nums">
                            Q{currentQuarter} / {state.quarters}
                        </span>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-7"
                            disabled={currentQuarter >= state.quarters}
                            aria-label="Next quarter"
                            onClick={() => changeQuarter(currentQuarter + 1)}
                        >
                            <ArrowRight aria-hidden="true" className="size-4" />
                        </Button>
                    </div>

                    <div className="flex h-10 items-center gap-1.5 rounded-md border bg-background px-3">
                        <span className="text-sm text-muted-foreground">
                            Clock
                        </span>
                        <span className="font-mono text-lg font-semibold tabular-nums">
                            <CountdownClock
                                anchor={state.game_clock_updated_at}
                                baseSeconds={state.game_clock_seconds}
                                running={running}
                            />
                        </span>
                        <Button
                            variant="ghost"
                            size="sm"
                            className="h-8"
                            onClick={() => adjustGameClock(-10)}
                        >
                            -10s
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            className="h-8"
                            onClick={resetGameClockToPeriod}
                        >
                            Reset
                        </Button>
                    </div>

                    <div className="flex h-10 items-center gap-1.5 rounded-md border bg-background px-3">
                        <span className="text-sm text-muted-foreground">
                            Shot
                        </span>
                        <span className="font-mono text-lg font-semibold tabular-nums">
                            <CountdownClock
                                anchor={state.shot_clock_updated_at}
                                baseSeconds={state.shot_clock_seconds}
                                running={running}
                            />
                        </span>
                        <Button
                            variant="ghost"
                            size="sm"
                            className="h-8"
                            onClick={resetShotClock}
                        >
                            Reset
                        </Button>
                    </div>
                </div>

                {/* Row 2: Horn, Possession, then Whistle (pause/resume) —
                    color reflects what the click will do: green while it
                    would Pause, orange while it would Resume. */}
                <div className="flex flex-wrap items-center justify-center gap-2">
                    <Button
                        className="h-[4.8rem] border-2 border-slate-950 bg-orange-600 px-6 text-lg font-semibold text-white hover:bg-orange-700 dark:border-white"
                        onClick={soundHorn}
                        aria-label="Sound horn"
                    >
                        <Bell aria-hidden="true" className="size-5" />
                        Horn
                    </Button>

                    <div
                        className="flex h-[4.8rem] overflow-hidden rounded-md border-2 border-slate-950 dark:border-white"
                        role="group"
                        aria-label="Possession"
                    >
                        <Button
                            className="h-full rounded-none border-0 px-5"
                            variant={state.possession === 'a' ? 'default' : 'outline'}
                            aria-label={`Possession: ${session.side_a_label ?? 'left team'}`}
                            aria-pressed={state.possession === 'a'}
                            onClick={() => setPossession('a')}
                        >
                            <ArrowLeft aria-hidden="true" className="size-7" />
                        </Button>
                        <Button
                            className="h-full rounded-none border-y-0 border-r border-l px-5 text-lg"
                            variant={state.possession === null ? 'default' : 'outline'}
                            aria-label="No possession"
                            aria-pressed={state.possession === null}
                            onClick={() => setPossession(null)}
                        >
                            <X aria-hidden="true" className="size-6" />
                        </Button>
                        <Button
                            className="h-full rounded-none border-0 px-5"
                            variant={state.possession === 'b' ? 'default' : 'outline'}
                            aria-label={`Possession: ${session.side_b_label ?? 'right team'}`}
                            aria-pressed={state.possession === 'b'}
                            onClick={() => setPossession('b')}
                        >
                            <ArrowRight aria-hidden="true" className="size-7" />
                        </Button>
                    </div>

                    <Button
                        className={cn(
                            'h-[4.8rem] border-2 border-slate-950 px-6 text-lg font-semibold text-white dark:border-white',
                            isPaused
                                ? 'border-orange-700 bg-orange-600 hover:bg-orange-700'
                                : 'border-emerald-700 bg-emerald-600 hover:bg-emerald-700',
                        )}
                        onClick={pauseResume}
                        aria-label={isPaused ? 'Resume clock' : 'Pause clock'}
                        title="Pause/resume clock (Space)"
                    >
                        <WhistleIcon aria-hidden="true" className="size-5" />
                        {isPaused ? 'Resume' : 'Pause'}
                    </Button>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <SidePanel
                    side="a"
                    label={session.side_a_label}
                    color={state.team_color_a}
                    fouls={state.fouls_a}
                    onCourt={session.onCourt.a}
                    matchId={session.match_id}
                    onCourtIds={state.on_court_a}
                    isPaused={isPaused}
                    state={state}
                    onScore={(id, delta) => scorePlayer('a', id, delta)}
                    onFoul={(id) => foulPlayer('a', id)}
                    onToggleCourt={(id, onCourt) =>
                        toggleCourt('a', id, onCourt)
                    }
                    onAddPlayer={(entryId, jersey, starter) =>
                        addPlayer('a', entryId, jersey, starter)
                    }
                    onRemovePlayer={removePlayer}
                    onResetFouls={resetTeamFouls}
                />
                <SidePanel
                    side="b"
                    label={session.side_b_label}
                    color={state.team_color_b}
                    fouls={state.fouls_b}
                    onCourt={session.onCourt.b}
                    matchId={session.match_id}
                    onCourtIds={state.on_court_b}
                    isPaused={isPaused}
                    state={state}
                    onScore={(id, delta) => scorePlayer('b', id, delta)}
                    onFoul={(id) => foulPlayer('b', id)}
                    onToggleCourt={(id, onCourt) =>
                        toggleCourt('b', id, onCourt)
                    }
                    onAddPlayer={(entryId, jersey, starter) =>
                        addPlayer('b', entryId, jersey, starter)
                    }
                    onRemovePlayer={removePlayer}
                    onResetFouls={resetTeamFouls}
                />
            </div>
        </div>
    );
}
