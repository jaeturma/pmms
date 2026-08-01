import { Link } from '@inertiajs/react';
import { Clock, MapPin } from 'lucide-react';
import { MunicipalityCrest, MunicipalityWatermark } from '@/apps/portal/components/municipality-crest';
import { cn } from '@/apps/portal/lib/utils';
import type { PortalGame } from '@/apps/portal/types';

type PortalMatchCardProps = {
    game: PortalGame;
    showScore?: boolean;
    className?: string;
    /** When set, the whole card becomes a link (e.g. to that game's
     * result/play-by-play detail) — omitted, the card stays a plain,
     * unlinked summary (today's/upcoming games). */
    href?: string;
};

const statusToneClasses: Record<string, string> = {
    completed: 'bg-[var(--portal-muted)] text-[var(--portal-muted-foreground)]',
    walkover: 'bg-[var(--portal-muted)] text-[var(--portal-muted-foreground)]',
    scheduled: 'bg-[var(--portal-accent-soft)] text-[var(--portal-accent)]',
};

export function PortalMatchCard({ game, showScore = false, className, href }: PortalMatchCardProps) {
    const toneClass = statusToneClasses[game.status] ?? statusToneClasses.scheduled;
    const Container = href ? Link : 'div';

    return (
        <Container
            {...(href ? { href } : {})}
            className={cn(
                'portal-animate-in relative overflow-hidden rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4 text-[var(--portal-surface-foreground)]',
                href && 'block transition-colors hover:border-[var(--portal-accent)] hover:bg-[var(--portal-muted)]',
                className,
            )}
        >
            {game.side_a && <MunicipalityWatermark name={game.side_a} className="-top-4 -right-4 size-24 text-5xl" />}

            <div className="relative flex items-center justify-between gap-2">
                <span className={cn('rounded-full px-2 py-0.5 text-xs font-medium', toneClass)}>{game.status_label}</span>
                {game.round_label && <span className="text-xs text-[var(--portal-muted-foreground)]">{game.round_label}</span>}
            </div>

            <div className="relative mt-3 flex items-center justify-between gap-3">
                <div className="min-w-0 flex-1 space-y-1.5">
                    <span className="flex min-w-0 items-center gap-2">
                        {game.side_a && <MunicipalityCrest name={game.side_a} size="sm" />}
                        <span className="truncate text-sm font-medium">{game.side_a ?? 'TBD'}</span>
                    </span>
                    <span className="flex min-w-0 items-center gap-2">
                        {game.side_b && <MunicipalityCrest name={game.side_b} size="sm" />}
                        <span className="truncate text-sm font-medium text-[var(--portal-muted-foreground)]">{game.side_b ?? 'TBD'}</span>
                    </span>
                </div>
                {showScore && (game.score_a !== null || game.score_b !== null) && (
                    <div className="text-right tabular-nums">
                        <p className="text-lg font-bold">{game.score_a ?? '–'}</p>
                        <p className="text-lg font-bold">{game.score_b ?? '–'}</p>
                    </div>
                )}
                {game.mark && <p className="text-sm font-semibold text-[var(--portal-accent)]">{game.mark}</p>}
            </div>

            <div className="relative mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-[var(--portal-muted-foreground)]">
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
        </Container>
    );
}
