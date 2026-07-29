import { MapPin, Navigation } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';

export type SportPortalVenue = {
    id: number;
    name: string;
    address: string | null;
    directions_url: string;
};

/**
 * Real venues this sport actually uses in the active meet
 * (`PortalController::sportPortalData()`) — no embedded map (the
 * brief's own rule), a plain external map-search link built from real
 * name/address text instead. `Venue` has no stored geo field.
 */
export function SportPortalVenueInfo({
    venues,
}: {
    venues: SportPortalVenue[];
}) {
    if (venues.length === 0) {
        return (
            <EmptyState
                icon={MapPin}
                title="No venue assigned yet"
                description="Venue information appears here once matches are scheduled."
            />
        );
    }

    return (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            {venues.map((venue) => (
                <div
                    key={venue.id}
                    className="flex items-start gap-3 rounded-xl border p-4"
                >
                    <MapPin
                        aria-hidden="true"
                        className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                    />
                    <div className="flex-1">
                        <p className="font-medium">{venue.name}</p>
                        {venue.address && (
                            <p className="text-sm text-muted-foreground">
                                {venue.address}
                            </p>
                        )}
                        <a
                            href={venue.directions_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="mt-1 inline-flex items-center gap-1 text-sm text-primary hover:underline"
                        >
                            <Navigation
                                aria-hidden="true"
                                className="size-3.5"
                            />
                            Directions
                        </a>
                    </div>
                </div>
            ))}
        </div>
    );
}
