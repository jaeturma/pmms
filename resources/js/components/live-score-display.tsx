import { Maximize2, Minimize2, WifiOff } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { TeamLogo } from '@/components/team-logo';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
};

export type BoxingRound = {
    round: number;
    score_a: number;
    score_b: number;
};

export type BoxingState = {
    rounds: BoxingRound[];
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
function useTicks(running: boolean): number {
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

function formatClock(totalSeconds: number): string {
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
function CountDots({
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
                'flex flex-1 flex-col items-center gap-2 px-4 text-center',
                fullscreen ? 'py-8' : 'py-5',
                corner === 'red' && 'bg-red-500/5',
                corner === 'blue' && 'bg-blue-500/5',
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
                        fullscreen ? 'size-32' : 'size-20',
                    )}
                />
            ) : (
                <TeamLogo
                    name={label}
                    shape="circle"
                    className={cn(
                        fullscreen ? 'size-32 text-3xl' : 'size-20 text-xl',
                    )}
                />
            )}

            <p className="w-full truncate text-lg font-medium text-muted-foreground">
                {label}
            </p>
            <p
                className={cn(
                    'font-bold tabular-nums',
                    fullscreen ? 'text-9xl' : 'text-6xl',
                )}
            >
                {score}
            </p>

            {children}
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
function CenterPanel({
    session,
    periodLabel,
    statusNote,
    fullscreen,
}: {
    session: LiveSession;
    periodLabel: string | null;
    statusNote: string | null;
    fullscreen: boolean;
}) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center gap-1 bg-muted/30 px-3 text-center',
                fullscreen ? 'py-8' : 'py-5',
            )}
        >
            <p
                className={cn(
                    'font-mono font-bold tabular-nums',
                    fullscreen ? 'text-6xl' : 'text-3xl',
                )}
                aria-label="Running time"
            >
                <RunningClock session={session} />
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
    participants,
}: {
    session: LiveSession;
    fullscreen: boolean;
    onToggleFullscreen: () => void;
    /** True once polling has failed several times in a row (WP-08-10) —
     * the score shown may be stale; the page keeps retrying on its own,
     * no user action needed. */
    disconnected?: boolean;
    /** Boxing's real boxer photos — internal operator console only, never
     * present on the public scoreboard (photos are never public). */
    participants?: [Participant | null, Participant | null];
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
    const [showAllPlays, setShowAllPlays] = useState(false);
    const visiblePlays = showAllPlays
        ? session.playByPlay
        : session.playByPlay.slice(0, PLAY_BY_PLAY_PREVIEW_COUNT);
    const cornerA: Corner = boxingState ? 'red' : null;
    const cornerB: Corner = boxingState ? 'blue' : null;

    return (
        <>
            <div className="flex items-center justify-between gap-2">
                <Badge
                    variant={
                        session.status === 'ended'
                            ? 'outline'
                            : session.status === 'paused'
                              ? 'secondary'
                              : 'default'
                    }
                >
                    {session.status_label}
                </Badge>
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
            </div>

            {disconnected && (
                <div
                    role="status"
                    className="flex items-center justify-center gap-2 rounded-md bg-warning/10 px-3 py-2 text-sm text-warning"
                >
                    <WifiOff aria-hidden="true" className="size-4" />
                    Connection lost — retrying automatically. Scores shown may
                    be out of date.
                </div>
            )}

            <div
                aria-live="polite"
                aria-atomic="true"
                className={cn(
                    'w-full overflow-hidden rounded-2xl border-2 bg-card shadow-sm',
                    fullscreen && 'mx-auto max-w-5xl',
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
                        Round-by-round
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
                        Inning {softballState.inning} (
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

            {session.playByPlay.length > 0 && (
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
                                <span className="flex-1">
                                    {play.description}
                                </span>
                                <span className="shrink-0 font-medium tabular-nums">
                                    {play.score_a} – {play.score_b}
                                </span>
                            </li>
                        ))}
                    </ul>
                    {!showAllPlays &&
                        session.playByPlay.length >
                            PLAY_BY_PLAY_PREVIEW_COUNT && (
                            <Button
                                variant="link"
                                className="mt-1 h-auto p-0"
                                onClick={() => setShowAllPlays(true)}
                            >
                                View full play by play (
                                {session.playByPlay.length} events) →
                            </Button>
                        )}
                </div>
            )}
        </>
    );
}
