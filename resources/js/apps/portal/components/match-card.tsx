import { Clock, MapPin } from 'lucide-react';
import { cn } from '@/apps/portal/lib/utils';
import type { PortalGame } from '@/apps/portal/types';

type PortalMatchCardProps = {
    game: PortalGame;
    showScore?: boolean;
    className?: string;
};

const statusToneClasses: Record<string, string> = {
    completed: 'bg-[var(--portal-muted)] text-[var(--portal-muted-foreground)]',
    walkover: 'bg-[var(--portal-muted)] text-[var(--portal-muted-foreground)]',
    scheduled: 'bg-[var(--portal-accent-soft)] text-[var(--portal-accent)]',
};

export function PortalMatchCard({ game, showScore = false, className }: PortalMatchCardProps) {
    const toneClass = statusToneClasses[game.status] ?? statusToneClasses.scheduled;

    return (
        <div
            className={cn(
                'portal-animate-in rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4 text-[var(--portal-surface-foreground)]',
                className,
            )}
        >
            <div className="flex items-center justify-between gap-2">
                <span className={cn('rounded-full px-2 py-0.5 text-xs font-medium', toneClass)}>{game.status_label}</span>
                {game.round_label && <span className="text-xs text-[var(--portal-muted-foreground)]">{game.round_label}</span>}
            </div>

            <div className="mt-3 flex items-center justify-between gap-3">
                <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-medium">{game.side_a ?? 'TBD'}</p>
                    <p className="truncate text-sm font-medium text-[var(--portal-muted-foreground)]">{game.side_b ?? 'TBD'}</p>
                </div>
                {showScore && (game.score_a !== null || game.score_b !== null) && (
                    <div className="text-right tabular-nums">
                        <p className="text-lg font-bold">{game.score_a ?? '–'}</p>
                        <p className="text-lg font-bold">{game.score_b ?? '–'}</p>
                    </div>
                )}
                {game.mark && <p className="text-sm font-semibold text-[var(--portal-accent)]">{game.mark}</p>}
            </div>

            <div className="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-[var(--portal-muted-foreground)]">
                <span>{game.category}</span>
                {game.scheduled_date && (
                    <span className="flex items-center gap-1">
                        <Clock aria-hidden="true" className="size-3.5" />
                        {game.scheduled_date}
                        {game.starts_at && ` · ${game.starts_at}`}
                    </span>
                )}
                {game.venue && (
                    <span className="flex items-center gap-1">
                        <MapPin aria-hidden="true" className="size-3.5" />
                        {game.venue}
                    </span>
                )}
            </div>
        </div>
    );
}
