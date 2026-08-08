import { Maximize2, Minimize2, WifiOff } from 'lucide-react';
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
    board_type: 'generic' | 'basketball' | 'boxing' | 'softball_baseball';
    sport_state: BasketballState | BoxingState | SoftballState | null;
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
            running={running}
        />
    );
}

function TickingCountdown({
    baseSeconds,
    running,
}: {
    baseSeconds: number;
    running: boolean;
}) {
    const ticks = useTicks(running);

    return <>{formatClock(baseSeconds - ticks)}</>;
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
}: {
    label: string;
    score: number;
    fullscreen: boolean;
    corner: Corner;
    photoUrl?: string | null;
    children?: ReactNode;
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
            <div className="flex min-w-0 flex-col items-center gap-2">
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

            {/* The score sits beside the logo/name column, not below it. */}
            <div className="flex flex-col items-center gap-2">
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
}: {
    playByPlay: PlayByPlayEntry[];
}) {
    const [showAllPlays, setShowAllPlays] = useState(false);
    const visiblePlays = showAllPlays
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
            <ul className="divide-y rounded-lg border text-sm">
                {visiblePlays.map((play) => (
                    <li
                        key={play.id}
                        className="flex items-center justify-between gap-3 px-3 py-2"
                    >
                        <span className="w-16 shrink-0 text-xs text-muted-foreground tabular-nums">
                            {play.created_at}
                        </span>
                        <span className="flex-1">{play.description}</span>
                        <span className="shrink-0 font-medium tabular-nums">
                            {play.score_a} – {play.score_b}
                        </span>
                    </li>
                ))}
            </ul>
            {!showAllPlays &&
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
                    </TeamPanel>
                </div>
            </div>

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

            {!hidePlayByPlay && (
                <PlayByPlayList playByPlay={session.playByPlay} />
            )}
        </>
    );
}
