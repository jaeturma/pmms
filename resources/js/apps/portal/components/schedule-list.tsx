import { CalendarClock } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalMatchCard } from '@/apps/portal/components/match-card';
import type { PortalGame } from '@/apps/portal/types';

type PortalScheduleListProps = {
    games: PortalGame[];
    emptyTitle: string;
    emptyDescription?: string;
    showScore?: boolean;
};

export function PortalScheduleList({ games, emptyTitle, emptyDescription, showScore = false }: PortalScheduleListProps) {
    if (games.length === 0) {
        return <PortalEmptyState icon={CalendarClock} title={emptyTitle} description={emptyDescription} />;
    }

    return (
        <div className="space-y-3">
            {games.map((game) => (
                <PortalMatchCard key={game.id} game={game} showScore={showScore} />
            ))}
        </div>
    );
}
