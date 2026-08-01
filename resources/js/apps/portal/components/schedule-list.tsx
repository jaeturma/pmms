import { CalendarClock } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalMatchCard } from '@/apps/portal/components/match-card';
import type { PortalGame } from '@/apps/portal/types';

type PortalScheduleListProps = {
    games: PortalGame[];
    emptyTitle: string;
    emptyDescription?: string;
    showScore?: boolean;
    /** When set, each card links to that game's own detail page (e.g. a
     * completed game's result/play-by-play) — omitted, cards stay plain
     * summaries (the default, unchanged for today's/upcoming games). */
    linkTo?: (game: PortalGame) => string;
};

export function PortalScheduleList({ games, emptyTitle, emptyDescription, showScore = false, linkTo }: PortalScheduleListProps) {
    if (games.length === 0) {
        return <PortalEmptyState icon={CalendarClock} title={emptyTitle} description={emptyDescription} />;
    }

    return (
        <div className="space-y-3">
            {games.map((game) => (
                <PortalMatchCard key={game.id} game={game} showScore={showScore} href={linkTo?.(game)} />
            ))}
        </div>
    );
}
