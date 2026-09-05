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
            className="group relative isolate flex min-h-52 flex-col gap-3 overflow-hidden rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-5 transition-[transform,box-shadow] hover:-translate-y-0.5 hover:shadow-md focus-visible:ring-2 focus-visible:ring-[var(--portal-accent)] focus-visible:outline-none"
        >
            {sport.photo_url && (
                <img
                    src={sport.photo_url}
                    alt=""
                    className="absolute inset-0 -z-20 size-full object-cover opacity-35 transition-transform duration-500 group-hover:scale-105"
                />
            )}
            <div className="absolute inset-0 -z-10 bg-gradient-to-b from-[var(--portal-surface)]/45 via-[var(--portal-surface)]/70 to-[var(--portal-surface)]/95" />
            <div className="flex items-start justify-between gap-3">
                <h3 className="pt-1 text-lg font-bold text-[var(--portal-fg)]">
                    {sport.name}
                </h3>
                <div className="flex shrink-0 flex-col items-end gap-2">
                    <PortalSportIcon
                        slug={sport.slug}
                        className="size-14 [&>svg]:size-7"
                    />
                    {sport.is_live && (
                        <span className="inline-flex items-center gap-1 rounded-full bg-[var(--portal-live)] px-2.5 py-1 text-xs font-bold text-[var(--portal-live-foreground)]">
                            <Radio aria-hidden="true" className="size-3" />
                            LIVE
                        </span>
                    )}
                </div>
            </div>

            <div>
                {sport.short_description && (
                    <p className="mt-1 text-sm text-[var(--portal-muted-foreground)]">
                        {sport.short_description}
                    </p>
                )}
            </div>

            <div className="mt-auto flex items-center justify-between pt-2 text-sm">
                <span className="text-[var(--portal-muted-foreground)]">
                    {sport.events.length === 0
                        ? 'No sports events yet'
                        : `${sport.events.length} ${sport.events.length === 1 ? 'sports event' : 'sports events'}`}
                </span>
                <span className="font-semibold text-[var(--portal-accent)] group-hover:underline">
                    View Sport →
                </span>
            </div>
        </Link>
    );
}
