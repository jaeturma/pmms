import { Maximize2, Minimize2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

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
};

const BASKETBALL_BONUS_THRESHOLD = 5;

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
}: {
    session: LiveSession;
    fullscreen: boolean;
    onToggleFullscreen: () => void;
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
                        Team fouls — {session.side_a_label}:{' '}
                        {basketballState.fouls_a}
                        {basketballState.fouls_a >=
                            BASKETBALL_BONUS_THRESHOLD && (
                            <Badge variant="destructive">Bonus</Badge>
                        )}
                    </span>
                    <span className="flex items-center gap-2">
                        Team fouls — {session.side_b_label}:{' '}
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
                <div className="flex flex-col items-center gap-2 text-center text-sm text-muted-foreground">
                    <p>
                        Inning {softballState.inning} (
                        {softballState.half === 'top' ? 'Top' : 'Bottom'}) ·{' '}
                        {softballState.outs} Out
                        {softballState.outs === 1 ? '' : 's'} · Count{' '}
                        {softballState.balls}-{softballState.strikes}
                    </p>
                    {softballState.innings.length > 0 && (
                        <ul className="flex flex-wrap justify-center gap-x-4 gap-y-1">
                            {softballState.innings.map((inn) => (
                                <li key={inn.inning}>
                                    Inn {inn.inning}: {inn.runs_a}-{inn.runs_b}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            )}
        </>
    );
}
