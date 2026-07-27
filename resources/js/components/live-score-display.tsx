import { Maximize2, Minimize2, WifiOff } from 'lucide-react';
import { useState } from 'react';
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
};

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

/** `count` filled dots out of `max` — a real count (fouls, balls,
 * strikes, outs), just rendered as dots instead of a bare number,
 * matching the reference's visual language. `count` can exceed `max`
 * (e.g. fouls past the bonus threshold) — every dot just stays filled. */
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

/**
 * The line-score grid (innings as columns, R as the final column) — a
 * real per-inning breakdown (`sport_state.innings`), not a fixed 7/9
 * -column layout: this app doesn't track a configured game length, so
 * only innings that have actually happened get a column, however many
 * that is.
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
                            {session.side_a_label}
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
                            {session.side_b_label}
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
}: {
    session: LiveSession;
    fullscreen: boolean;
    onToggleFullscreen: () => void;
    /** True once polling has failed several times in a row (WP-08-10) —
     * the score shown may be stale; the page keeps retrying on its own,
     * no user action needed. */
    disconnected?: boolean;
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
                className={
                    fullscreen
                        ? 'grid w-full grid-cols-2 gap-8 text-center'
                        : 'grid grid-cols-2 gap-4 text-center'
                }
            >
                <div>
                    <p className="truncate text-lg font-medium text-muted-foreground">
                        {session.side_a_label}
                    </p>
                    <p
                        className={
                            fullscreen
                                ? 'text-9xl font-bold tabular-nums'
                                : 'text-6xl font-bold tabular-nums'
                        }
                    >
                        {session.score_a}
                    </p>
                </div>
                <div>
                    <p className="truncate text-lg font-medium text-muted-foreground">
                        {session.side_b_label}
                    </p>
                    <p
                        className={
                            fullscreen
                                ? 'text-9xl font-bold tabular-nums'
                                : 'text-6xl font-bold tabular-nums'
                        }
                    >
                        {session.score_b}
                    </p>
                </div>
            </div>

            {(session.period_label || session.status_note) && (
                <p className="text-center text-muted-foreground">
                    {[session.period_label, session.status_note]
                        .filter(Boolean)
                        .join(' · ')}
                </p>
            )}

            {basketballState && (
                <div className="flex flex-wrap items-center justify-center gap-x-8 gap-y-2 text-center text-sm text-muted-foreground">
                    <span className="flex items-center gap-2">
                        Team fouls — {session.side_a_label}:
                        <CountDots
                            count={basketballState.fouls_a}
                            max={BASKETBALL_BONUS_THRESHOLD}
                            colorClass="bg-destructive"
                        />
                        {basketballState.fouls_a}
                        {basketballState.fouls_a >=
                            BASKETBALL_BONUS_THRESHOLD && (
                            <Badge variant="destructive">Bonus</Badge>
                        )}
                    </span>
                    <span className="flex items-center gap-2">
                        Team fouls — {session.side_b_label}:
                        <CountDots
                            count={basketballState.fouls_b}
                            max={BASKETBALL_BONUS_THRESHOLD}
                            colorClass="bg-destructive"
                        />
                        {basketballState.fouls_b}
                        {basketballState.fouls_b >=
                            BASKETBALL_BONUS_THRESHOLD && (
                            <Badge variant="destructive">Bonus</Badge>
                        )}
                    </span>
                </div>
            )}

            {boxingState && boxingState.rounds.length > 0 && (
                <div className="mx-auto w-full max-w-md">
                    <p className="mb-2 text-center text-sm font-medium text-muted-foreground">
                        Round-by-round
                    </p>
                    <ul className="flex flex-col gap-1 text-center text-sm text-muted-foreground">
                        {boxingState.rounds.map((round) => (
                            <li key={round.round}>
                                Round {round.round}: {round.score_a} –{' '}
                                {round.score_b}
                            </li>
                        ))}
                    </ul>
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
                    <SoftballLineScore
                        session={session}
                        state={softballState}
                    />
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
