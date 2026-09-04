import { Head, Link } from '@inertiajs/react';
import {
    Activity,
    CalendarDays,
    Clock3,
    Filter,
    MapPin,
    Radio,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { formatTime } from '@/lib/format-time';
import { PortalDaySwitcher } from '@/apps/portal/components/day-switcher';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import { PortalSelect } from '@/apps/portal/components/select';
import { PortalVenueInformation } from '@/apps/portal/components/venue-information';
import type {
    PortalDay,
    PortalLiveMatch,
    PortalMeetSummary,
    PortalScheduleVenueGroup,
    PortalVenueGuideEntry,
} from '@/apps/portal/types';
import {
    athletics as publicAthletics,
    meet as publicMeet,
} from '@/routes/public';

type Props = {
    meet: PortalMeetSummary;
    announcements: unknown[];
    hasAthletics: boolean;
    days: PortalDay[];
    selectedDay: string | null;
    venuesForDay: PortalScheduleVenueGroup[];
    venueGuide: PortalVenueGuideEntry[];
    liveMatches: PortalLiveMatch[];
    sportOptions: Array<{ value: string; label: string }>;
};

export default function PortalSchedule({
    meet,
    hasAthletics,
    days,
    selectedDay,
    venuesForDay,
    venueGuide,
    liveMatches,
    sportOptions,
}: Props) {
    const [sport, setSport] = useState('all');
    const filteredVenues = useMemo(
        () =>
            venuesForDay
                .map((venue) => ({
                    ...venue,
                    slots:
                        sport === 'all'
                            ? venue.slots
                            : venue.slots.filter(
                                  (slot) => String(slot.sport_id) === sport,
                              ),
                }))
                .filter((venue) => venue.slots.length > 0),
        [venuesForDay, sport],
    );
    const eventCount = filteredVenues.reduce(
        (total, venue) => total + venue.slots.length,
        0,
    );

    return (
        <>
            <Head title={`Schedule of Events — ${meet.name}`} />
            <div className="flex flex-col gap-8">
                <PortalHero
                    title="Schedule of Events"
                    description="Day-by-day schedule of events, grouped by venue."
                    actions={
                        hasAthletics ? (
                            <Link
                                href={publicAthletics(meet.id).url}
                                className="inline-flex items-center gap-1.5 rounded-[var(--portal-radius)] bg-white/15 px-4 py-2 text-sm font-medium text-[var(--portal-ink-foreground)]"
                            >
                                <Activity
                                    aria-hidden="true"
                                    className="size-4"
                                />
                                Athletics schedule
                            </Link>
                        ) : undefined
                    }
                />

                {liveMatches.length > 0 && (
                    <section className="portal-animate-in flex items-center gap-2 rounded-[var(--portal-radius)] border border-[var(--portal-live)]/30 bg-[var(--portal-live)]/5 px-4 py-3 text-sm">
                        <Radio
                            aria-hidden="true"
                            className="size-4 text-[var(--portal-live)]"
                        />
                        <span>
                            {liveMatches.length} match
                            {liveMatches.length === 1 ? '' : 'es'} live right
                            now.
                        </span>
                    </section>
                )}

                {selectedDay && (
                    <section className="space-y-4">
                        <PortalSectionHeader
                            title="Competition schedule"
                            action={
                                <PortalDaySwitcher
                                    days={days}
                                    selected={selectedDay}
                                    baseUrl={publicMeet(meet.id).url}
                                />
                            }
                        />

                        <div className="flex flex-col gap-3 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-3 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-2 text-sm text-[var(--portal-muted-foreground)]">
                                <Filter
                                    aria-hidden="true"
                                    className="size-4 text-[var(--portal-accent)]"
                                />
                                <span>
                                    {eventCount} event
                                    {eventCount === 1 ? '' : 's'} ·{' '}
                                    {filteredVenues.length} venue
                                    {filteredVenues.length === 1 ? '' : 's'}
                                </span>
                            </div>
                            <label className="flex flex-col gap-1.5 text-sm font-medium sm:flex-row sm:items-center sm:gap-2">
                                <span>Filter by sport</span>
                                <PortalSelect
                                    value={sport}
                                    onChange={(event) =>
                                        setSport(event.target.value)
                                    }
                                    options={[
                                        { value: 'all', label: 'All sports' },
                                        ...sportOptions,
                                    ]}
                                    className="w-full sm:min-w-48"
                                />
                            </label>
                        </div>

                        {filteredVenues.length === 0 ? (
                            <PortalEmptyState
                                icon={CalendarDays}
                                title={
                                    sport === 'all'
                                        ? 'Nothing scheduled this day'
                                        : 'No events for this sport on the selected day'
                                }
                            />
                        ) : (
                            <div className="grid gap-4 lg:grid-cols-2">
                                {filteredVenues.map((group) => (
                                    <div
                                        key={group.venue}
                                        className="overflow-hidden rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] shadow-sm"
                                    >
                                        <div className="flex items-center justify-between gap-3 border-b border-[var(--portal-border)] bg-[var(--portal-accent)]/5 px-4 py-3">
                                            <h3 className="flex min-w-0 items-center gap-2 text-sm font-semibold">
                                                <MapPin
                                                    aria-hidden="true"
                                                    className="size-4 shrink-0 text-[var(--portal-accent)]"
                                                />
                                                <span className="truncate">
                                                    {group.venue}
                                                </span>
                                            </h3>
                                            <span className="shrink-0 rounded-full bg-[var(--portal-surface)] px-2.5 py-1 text-xs font-medium text-[var(--portal-muted-foreground)]">
                                                {group.slots.length}{' '}
                                                {group.slots.length === 1
                                                    ? 'event'
                                                    : 'events'}
                                            </span>
                                        </div>
                                        <ul className="divide-y divide-[var(--portal-border)] text-sm">
                                            {group.slots.map((slot) => (
                                                <li
                                                    key={slot.id}
                                                    className="grid gap-2 px-4 py-3 sm:grid-cols-[7.5rem_1fr] sm:gap-4"
                                                >
                                                    <span className="flex items-center gap-1.5 text-xs font-semibold text-[var(--portal-accent)] sm:items-start sm:pt-0.5">
                                                        <Clock3
                                                            aria-hidden="true"
                                                            className="size-3.5 shrink-0"
                                                        />
                                                        {formatTime(slot.starts_at)}–
                                                        {formatTime(slot.ends_at)}
                                                    </span>
                                                    <span className="flex min-w-0 flex-col gap-1">
                                                        <span className="font-semibold">
                                                            {slot.event}
                                                        </span>
                                                        {slot.competition_area && (
                                                            <span className="text-xs text-[var(--portal-muted-foreground)]">
                                                                Competition
                                                                area:{' '}
                                                                {
                                                                    slot.competition_area
                                                                }
                                                            </span>
                                                        )}
                                                        {slot.note && (
                                                            <span className="text-xs text-[var(--portal-muted-foreground)]">
                                                                {slot.note}
                                                            </span>
                                                        )}
                                                    </span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>
                )}

                <section className="space-y-3">
                    <PortalSectionHeader title="Venue guide" />
                    <PortalVenueInformation
                        venues={venueGuide.map((venue, index) => ({
                            id: index,
                            name: venue.name,
                            address: venue.address,
                            directions_url: `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(venue.address ? `${venue.name}, ${venue.address}` : venue.name)}`,
                        }))}
                    />
                </section>
            </div>
        </>
    );
}
