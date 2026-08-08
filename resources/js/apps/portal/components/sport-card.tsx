import { Link } from '@inertiajs/react';
import { Radio } from 'lucide-react';
import { PortalSportIcon } from '@/apps/portal/components/sport-icon';
import type { PortalSportCard as PortalSportCardData } from '@/apps/portal/types';
import { sportPortal } from '@/routes/public';

/**
 * One large, colorful sport card on the `/sports-directory` browse page —
 * icon, name, short description, category count, and a "View Sport →"
 * link out to the sport's own permanent mini portal. Deliberately not
 * dashboard-looking: no tables, no numeric-only stat grid, one clear
 * action per card.
 */
export function PortalSportCard({ sport }: { sport: PortalSportCardData }) {
    return (
        <Link
            href={sportPortal(sport.slug).url}
            className="group flex flex-col gap-3 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-5 transition-shadow hover:shadow-md focus-visible:ring-2 focus-visible:ring-[var(--portal-accent)] focus-visible:outline-none"
        >
            <div className="flex items-start justify-between gap-3">
                <PortalSportIcon slug={sport.slug} className="size-14 shrink-0 [&>svg]:size-7" />
                {sport.is_live && (
                    <span className="inline-flex items-center gap-1 rounded-full bg-[var(--portal-live)] px-2.5 py-1 text-xs font-bold text-[var(--portal-live-foreground)]">
                        <Radio aria-hidden="true" className="size-3" />
                        LIVE
                    </span>
                )}
            </div>

            <div>
                <h3 className="text-lg font-bold text-[var(--portal-fg)]">{sport.name}</h3>
                {sport.short_description && <p className="mt-1 text-sm text-[var(--portal-muted-foreground)]">{sport.short_description}</p>}
            </div>

            <div className="mt-auto flex items-center justify-between pt-2 text-sm">
                <span className="text-[var(--portal-muted-foreground)]">
                    {sport.category_count === 0 ? 'No categories yet' : `${sport.category_count} ${sport.category_count === 1 ? 'category' : 'categories'}`}
                </span>
                <span className="font-semibold text-[var(--portal-accent)] group-hover:underline">View Sport →</span>
            </div>
        </Link>
    );
}
