import { Maximize2, Minimize2, Trash2, WifiOff } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { LiveBadge } from '@/components/live-badge';
import { TeamLogo } from '@/components/team-logo';
import { Badge } from '@/components/ui/badge';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';

export type PlayByPlayEntry = {
    id: number;
    description: string;
    score_a: number;
    score_b: number;
    created_at: string | null;
    removable: boolean;
};

const PLAY_BY_PLAY_PREVIEW_COUNT = 8;

export type BasketballState = {
    fouls_a: number;
    fouls_b: number;
    on_court_a: number[];
    on_court_b: number[];
    possession: 'a' | 'b' | null;
    player_points: Record<string, number>;
    player_fouls: Record<string, number>;
    game_clock_seconds: number;
    game_clock_updated_at: string | null;
    shot_clock_seconds: number;
    shot_clock_updated_at: string | null;
    minutes_per_period: number;
    shot_clock_duration: number;
    team_color_a: string;
    team_color_b: string;
    horn_sounded_at: string | null;
    quarters: 2 | 4;
};

export type BoxingRound = {
    round: number;
    score_a: number;
    score_b: number;
};

export type BoxingState = {
    rounds: BoxingRound[];
    round_duration_seconds: number;
    rest_duration_seconds: number;
    total_rounds: number;
    clock_seconds: number;
    clock_updated_at: string | null;
    clock_phase: 'round' | 'rest';
    bell_sounded_at: string | null;
};

export type SoftballInning = {
    inning: number;
    runs_a: number;
    runs_b: number;
};

export type SoftballState = {
    inning: number;
    half: 'top' | 'bottom';
    outs: number;
    balls: number;
    strikes: number;
    innings: SoftballInning[];
    innings_scheduled: number;
    team_color_a: string;
    team_color_b: string;
};

export type RallySet = {
    set: number;
    score_a: number;
    score_b: number;
};

export type RallySetsState = {
    sets: RallySet[];
    current_set_score_a: number;
    current_set_score_b: number;
    sets_won_a: number;
    sets_won_b: number;
    set_target_points: number;
    deciding_set_target_points: number;
    sets_to_win: number;
    possession: 'a' | 'b' | null;
};

export type FootballState = {
    yellow_cards_a: number;
    yellow_cards_b: number;
    red_cards_a: number;
    red_cards_b: number;
    minutes_per_half: number;
};

export type RacketGame = {
    game: number;
    score_a: number;
    score_b: number;
};

export type RacketGamesState = {
    games: RacketGame[];
    current_game_score_a: number;
    current_game_score_b: number;
    games_won_a: number;
    games_won_b: number;
    game_target_points: number;
    /** 0 means no cap (table tennis); a real ceiling otherwise
     * (badminton's 30 points — wins outright even without a 2-point
     * lead). */
    hard_cap_points: number;
    games_to_win: number;
    possession: 'a' | 'b' | null;
};

export type WrestlingState = {
    period_duration_seconds: number;
    rest_duration_seconds: number;
    total_periods: number;
    clock_seconds: number;
    clock_updated_at: string | null;
    clock_phase: 'period' | 'rest';
    fall_side: 'a' | 'b' | null;
    fall_declared_at: string | null;
};

export type TennisSet = {
    set: number;
    score_a: number;
    score_b: number;
};

export type TennisState = {
    sets: TennisSet[];
    sets_won_a: number;
    sets_won_b: number;
    current_set_games_a: number;
    current_set_games_b: number;
    current_game_points_a: number;
    current_game_points_b: number;
    is_tiebreak: boolean;
    tiebreak_points_a: number;
    tiebreak_points_b: number;
    sets_to_win: number;
    possession: 'a' | 'b' | null;
};

export type GoalBallState = {
    penalty_throws_a: number;
    penalty_throws_b: number;
    minutes_per_half: number;
};

export type BilliardRack = {
    rack: number;
    winner: 'a' | 'b';
};

export type BilliardState = {
    racks: BilliardRack[];
    racks_won_a: number;
    racks_won_b: number;
    racks_to_win: number;
};

export type BocceEnd = {
    end: number;
    winner: 'a' | 'b';
    points: number;
};

export type BocceState = {
    ends: BocceEnd[];
    ends_played: number;
    target_score: number;
};

export type RosterPlayer = {
    id: number;
    name: string;
    jersey_number: string | null;
    is_starter: boolean;
    photo_url: string | null;
};

export type LiveSession = {
    id: number;
    match_id: number;
    status: 'in_progress' | 'paused' | 'ended';
    status_label: string;
    side_a_label: string;
    side_b_label: string;
    score_a: number;
    score_b: number;
    period_label: string | null;
    status_note: string | null;
    board_type:
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
    sport_state:
        | BasketballState
        | BoxingState
        | SoftballState
        | RallySetsState
        | FootballState
        | RacketGamesState
        | WrestlingState
        | TennisState
        | GoalBallState
        | BilliardState
        | BocceState
        | null;
    /** Basketball only: the (at most 5-per-side) players currently on
     * court — deliberately not the whole roster, kept small since this is
     * embedded in the live-polled payload. The full roster (bench
     * included) is fetched on demand only, by the substitution modal. */
    onCourt: { a: RosterPlayer[]; b: RosterPlayer[] };
    playByPlay: PlayByPlayEntry[];
    started_at: string | null;
    elapsed_seconds: number;
    clock_running: boolean;
};

/** A participant's real photo — boxing's red/blue corner display only.
 * Never present on the public scoreboard (athlete photos are never
 * public, docs/public-portal.md); the operator console is the only page
 * that ever passes this in. */
export type Participant = { photo_url: string | null };

const BASKETBALL_BONUS_THRESHOLD = 5;

/** Highest value each softball/baseball count ever stably displays —
 * the 4th ball is a walk (auto-resets to 0), the 3rd strike is itself
 * an out (auto-resets), and the 3rd out flips the half-inning
 * (auto-resets) — real business rules already enforced server-side
 * (WP-07-06), just reflected here as the dot rows' real max. */
const SOFTBALL_BALLS_DISPLAY_MAX = 3;
const SOFTBALL_STRIKES_DISPLAY_MAX = 2;
const SOFTBALL_OUTS_DISPLAY_MAX = 2;

export function isBasketballState(
    state: LiveSession['sport_state'],
): state is BasketballState {
    return state !== null && 'fouls_a' in state;
}

export function isBoxingState(
    state: LiveSession['sport_state'],
): state is BoxingState {
    return state !== null && 'rounds' in state;
}

export function isSoftballState(
    state: LiveSession['sport_state'],
): state is SoftballState {
    return state !== null && 'innings' in state;
}

export function isRallySetsState(
    state: LiveSession['sport_state'],
): state is RallySetsState {
    return state !== null && 'set_target_points' in state;
}

export function isFootballState(
    state: LiveSession['sport_state'],
): state is FootballState {
    return state !== null && 'yellow_cards_a' in state;
}

export function isRacketGamesState(
    state: LiveSession['sport_state'],
): state is RacketGamesState {
    return state !== null && 'games_won_a' in state;
}

export function isWrestlingState(
    state: LiveSession['sport_state'],
): state is WrestlingState {
    return state !== null && 'total_periods' in state;
}

export function isTennisState(
    state: LiveSession['sport_state'],
): state is TennisState {
    return state !== null && 'is_tiebreak' in state;
}

export function isGoalBallState(
    state: LiveSession['sport_state'],
): state is GoalBallState {
    return state !== null && 'penalty_throws_a' in state;
}

export function isBilliardState(
    state: LiveSession['sport_state'],
): state is BilliardState {
    return state !== null && 'racks_to_win' in state;
}

export function isBocceState(
    state: LiveSession['sport_state'],
): state is BocceState {
    return state !== null && 'target_score' in state;
}

/**
 * Formats a tennis game's raw point counters as the real Love/15/30/40/
 * Deuce/Ad display — mirrors `ScoringSession::formatTennisPoints()` on
 * the backend (used for the play-by-play description) exactly, so the
 * live scoreboard and the play-by-play feed never show different labels
 * for the same state. The raw counters only ever exceed 3 while both
 * sides are tied at 3+ (deuce territory) — see
 * `ScoringSessionController::applyTennisPoint()`.
 */
export function formatTennisPoints(
    a: number,
    b: number,
    labelA: string,
    labelB: string,
): string {
    if (a >= 3 && b >= 3) {
        if (a === b) {
            return 'Deuce';
        }

        return a > b ? `Ad ${labelA}` : `Ad ${labelB}`;
    }

    const labels = ['Love', '15', '30', '40'];

    return `${labels[Math.min(a, 3)]}-${labels[Math.min(b, 3)]}`;
}

/**
 * A one-second tick counter — counts up from 0 while `running`, reset by
 * simply remounting (see `RunningClock`'s `key`) rather than by reading
 * the wall clock during render, which the React Compiler's purity rule
 * disallows. `setInterval`'s callback runs outside render, so the actual
 * timekeeping (the browser's own timer) is the only "impure" part, and it
 * stays confined to the effect.
 */
export function useTicks(running: boolean): number {
    const [ticks, setTicks] = useState(0);

    useEffect(() => {
        if (!running) {
            return;
        }

        const interval = setInterval(() => setTicks((t) => t + 1), 1000);

        return () => clearInterval(interval);
    }, [running]);

    return ticks;
}

/**
 * The running game clock — `session.elapsed_seconds` (a real, pause-aware
 * value computed server-side, `ScoringSession::activeElapsedSeconds()`)
 * plus a local one-second ticker for the time between polls, so the
 * display doesn't visibly freeze for 5 seconds at a time. Keyed on
 * `elapsed_seconds` so a fresh payload (poll, Echo, or an Inertia visit)
 * remounts the ticker back to 0 instead of drifting — self-correcting
 * every update, never more than one polling interval out of sync.
 */
function RunningClock({ session }: { session: LiveSession }) {
    return (
        <TickingClock
            key={session.elapsed_seconds}
            baseSeconds={session.elapsed_seconds}
            running={session.clock_running}
        />
    );
}

function TickingClock({
    baseSeconds,
    running,
}: {
    baseSeconds: number;
    running: boolean;
}) {
    const ticks = useTicks(running);

    return <>{formatClock(baseSeconds + ticks)}</>;
}

/**
 * A manual, operator-set countdown (basketball's game/shot clock) — the
 * same anchor+ticker shape as `RunningClock`/`TickingClock` above, just
 * counting DOWN instead of up. Shared by `CenterPanel` (the main basketball
 * scoreboard clock) and `BasketballGameControl`'s toolbar readouts, so
 * there's exactly one countdown implementation, not two.
 */
export function CountdownClock({
    baseSeconds,
    anchor,
    running,
}: {
    baseSeconds: number;
    anchor: string | null;
    running: boolean;
}) {
    return (
        <TickingCountdown
            key={anchor ?? 'initial'}
            baseSeconds={baseSeconds}
            anchor={anchor}
            running={running}
        />
    );
}

function TickingCountdown({
    baseSeconds,
    anchor,
    running,
}: {
    baseSeconds: number;
    anchor: string | null;
    running: boolean;
}) {
    const ticks = useTicks(running);
    const [elapsedAtMount] = useState(() =>
        running && anchor !== null
            ? Math.max(0, Math.floor((Date.now() - Date.parse(anchor)) / 1000))
            : 0,
    );

    return <>{formatClock(baseSeconds - elapsedAtMount - ticks)}</>;
}

/**
 * "Updated Xs ago" (WP-08.5-04's "last-updated time" requirement) — the
 * same remount-a-ticker-from-zero technique as `RunningClock`, so no
 * render ever reads the wall clock directly (the React Compiler's purity
 * rule, per this file's own `useTicks` note). `key={at}` remounts the
 * ticker back to 0 every time the caller passes a genuinely new
 * timestamp (a successful poll or Echo push); while disconnected, the
 * caller stops passing a new value, so this keeps counting up — the
 * growing "ago" is itself part of the disconnected signal, not just the
 * separate warning banner.
 */
function LastUpdated({ at }: { at: number | null }) {
    if (at === null) {
        return null;
    }

    return (
        <span className="text-xs text-muted-foreground">
            <TickingLastUpdated key={at} />
        </span>
    );
}

function TickingLastUpdated() {
    const ticks = useTicks(true);

    return <>Updated {ticks === 0 ? 'just now' : `${ticks}s ago`}</>;
}

export function formatClock(totalSeconds: number): string {
    const seconds = Math.max(0, Math.floor(totalSeconds));
    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    const mm = String(mins).padStart(2, '0');
    const ss = String(secs).padStart(2, '0');

    return hrs > 0 ? `${hrs}:${mm}:${ss}` : `${mm}:${ss}`;
}

/**
 * `count` filled dots out of `max` — a real count (fouls, balls,
 * strikes, outs), just rendered as dots instead of a bare number,
 * matching the reference's visual language. `count` can exceed `max`
 * (e.g. fouls past the bonus threshold) — every dot just stays filled.
 */
export function CountDots({
    count,
    max,
    colorClass,
}: {
    count: number;
    max: number;
    colorClass: string;
}) {
    return (
        <span className="flex items-center gap-0.5" aria-hidden="true">
            {Array.from({ length: max }, (_, i) => (
                <span
                    key={i}
                    className={cn(
                        'size-2 rounded-full',
                        i < count ? colorClass : 'bg-muted',
                    )}
                />
            ))}
        </span>
    );
}

/** Football/futsal's yellow/red card tally — real square swatches (the
 * actual card shape/colors, not a generic dot) since there are only ever
 * a handful per team, unlike fouls/balls/strikes' higher-volume dots. Only
 * renders a count once it's non-zero, so a clean scoreline stays clean. */
function CardTally({ yellow, red }: { yellow: number; red: number }) {
    if (yellow === 0 && red === 0) {
        return null;
    }

    return (
        <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
            {yellow > 0 && (
                <span className="flex items-center gap-1">
                    <span
                        className="size-2.5 rounded-[2px] bg-yellow-400"
                        aria-hidden="true"
                    />
                    {yellow}
                </span>
            )}
            {red > 0 && (
                <span className="flex items-center gap-1">
                    <span
                        className="size-2.5 rounded-[2px] bg-red-600"
                        aria-hidden="true"
                    />
                    {red}
                </span>
            )}
        </span>
    );
}

/** 'red' (side A) / 'blue' (side B) — the standard boxing broadcast
 * convention — or `null` for every other board type. */
type Corner = 'red' | 'blue' | null;

/**
 * One side's boxed panel: logo (or a real photo, boxing only), name, and
 * the big running score — the scoreboard's main "box." Boxing gets a
 * tinted, corner-labeled variant with the boxer's own accredited photo
 * when one exists, falling back to the same generated logo every other
 * sport uses.
 */
function TeamPanel({
    label,
    score,
    fullscreen,
    corner,
    photoUrl,
    children,
    logoAfterScore = false,
}: {
    label: string;
    score: number;
    fullscreen: boolean;
    corner: Corner;
    photoUrl?: string | null;
    children?: ReactNode;
    logoAfterScore?: boolean;
}) {
    return (
        <div
            className={cn(
                // `flex-nowrap` is deliberate: with the score sitting
                // beside the logo/name column instead of below it, wrapping
                // this row (e.g. in fullscreen, where both the logo and the
                // `text-score-lg` digits are largest) pushed the score onto
                // a second line that the fullscreen container's fixed
                // height then clipped — the score simply wasn't visible.
                // Not wrapping keeps both columns on one line; `min-w-0` +
                // `truncate` on the name below is what lets it shrink
                // instead.
                'flex flex-1 flex-nowrap items-center justify-center gap-2 overflow-hidden px-2 text-center sm:gap-4 sm:px-4',
                fullscreen ? 'py-8' : 'py-5',
                corner === 'red' && 'bg-red-500/5',
                corner === 'blue' && 'bg-blue-500/5',
            )}
        >
            {/* Logo, then the team name below it — one column. */}
            <div
                className={cn(
                    'flex min-w-0 flex-col items-center gap-2',
                    logoAfterScore && 'order-1',
                )}
            >
                {corner && (
                    <Badge
                        className={cn(
                            'border-0 text-white',
                            corner === 'red' ? 'bg-red-600' : 'bg-blue-600',
                        )}
                    >
                        {corner === 'red' ? 'Red corner' : 'Blue corner'}
                    </Badge>
                )}

                {photoUrl ? (
                    <img
                        src={photoUrl}
                        alt={`Photo of ${label}`}
                        className={cn(
                            'rounded-full border-4 object-cover',
                            corner === 'red'
                                ? 'border-red-500'
                                : corner === 'blue'
                                  ? 'border-blue-500'
                                  : 'border-border',
                            fullscreen ? 'size-24' : 'size-20',
                        )}
                    />
                ) : (
                    <TeamLogo
                        name={label}
                        shape="circle"
                        className={cn(
                            fullscreen ? 'size-24 text-2xl' : 'size-20 text-xl',
                        )}
                    />
                )}

                <p className="max-w-28 truncate text-base font-medium text-muted-foreground sm:max-w-56 sm:text-lg">
                    {label}
                </p>
            </div>

            <div
                aria-hidden="true"
                className="h-24 shrink-0 border-l-2 border-slate-900/80 dark:border-slate-100/80"
            />

            {/* The score sits beside the logo/name column, not below it. */}
            <div
                className={cn(
                    'flex flex-col items-center gap-2',
                    logoAfterScore && '-order-1',
                )}
            >
                {/* `key={score}` remounts this element on every real score
                    change (same technique as `RunningClock`/`LastUpdated`
                    below), replaying `animate-score-pop` — WP-08.5-06's
                    "score-change emphasis." Plays once on first mount too,
                    which reads fine as part of the board's own entrance. */}
                <p
                    key={score}
                    className={cn(
                        fullscreen ? 'text-score-lg' : 'text-score',
                        'animate-score-pop',
                    )}
                >
                    {score}
                </p>

                {children}
            </div>
        </div>
    );
}

/**
 * The center "bug" between the two team panels — the running clock (see
 * `RunningClock`) plus the period/round label and any free-text status
 * note. Every board type gets the clock, not just basketball — a real
 * elapsed-time readout is useful regardless of sport, and keeping one
 * shared center panel (rather than a basketball-only one) is simpler than
 * branching the whole scoreboard layout per sport.
 */
/** A sport-specific manual countdown to show instead of the generic
 * elapsed-since-start stopwatch — basketball's game clock or boxing's
 * round/rest clock. `null` falls back to `RunningClock`. */
type Countdown = { seconds: number; anchor: string | null; label: string };

function CenterPanel({
    session,
    periodLabel,
    statusNote,
    fullscreen,
    countdown,
}: {
    session: LiveSession;
    periodLabel: string | null;
    statusNote: string | null;
    fullscreen: boolean;
    countdown: Countdown | null;
}) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center gap-1 bg-muted/30 px-3 text-center',
                fullscreen ? 'py-8' : 'py-5',
            )}
        >
            {session.status === 'in_progress' ? (
                <LiveBadge label={session.status_label} />
            ) : (
                <Badge
                    variant={
                        session.status === 'ended' ? 'outline' : 'secondary'
                    }
                >
                    {session.status_label}
                </Badge>
            )}

            <p
                className={cn(
                    'text-clock',
                    // Matches `.text-score`/`.text-score-lg`'s own `sm:`
                    // step-up (WP-08.5-05) — a flat `text-6xl` clock in
                    // fullscreen would also squeeze the two score panels
                    // on a phone, since this center column is `auto`-
                    // width and sizes to its own content. Bumped up a
                    // step (owner instruction) — the clock is the single
                    // most-glanced-at number on the board.
                    fullscreen
                        ? 'text-5xl sm:text-7xl'
                        : 'text-4xl sm:text-5xl',
                )}
                aria-label={countdown?.label ?? 'Running time'}
            >
                {countdown ? (
                    <CountdownClock
                        baseSeconds={countdown.seconds}
                        anchor={countdown.anchor}
                        running={session.status === 'in_progress'}
                    />
                ) : (
                    <RunningClock session={session} />
                )}
            </p>
            {periodLabel && (
                <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                    {periodLabel}
                </p>
            )}
            {statusNote && (
                <p className="max-w-40 text-xs text-muted-foreground">
                    {statusNote}
                </p>
            )}
        </div>
    );
}

/**
 * The line-score grid (innings as columns, R as the final column) — a
 * real per-inning breakdown (`sport_state.innings`), not a fixed 7/9
 * -column layout: this app doesn't track a configured game length, so
 * only innings that have actually happened get a column, however many
 * that is. Each team's own generated logo sits next to its name, same
 * placeholder convention the rest of the scoreboard uses.
 */
function SoftballLineScore({
    session,
    state,
}: {
    session: LiveSession;
    state: SoftballState;
}) {
    if (state.innings.length === 0) {
        return null;
    }

    return (
        <div className="w-full overflow-x-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Team</TableHead>
                        {state.innings.map((inning) => (
                            <TableHead
                                key={inning.inning}
                                className="w-10 text-center"
                            >
                                {inning.inning}
                            </TableHead>
                        ))}
                        <TableHead className="w-10 text-center font-semibold">
                            R
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow>
                        <TableCell className="font-medium">
                            <span className="flex items-center gap-2">
                                <TeamLogo
                                    name={session.side_a_label}
                                    shape="square"
                                    className="size-6 text-[0.65rem]"
                                />
                                {session.side_a_label}
                            </span>
                        </TableCell>
                        {state.innings.map((inning) => (
                            <TableCell
                                key={inning.inning}
                                className="text-center tabular-nums"
                            >
                                {inning.runs_a}
                            </TableCell>
                        ))}
                        <TableCell className="text-center font-semibold tabular-nums">
                            {session.score_a}
                        </TableCell>
                    </TableRow>
                    <TableRow>
                        <TableCell className="font-medium">
                            <span className="flex items-center gap-2">
                                <TeamLogo
                                    name={session.side_b_label}
                                    shape="square"
                                    className="size-6 text-[0.65rem]"
                                />
                                {session.side_b_label}
                            </span>
                        </TableCell>
                        {state.innings.map((inning) => (
                            <TableCell
                                key={inning.inning}
                                className="text-center tabular-nums"
                            >
                                {inning.runs_b}
                            </TableCell>
                        ))}
                        <TableCell className="text-center font-semibold tabular-nums">
                            {session.score_b}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    );
}

/**
 * Boxing's round-by-round history as a real bordered table (matching the
 * scoreboard's overall "boxes and lines" treatment) instead of a plain
 * list — one row per judged round, 10-point-must scores for each corner.
 */
function BoxingRoundTable({
    session,
    state,
}: {
    session: LiveSession;
    state: BoxingState;
}) {
    if (state.rounds.length === 0) {
        return null;
    }

    return (
        <div className="w-full overflow-x-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead className="w-16">Round</TableHead>
                        <TableHead className="text-center text-red-600">
                            {session.side_a_label}
                        </TableHead>
                        <TableHead className="text-center text-blue-600">
                            {session.side_b_label}
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {state.rounds.map((round) => (
                        <TableRow key={round.round}>
                            <TableCell className="font-medium">
                                Round {round.round}
                            </TableCell>
                            <TableCell className="text-center font-semibold tabular-nums">
                                {round.score_a}
                            </TableCell>
                            <TableCell className="text-center font-semibold tabular-nums">
                                {round.score_b}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

/**
 * Volleyball/sepak takraw's completed-set history — one row per finished
 * set's final point score, the same "real, append-only history" treatment
 * as `BoxingRoundTable`. The live/in-progress set isn't a row here (it has
 * no final score yet) — shown separately above as "Set N — live score".
 */
function RallySetsHistoryTable({
    session,
    state,
}: {
    session: LiveSession;
    state: RallySetsState;
}) {
    return (
        <div className="w-full overflow-x-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead className="w-16">Set</TableHead>
                        <TableHead className="text-center">
                            {session.side_a_label}
                        </TableHead>
                        <TableHead className="text-center">
                            {session.side_b_label}
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {state.sets.map((set) => (
                        <TableRow key={set.set}>
                            <TableCell className="font-medium">
                                Set {set.set}
                            </TableCell>
                            <TableCell className="text-center font-semibold tabular-nums">
                                {set.score_a}
                            </TableCell>
                            <TableCell className="text-center font-semibold tabular-nums">
                                {set.score_b}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

/**
 * Table tennis/badminton's completed-game history — same "real,
 * append-only history" treatment as `RallySetsHistoryTable`, just with
 * this sport family's own "game" terminology instead of "set".
 */
function RacketGamesHistoryTable({
    session,
    state,
}: {
    session: LiveSession;
    state: RacketGamesState;
}) {
    return (
        <div className="w-full overflow-x-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead className="w-16">Game</TableHead>
                        <TableHead className="text-center">
                            {session.side_a_label}
                        </TableHead>
                        <TableHead className="text-center">
                            {session.side_b_label}
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {state.games.map((game) => (
                        <TableRow key={game.game}>
                            <TableCell className="font-medium">
                                Game {game.game}
                            </TableCell>
                            <TableCell className="text-center font-semibold tabular-nums">
                                {game.score_a}
                            </TableCell>
                            <TableCell className="text-center font-semibold tabular-nums">
                                {game.score_b}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

/**
 * Tennis's completed-set history — same "real, append-only history"
 * treatment as `RallySetsHistoryTable`/`RacketGamesHistoryTable`, with
 * this sport's own "set" terminology (games won within that set, not
 * points — a completed tennis set is always recorded as its final game
 * count, e.g. 6-4 or 7-6).
 */
function TennisSetsHistoryTable({
    session,
    state,
}: {
    session: LiveSession;
    state: TennisState;
}) {
    return (
        <div className="w-full overflow-x-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead className="w-16">Set</TableHead>
                        <TableHead className="text-center">
                            {session.side_a_label}
                        </TableHead>
                        <TableHead className="text-center">
                            {session.side_b_label}
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {state.sets.map((set) => (
                        <TableRow key={set.set}>
                            <TableCell className="font-medium">
                                Set {set.set}
                            </TableCell>
                            <TableCell className="text-center font-semibold tabular-nums">
                                {set.score_a}
                            </TableCell>
                            <TableCell className="text-center font-semibold tabular-nums">
                                {set.score_b}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

/**
 * Billiard's completed-rack history — one row per rack played, just the
 * declared winner (this app doesn't model individual balls/shots, so
 * there's no per-rack score to show, unlike every other history table
 * in this file).
 */
function BilliardRackHistoryTable({
    session,
    state,
}: {
    session: LiveSession;
    state: BilliardState;
}) {
    return (
        <div className="w-full overflow-x-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead className="w-16">Rack</TableHead>
                        <TableHead>Winner</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {state.racks
                        .slice()
                        .reverse()
                        .map((rack) => (
                            <TableRow key={rack.rack}>
                                <TableCell className="font-medium">
                                    Rack {rack.rack}
                                </TableCell>
                                <TableCell>
                                    {rack.winner === 'a'
                                        ? session.side_a_label
                                        : session.side_b_label}
                                </TableCell>
                            </TableRow>
                        ))}
                </TableBody>
            </Table>
        </div>
    );
}

/**
 * Bocce's completed-end history — one row per end, the side that won it
 * and how many points that was worth (a real end always awards points to
 * exactly one side, the other scores 0 — see
 * `ScoringSessionController::bocceEnd()`).
 */
function BocceEndHistoryTable({
    session,
    state,
}: {
    session: LiveSession;
    state: BocceState;
}) {
    return (
        <div className="w-full overflow-x-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead className="w-16">End</TableHead>
                        <TableHead>Winner</TableHead>
                        <TableHead className="text-center">Points</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {state.ends
                        .slice()
                        .reverse()
                        .map((end) => (
                            <TableRow key={end.end}>
                                <TableCell className="font-medium">
                                    End {end.end}
                                </TableCell>
                                <TableCell>
                                    {end.winner === 'a'
                                        ? session.side_a_label
                                        : session.side_b_label}
                                </TableCell>
                                <TableCell className="text-center font-semibold tabular-nums">
                                    {end.points}
                                </TableCell>
                            </TableRow>
                        ))}
                </TableBody>
            </Table>
        </div>
    );
}

/**
 * A manual score correction (delta + required reason) for one side —
 * shared by the operator console's generic controls and
 * `BasketballGameControl`'s team-level correction, so the two never drift.
 */
export function CorrectionDialog({
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

/**
 * The live play-by-play feed — extracted from `LiveScoreDisplay` so the
 * operator console can position it independently of the score card (e.g.
 * below its own operator-controls block) instead of always at the bottom
 * of the shared score display. `LiveScoreDisplay` still renders this
 * itself by default (`hidePlayByPlay` prop) for every caller that doesn't
 * need to reposition it — the public/legacy scoreboard pages.
 */
export function PlayByPlayList({
    playByPlay,
    onRemove,
    scrollable = false,
}: {
    playByPlay: PlayByPlayEntry[];
    onRemove?: (id: number) => void;
    scrollable?: boolean;
}) {
    const [showAllPlays, setShowAllPlays] = useState(false);
    const visiblePlays = scrollable
        ? playByPlay
        : showAllPlays
        ? playByPlay
        : playByPlay.slice(0, PLAY_BY_PLAY_PREVIEW_COUNT);

    if (playByPlay.length === 0) {
        return null;
    }

    return (
        <div className="w-full">
            <p className="mb-2 text-sm font-medium text-muted-foreground">
                Live play by play
            </p>
            <ul
                className={cn(
                    'divide-y rounded-lg border text-sm',
                    scrollable && 'max-h-[15rem] overflow-y-auto',
                )}
            >
                {visiblePlays.map((play) => (
                    <li
                        key={play.id}
                        className={cn(
                            'flex items-center justify-between gap-3 px-3 py-2',
                            scrollable && 'h-12',
                        )}
                    >
                        <span className="w-16 shrink-0 text-xs text-muted-foreground tabular-nums">
                            {play.created_at}
                        </span>
                        <span className="flex-1">{play.description}</span>
                        <span className="shrink-0 font-medium tabular-nums">
                            {play.score_a} – {play.score_b}
                        </span>
                        {onRemove && play.removable && (
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-8 shrink-0 text-destructive hover:text-destructive"
                                aria-label={`Remove ${play.description}`}
                                onClick={() => onRemove(play.id)}
                            >
                                <Trash2 aria-hidden="true" className="size-4" />
                            </Button>
                        )}
                    </li>
                ))}
            </ul>
            {!scrollable &&
                !showAllPlays &&
                playByPlay.length > PLAY_BY_PLAY_PREVIEW_COUNT && (
                    <Button
                        variant="link"
                        className="mt-1 h-auto p-0"
                        onClick={() => setShowAllPlays(true)}
                    >
                        View full play by play ({playByPlay.length} events) →
                    </Button>
                )}
        </div>
    );
}

/**
 * The read-only running score, status, and sport-specific breakdown — the
 * one presentation shared by the operator console (`scoring/show.tsx`) and
 * the public scoreboard (`public/scoreboard.tsx`). Purely presentational:
 * each page still fetches and shapes its own props (public pages never
 * reuse an internal page's props — see docs/public-portal.md), only this
 * rendering is shared, to avoid the two views drifting apart over time.
 */
export function LiveScoreDisplay({
    session,
    fullscreen,
    onToggleFullscreen,
    disconnected = false,
    lastUpdatedAt = null,
    showFullscreenToggle = true,
    maxWidthClassName = 'max-w-5xl',
    participants,
    hidePlayByPlay = false,
}: {
    session: LiveSession;
    fullscreen: boolean;
    onToggleFullscreen: () => void;
    /** True once polling has failed several times in a row (WP-08-10) —
     * the score shown may be stale; the page keeps retrying on its own,
     * no user action needed. */
    disconnected?: boolean;
    /** `Date.now()` at the caller's last successful poll or Echo push
     * (WP-08.5-04) — when to omit, pass `null` rather than leaving it
     * unset from a component that has no notion of freshness. */
    lastUpdatedAt?: number | null;
    /** Hide the manual full-screen toggle (WP-08.5-07's kiosk mode,
     * where `fullscreen` is always forced on and there's no benefit —
     * or expectation of anyone — clicking a toggle on an unattended
     * TV/LED wall). Every other caller keeps the default. */
    showFullscreenToggle?: boolean;
    /** The board's cap on fullscreen width (WP-08.5-07) — the default
     * `max-w-5xl` fits a normal browser window; a genuine 16:9 kiosk/TV
     * display should fill much more of the frame, so kiosk mode passes
     * a wider value (or `''` for no cap) instead. */
    maxWidthClassName?: string;
    /** Boxing's real boxer photos — internal operator console only, never
     * present on the public scoreboard (photos are never public). */
    participants?: [Participant | null, Participant | null];
    /** Suppress the embedded play-by-play feed (default: shown here) — the
     * operator console renders its own `<PlayByPlayList>` after its
     * controls block instead, so score/controls/plays land in that order
     * rather than always having plays directly under the score card. */
    hidePlayByPlay?: boolean;
}) {
    const basketballState = isBasketballState(session.sport_state)
        ? session.sport_state
        : null;
    const boxingState = isBoxingState(session.sport_state)
        ? session.sport_state
        : null;
    const softballState = isSoftballState(session.sport_state)
        ? session.sport_state
        : null;
    const rallySetsState = isRallySetsState(session.sport_state)
        ? session.sport_state
        : null;
    const footballState = isFootballState(session.sport_state)
        ? session.sport_state
        : null;
    const racketGamesState = isRacketGamesState(session.sport_state)
        ? session.sport_state
        : null;
    const wrestlingState = isWrestlingState(session.sport_state)
        ? session.sport_state
        : null;
    const tennisState = isTennisState(session.sport_state)
        ? session.sport_state
        : null;
    const goalBallState = isGoalBallState(session.sport_state)
        ? session.sport_state
        : null;
    const billiardState = isBilliardState(session.sport_state)
        ? session.sport_state
        : null;
    const bocceState = isBocceState(session.sport_state)
        ? session.sport_state
        : null;
    const cornerA: Corner = boxingState ? 'red' : null;
    const cornerB: Corner = boxingState ? 'blue' : null;
    const countdown: Countdown | null = basketballState
        ? {
              seconds: basketballState.game_clock_seconds,
              anchor: basketballState.game_clock_updated_at,
              label: 'Game clock',
          }
        : boxingState
          ? {
                seconds: boxingState.clock_seconds,
                anchor: boxingState.clock_updated_at,
                label:
                    boxingState.clock_phase === 'round'
                        ? 'Round clock'
                        : 'Rest clock',
            }
          : wrestlingState
            ? {
                  seconds: wrestlingState.clock_seconds,
                  anchor: wrestlingState.clock_updated_at,
                  label:
                      wrestlingState.clock_phase === 'period'
                          ? 'Period clock'
                          : 'Rest clock',
              }
            : null;

    return (
        <>
            <div className="flex items-center justify-end gap-3">
                <LastUpdated at={lastUpdatedAt} />
                {showFullscreenToggle && (
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={onToggleFullscreen}
                    >
                        {fullscreen ? (
                            <Minimize2 aria-hidden="true" />
                        ) : (
                            <Maximize2 aria-hidden="true" />
                        )}
                        {fullscreen ? 'Exit full screen' : 'Full screen'}
                    </Button>
                )}
            </div>

            {disconnected && (
                // The message text no longer sets `text-warning` (WP-
                // 08.5-09) — measured contrast of `--warning` text against
                // this tinted background at only ~2.1:1 in light mode,
                // well under WCAG AA's 4.5:1 (and `--warning-foreground`,
                // the token that looks like the fix, actually measures
                // ~1.05:1 in dark mode — it's designed to pair with a
                // *solid* `bg-warning` fill, not this 10%-tint style).
                // Leaving the text at the inherited default `foreground`
                // color measures ~18:1 (light) / ~16:1 (dark) against this
                // same tint — the ordinary body-text pairing is simply
                // correct here; only the icon stays warning-colored
                // (decorative, `aria-hidden`, no contrast requirement).
                <div
                    role="status"
                    className="flex items-center justify-center gap-2 rounded-md bg-warning/10 px-3 py-2 text-sm"
                >
                    <WifiOff
                        aria-hidden="true"
                        className="size-4 text-warning"
                    />
                    Connection lost — retrying automatically. Scores shown may
                    be out of date.
                </div>
            )}

            <div
                aria-live="polite"
                aria-atomic="true"
                className={cn(
                    'w-full overflow-hidden rounded-2xl border-2 bg-card shadow-sm',
                    fullscreen && cn('mx-auto', maxWidthClassName),
                )}
            >
                <div className="h-1.5 w-full bg-gradient-to-r from-sidebar to-primary" />
                <div className="grid grid-cols-[1fr_auto_1fr] divide-x-2">
                    <TeamPanel
                        label={session.side_a_label}
                        score={session.score_a}
                        fullscreen={fullscreen}
                        corner={cornerA}
                        photoUrl={participants?.[0]?.photo_url}
                    >
                        {basketballState && (
                            <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <CountDots
                                    count={basketballState.fouls_a}
                                    max={BASKETBALL_BONUS_THRESHOLD}
                                    colorClass="bg-destructive"
                                />
                                Fouls: {basketballState.fouls_a}
                                {basketballState.fouls_a >=
                                    BASKETBALL_BONUS_THRESHOLD && (
                                    <Badge
                                        variant="destructive"
                                        className="ml-1"
                                    >
                                        Bonus
                                    </Badge>
                                )}
                            </span>
                        )}
                        {rallySetsState && (
                            <span className="text-xs text-muted-foreground">
                                Set score: {rallySetsState.current_set_score_a}
                            </span>
                        )}
                        {footballState && (
                            <CardTally
                                yellow={footballState.yellow_cards_a}
                                red={footballState.red_cards_a}
                            />
                        )}
                        {racketGamesState && (
                            <span className="text-xs text-muted-foreground">
                                Game score:{' '}
                                {racketGamesState.current_game_score_a}
                            </span>
                        )}
                        {tennisState && (
                            <span className="text-xs text-muted-foreground">
                                Games: {tennisState.current_set_games_a}
                            </span>
                        )}
                        {goalBallState && goalBallState.penalty_throws_a > 0 && (
                            <span className="text-xs text-muted-foreground">
                                Penalty throws: {goalBallState.penalty_throws_a}
                            </span>
                        )}
                    </TeamPanel>

                    <CenterPanel
                        session={session}
                        periodLabel={session.period_label}
                        statusNote={session.status_note}
                        fullscreen={fullscreen}
                        countdown={countdown}
                    />

                    <TeamPanel
                        label={session.side_b_label}
                        score={session.score_b}
                        fullscreen={fullscreen}
                        corner={cornerB}
                        photoUrl={participants?.[1]?.photo_url}
                        logoAfterScore
                    >
                        {basketballState && (
                            <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <CountDots
                                    count={basketballState.fouls_b}
                                    max={BASKETBALL_BONUS_THRESHOLD}
                                    colorClass="bg-destructive"
                                />
                                Fouls: {basketballState.fouls_b}
                                {basketballState.fouls_b >=
                                    BASKETBALL_BONUS_THRESHOLD && (
                                    <Badge
                                        variant="destructive"
                                        className="ml-1"
                                    >
                                        Bonus
                                    </Badge>
                                )}
                            </span>
                        )}
                        {rallySetsState && (
                            <span className="text-xs text-muted-foreground">
                                Set score: {rallySetsState.current_set_score_b}
                            </span>
                        )}
                        {footballState && (
                            <CardTally
                                yellow={footballState.yellow_cards_b}
                                red={footballState.red_cards_b}
                            />
                        )}
                        {racketGamesState && (
                            <span className="text-xs text-muted-foreground">
                                Game score:{' '}
                                {racketGamesState.current_game_score_b}
                            </span>
                        )}
                        {tennisState && (
                            <span className="text-xs text-muted-foreground">
                                Games: {tennisState.current_set_games_b}
                            </span>
                        )}
                        {goalBallState && goalBallState.penalty_throws_b > 0 && (
                            <span className="text-xs text-muted-foreground">
                                Penalty throws: {goalBallState.penalty_throws_b}
                            </span>
                        )}
                    </TeamPanel>
                </div>
            </div>

            {wrestlingState && wrestlingState.fall_side && (
                <div
                    role="status"
                    className="flex items-center justify-center gap-2 rounded-md bg-destructive/10 px-3 py-2 text-sm font-semibold text-destructive"
                >
                    FALL —{' '}
                    {wrestlingState.fall_side === 'a'
                        ? session.side_a_label
                        : session.side_b_label}
                </div>
            )}

            {boxingState && boxingState.rounds.length > 0 && (
                <div className="mx-auto w-full max-w-md">
                    <p className="mb-2 text-center text-sm font-medium text-muted-foreground">
                        Round-by-round ({boxingState.rounds.length} of{' '}
                        {boxingState.total_rounds})
                    </p>
                    <div className="overflow-hidden rounded-xl border">
                        <BoxingRoundTable
                            session={session}
                            state={boxingState}
                        />
                    </div>
                </div>
            )}

            {softballState && (
                <div className="flex w-full flex-col items-center gap-3">
                    <p className="text-center text-sm font-medium text-muted-foreground">
                        Inning {softballState.inning} of{' '}
                        {softballState.innings_scheduled} (
                        {softballState.half === 'top' ? 'Top' : 'Bottom'})
                    </p>
                    <div className="flex flex-wrap items-center justify-center gap-x-8 gap-y-2 text-center text-sm text-muted-foreground">
                        <span className="flex items-center gap-2">
                            Balls:
                            <CountDots
                                count={softballState.balls}
                                max={SOFTBALL_BALLS_DISPLAY_MAX}
                                colorClass="bg-success"
                            />
                            {softballState.balls}
                        </span>
                        <span className="flex items-center gap-2">
                            Strikes:
                            <CountDots
                                count={softballState.strikes}
                                max={SOFTBALL_STRIKES_DISPLAY_MAX}
                                colorClass="bg-warning"
                            />
                            {softballState.strikes}
                        </span>
                        <span className="flex items-center gap-2">
                            Outs:
                            <CountDots
                                count={softballState.outs}
                                max={SOFTBALL_OUTS_DISPLAY_MAX}
                                colorClass="bg-destructive"
                            />
                            {softballState.outs}
                        </span>
                    </div>
                    <div className="overflow-hidden rounded-xl border">
                        <SoftballLineScore
                            session={session}
                            state={softballState}
                        />
                    </div>
                </div>
            )}

            {rallySetsState && (
                <div className="flex w-full flex-col items-center gap-3">
                    <p className="text-center text-sm font-medium text-muted-foreground">
                        Set {rallySetsState.sets.length + 1} — live score{' '}
                        {rallySetsState.current_set_score_a}–
                        {rallySetsState.current_set_score_b}
                        {rallySetsState.possession && (
                            <>
                                {' · Serving: '}
                                {rallySetsState.possession === 'a'
                                    ? session.side_a_label
                                    : session.side_b_label}
                            </>
                        )}
                    </p>
                    {rallySetsState.sets.length > 0 && (
                        <div className="overflow-hidden rounded-xl border">
                            <RallySetsHistoryTable
                                session={session}
                                state={rallySetsState}
                            />
                        </div>
                    )}
                </div>
            )}

            {racketGamesState && (
                <div className="flex w-full flex-col items-center gap-3">
                    <p className="text-center text-sm font-medium text-muted-foreground">
                        Game {racketGamesState.games.length + 1} — live score{' '}
                        {racketGamesState.current_game_score_a}–
                        {racketGamesState.current_game_score_b}
                        {racketGamesState.possession && (
                            <>
                                {' · Serving: '}
                                {racketGamesState.possession === 'a'
                                    ? session.side_a_label
                                    : session.side_b_label}
                            </>
                        )}
                    </p>
                    {racketGamesState.games.length > 0 && (
                        <div className="overflow-hidden rounded-xl border">
                            <RacketGamesHistoryTable
                                session={session}
                                state={racketGamesState}
                            />
                        </div>
                    )}
                </div>
            )}

            {tennisState && (
                <div className="flex w-full flex-col items-center gap-3">
                    <p className="text-center text-sm font-medium text-muted-foreground">
                        Set {tennisState.sets.length + 1} — Games{' '}
                        {tennisState.current_set_games_a}–
                        {tennisState.current_set_games_b}
                        {tennisState.is_tiebreak ? (
                            <>
                                {' · Tiebreak '}
                                {tennisState.tiebreak_points_a}–
                                {tennisState.tiebreak_points_b}
                            </>
                        ) : (
                            <>
                                {' · '}
                                {formatTennisPoints(
                                    tennisState.current_game_points_a,
                                    tennisState.current_game_points_b,
                                    session.side_a_label,
                                    session.side_b_label,
                                )}
                            </>
                        )}
                        {tennisState.possession && (
                            <>
                                {' · Serving: '}
                                {tennisState.possession === 'a'
                                    ? session.side_a_label
                                    : session.side_b_label}
                            </>
                        )}
                    </p>
                    {tennisState.sets.length > 0 && (
                        <div className="overflow-hidden rounded-xl border">
                            <TennisSetsHistoryTable
                                session={session}
                                state={tennisState}
                            />
                        </div>
                    )}
                </div>
            )}

            {billiardState && (
                <div className="flex w-full flex-col items-center gap-3">
                    <p className="text-center text-sm font-medium text-muted-foreground">
                        Race to {billiardState.racks_to_win} racks — Rack{' '}
                        {billiardState.racks.length + 1} in progress
                    </p>
                    {billiardState.racks.length > 0 && (
                        <div className="mx-auto w-full max-w-md overflow-hidden rounded-xl border">
                            <BilliardRackHistoryTable
                                session={session}
                                state={billiardState}
                            />
                        </div>
                    )}
                </div>
            )}

            {bocceState && (
                <div className="flex w-full flex-col items-center gap-3">
                    <p className="text-center text-sm font-medium text-muted-foreground">
                        First to {bocceState.target_score} points — End{' '}
                        {bocceState.ends_played + 1} in progress
                    </p>
                    {bocceState.ends.length > 0 && (
                        <div className="mx-auto w-full max-w-md overflow-hidden rounded-xl border">
                            <BocceEndHistoryTable
                                session={session}
                                state={bocceState}
                            />
                        </div>
                    )}
                </div>
            )}

            {!hidePlayByPlay && (
                <PlayByPlayList playByPlay={session.playByPlay} />
            )}
        </>
    );
}
