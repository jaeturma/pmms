import { Head, Link } from '@inertiajs/react';
import { Activity, CalendarDays, MapPin, Radio } from 'lucide-react';
import { athletics as publicAthletics, meet as publicMeet } from '@/routes/public';
import { PortalDaySwitcher } from '@/apps/portal/components/day-switcher';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import { PortalVenueInformation } from '@/apps/portal/components/venue-information';
import type {
    PortalDay,
    PortalLiveMatch,
    PortalMeetSummary,
    PortalScheduleVenueGroup,
    PortalVenueGuideEntry,
} from '@/apps/portal/types';

type Props = {
    meet: PortalMeetSummary;
    announcements: unknown[];
    hasAthletics: boolean;
    days: PortalDay[];
    selectedDay: string | null;
    venuesForDay: PortalScheduleVenueGroup[];
    venueGuide: PortalVenueGuideEntry[];
    liveMatches: PortalLiveMatch[];
};

export default function PortalSchedule({ meet, hasAthletics, days, selectedDay, venuesForDay, venueGuide, liveMatches }: Props) {
    return (
        <>
            <Head title={`Schedule — ${meet.name}`} />
            <div className="flex flex-col gap-8">
                <PortalHero
                    title="Schedule"
                    description="Day-by-day schedule of events, grouped by venue."
                    actions={
                        hasAthletics ? (
                            <Link
                                href={publicAthletics(meet.id).url}
                                className="inline-flex items-center gap-1.5 rounded-[var(--portal-radius)] bg-white/15 px-4 py-2 text-sm font-medium text-[var(--portal-ink-foreground)]"
                            >
                                <Activity aria-hidden="true" className="size-4" />
                                Athletics schedule
                            </Link>
                        ) : undefined
                    }
                />

                {liveMatches.length > 0 && (
                    <section className="portal-animate-in flex items-center gap-2 rounded-[var(--portal-radius)] border border-[var(--portal-live)]/30 bg-[var(--portal-live)]/5 px-4 py-3 text-sm">
                        <Radio aria-hidden="true" className="size-4 text-[var(--portal-live)]" />
                        <span>
                            {liveMatches.length} match{liveMatches.length === 1 ? '' : 'es'} live right now.
                        </span>
                    </section>
                )}

                {selectedDay && (
                    <section className="space-y-4">
                        <PortalSectionHeader
                            title="Events by day"
                            action={<PortalDaySwitcher days={days} selected={selectedDay} baseUrl={publicMeet(meet.id).url} />}
                        />

                        {venuesForDay.length === 0 ? (
                            <PortalEmptyState icon={CalendarDays} title="Nothing scheduled this day" />
                        ) : (
                            <div className="space-y-4">
                                {venuesForDay.map((group) => (
                                    <div key={group.venue} className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4">
                                        <p className="mb-3 flex items-center gap-1.5 text-sm font-semibold">
                                            <MapPin aria-hidden="true" className="size-4 text-[var(--portal-accent)]" />
                                            {group.venue}
                                        </p>
                                        <ul className="space-y-2 text-sm">
                                            {group.slots.map((slot) => (
                                                <li key={slot.id} className="flex flex-col gap-0.5 border-t border-[var(--portal-border)] pt-2 first:border-t-0 first:pt-0">
                                                    <span className="font-medium">
                                                        {slot.starts_at}–{slot.ends_at} · {slot.event}
                                                    </span>
                                                    {slot.competition_area && (
                                                        <span className="text-xs text-[var(--portal-muted-foreground)]">
                                                            Competition area: {slot.competition_area}
                                                        </span>
                                                    )}
                                                    {slot.note && <span className="text-xs text-[var(--portal-muted-foreground)]">{slot.note}</span>}
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
