import { CalendarDays, MapPin } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import { formatTime } from '@/lib/format-time';

export type SportPortalGame = {
    id: number;
    round_label: string;
    status: string;
    status_label: string;
    category: string;
    venue: string | null;
    scheduled_date: string | null;
    starts_at: string | null;
    side_a: string | null;
    side_b: string | null;
    score_a: number | null;
    score_b: number | null;
    /** Real recorded mark/time (Athletics/Swimming only — individual
     * events have no `side_b`/numeric score, so their one real result
     * is a mark, not a score pair). `null` for every match-based sport
     * and for any event with no validated result yet. */
    mark: string | null;
};

type Props = {
    games: SportPortalGame[];
    emptyTitle: string;
    emptyDescription: string;
    /** Show the score line instead of a plain "vs" — Today's and
     * Completed games may have a real score; Upcoming games never do. */
    showScore?: boolean;
};

/**
 * Shared, compact game-card list (Phase 12) — reused for Today's,
 * Completed, and Upcoming games on every sport-portal page, driven
 * entirely by real data: `EventMatch`/`ScoringSession` for match-based
 * sports (`PortalController::sportPortalGameRow()`), or `EventSchedule`/
 * `EventResult` for Athletics/Swimming's own individual-event shape
 * (`individualEventGameRow()`, WP-12-05) — never fabricated either way.
 */
export function SportPortalGameList({
    games,
    emptyTitle,
    emptyDescription,
    showScore = false,
}: Props) {
    if (games.length === 0) {
        return (
            <EmptyState
                icon={CalendarDays}
                title={emptyTitle}
                description={emptyDescription}
            />
        );
    }

    return (
        <ul className="flex flex-col gap-2">
            {games.map((game) => (
                <li
                    key={game.id}
                    className="flex flex-col gap-2 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div className="flex flex-col gap-1">
                        {game.side_a !== null && (
                            <div className="flex flex-wrap items-center gap-2 text-sm font-medium">
                                {game.side_b === null ? (
                                    // Individual event (Athletics/
                                    // Swimming) — one real result, no
                                    // opposing side, so no "vs" line.
                                    <>
                                        <span>{game.side_a}</span>
                                        {game.mark && (
                                            <span className="text-muted-foreground tabular-nums">
                                                {game.mark}
                                            </span>
                                        )}
                                    </>
                                ) : (
                                    <>
                                        <span>{game.side_a}</span>
                                        {showScore &&
                                        game.score_a !== null &&
                                        game.score_b !== null ? (
                                            <span className="text-muted-foreground tabular-nums">
                                                {game.score_a} – {game.score_b}
                                            </span>
                                        ) : (
                                            <span className="text-muted-foreground">
                                                vs
                                            </span>
                                        )}
                                        <span>{game.side_b}</span>
                                    </>
                                )}
                            </div>
                        )}
                        <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                            <span>{game.category}</span>
                            <span aria-hidden="true">·</span>
                            <span>{game.round_label}</span>
                            {game.venue && (
                                <span className="flex items-center gap-1">
                                    <MapPin
                                        aria-hidden="true"
                                        className="size-3"
                                    />
                                    {game.venue}
                                </span>
                            )}
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        {game.starts_at && (
                            <span className="text-xs text-muted-foreground">
                                {game.scheduled_date
                                    ? `${game.scheduled_date}, `
                                    : ''}
                                {formatTime(game.starts_at)}
                            </span>
                        )}
                        <Badge variant="outline">{game.status_label}</Badge>
                    </div>
                </li>
            ))}
        </ul>
    );
}
