import { MapPin } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import type { PortalVenue } from '@/apps/portal/types';

type PortalVenueInformationProps = {
    venues: PortalVenue[];
};

export function PortalVenueInformation({
    venues,
}: PortalVenueInformationProps) {
    if (venues.length === 0) {
        return (
            <PortalEmptyState icon={MapPin} title="No venues scheduled yet" />
        );
    }

    return (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            {venues.map((venue) => (
                <a
                    key={venue.id}
                    href={venue.directions_url}
                    target="_blank"
                    rel="noreferrer"
                    className="flex items-center gap-3 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-3 text-sm transition-colors hover:bg-[var(--portal-muted)]"
                >
                    <span className="portal-icon-badge size-11 shrink-0 bg-[var(--portal-ink-soft)] text-[var(--portal-ink)]">
                        <MapPin aria-hidden="true" className="size-5" />
                    </span>
                    <span>
                        <span className="block font-medium">{venue.name}</span>
                        {venue.address && (
                            <span className="text-xs text-[var(--portal-muted-foreground)]">
                                {venue.address}
                            </span>
                        )}
                    </span>
                </a>
            ))}
        </div>
    );
}
