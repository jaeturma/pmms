import { Head, usePage } from '@inertiajs/react';
import {
    Award,
    CalendarClock,
    CalendarDays,
    Crown,
    Dumbbell,
    Flag,
    MapPin,
    Radio,
    Trophy,
    Users,
} from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalLandingHero } from '@/apps/portal/components/landing-hero';
import { PortalQuickNav } from '@/apps/portal/components/quick-nav';
import type { PortalQuickNavItem } from '@/apps/portal/components/quick-nav';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import { PortalStandingsTable } from '@/apps/portal/components/standings-table';
import type {
    PortalAnnouncement,
    PortalClosingSummary,
    PortalLatestResult,
    PortalLeader,
    PortalMeetSummary,
    PortalMunicipality,
    PortalUpcomingEvent,
} from '@/apps/portal/types';
import { home } from '@/routes';
import {
    meet as publicMeet,
    results as publicResults,
    sports as publicSports,
    tally as publicTally,
} from '@/routes/public';

type Props = {
    meet: PortalMeetSummary | null;
    municipalities: PortalMunicipality[];
    announcements: PortalAnnouncement[];
    facebookLive: { url: string; embed_url: string } | null;
    currentLeaders: PortalLeader[];
    upcomingEvents: PortalUpcomingEvent[];
    latestResult: PortalLatestResult | null;
    closingSummary: PortalClosingSummary | null;
};

export default function PortalHome({
    meet,
    municipalities,
    announcements,
    facebookLive,
    currentLeaders,
    upcomingEvents,
    latestResult,
    closingSummary,
}: Props) {
    const { props } = usePage<{
        division: { name: string; heroIconUrl: string | null };
    }>();

    if (meet === null) {
        return (
            <>
                <Head title="Provincial Meet" />
                <div className="flex min-h-[60vh] flex-col gap-8">
                    <PortalHero
                        title="Provincial Meet"
                        description="Schedules, results, and medal standings of the Schools Division Office athletic meets."
                    />
                    <PortalEmptyState
                        icon={Flag}
                        title="No meet is active right now"
                        description="Check back here once the next meet is underway."
                    />
                </div>
            </>
        );
    }

    const quickNavItems: PortalQuickNavItem[] = [
        {
            label: 'Schedule',
            href: publicMeet(meet.id).url,
            icon: CalendarDays,
            tone: 'accent',
        },
        {
            label: 'Results',
            href: publicResults(meet.id).url,
            icon: Award,
            tone: 'ink',
        },
        {
            label: 'Medal Tally',
            href: publicTally(meet.id).url,
            icon: Trophy,
            tone: 'maroon',
        },
        {
            label: 'Municipalities',
            href: `${home().url}#municipalities`,
            icon: Users,
            tone: 'warning',
        },
        {
            label: 'Sports',
            href: publicSports(meet.id).url,
            icon: Dumbbell,
            tone: 'muted',
        },
        ...(facebookLive
            ? [
                  {
                      label: 'Live Now',
                      href: `${home().url}#live-now`,
                      icon: Radio,
                      live: true,
                      tone: 'live' as const,
                  },
              ]
            : []),
    ];

    return (
        <>
            <Head title={meet.name} />
            <div className="flex flex-col gap-10">
                <PortalLandingHero
                    title={meet.name}
                    description={`Department of Education - Schools Division of ${props.division.name}`}
                    startsAtIso={meet.starts_at_iso}
                    heroIconUrl={props.division.heroIconUrl}
                    municipalities={municipalities}
                    meta={
                        <>
                            <span>SY {meet.school_year}</span>
                            <span className="flex items-center gap-1.5">
                                <CalendarDays
                                    aria-hidden="true"
                                    className="size-4"
                                />
                                {meet.starts_at} – {meet.ends_at}
                            </span>
                            {meet.venue && (
                                <span className="flex items-center gap-1.5">
                                    <MapPin
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                    {meet.venue}
                                </span>
                            )}
                        </>
                    }
                />

                <PortalQuickNav items={quickNavItems} />

                {closingSummary && (
                    <div className="portal-animate-in flex flex-wrap items-center gap-4 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-5">
                        <Crown
                            aria-hidden="true"
                            className="size-10 shrink-0 text-[var(--portal-accent)]"
                        />
                        <div className="flex-1">
                            <p className="text-xs font-semibold tracking-wide text-[var(--portal-accent)] uppercase">
                                Meet concluded
                            </p>
                            <p className="text-lg font-semibold">
                                Champion: {closingSummary.champion}
                            </p>
                            <p className="text-sm text-[var(--portal-muted-foreground)]">
                                {closingSummary.gold} gold ·{' '}
                                {closingSummary.silver} silver ·{' '}
                                {closingSummary.bronze} bronze ·{' '}
                                {closingSummary.total} total medals
                            </p>
                        </div>
                    </div>
                )}

                {facebookLive && (
                    <section id="live-now" className="scroll-mt-20 space-y-3">
                        <PortalSectionHeader
                            title="Live now"
                            action={
                                <span className="flex items-center gap-1.5 text-sm font-medium text-[var(--portal-live)]">
                                    <span className="size-2 animate-pulse rounded-full bg-[var(--portal-live)]" />
                                    Facebook Live
                                </span>
                            }
                        />
                        <div className="portal-animate-in mx-auto max-w-5xl overflow-hidden rounded-[var(--portal-radius)] border border-[var(--portal-live)]/30 bg-black shadow-lg">
                            <div className="aspect-video w-full">
                                <iframe
                                    src={facebookLive.embed_url}
                                    title="Facebook Live video"
                                    className="h-full w-full border-0"
                                    allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
                                    allowFullScreen
                                />
                            </div>
                        </div>
                    </section>
                )}

                <section className="space-y-3">
                    <PortalSectionHeader title="Announcements" />
                    {announcements.length === 0 ? (
                        <PortalEmptyState
                            icon={CalendarClock}
                            title="No announcements yet"
                        />
                    ) : (
                        <div className="grid gap-3 sm:grid-cols-2">
                            {announcements.map((announcement) => (
                                <div
                                    key={announcement.id}
                                    className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4"
                                >
                                    <p className="text-sm font-medium">
                                        {announcement.title}
                                    </p>
                                    <p className="mt-1 line-clamp-2 text-sm text-[var(--portal-muted-foreground)]">
                                        {announcement.body}
                                    </p>
                                    {announcement.published_at && (
                                        <p className="mt-2 text-xs text-[var(--portal-muted-foreground)]">
                                            {announcement.published_at}
                                        </p>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </section>

                <section className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-3">
                        <PortalSectionHeader title="Current leaders" />
                        <PortalStandingsTable
                            rows={currentLeaders.map((leader) => ({
                                label: leader.district,
                                points: leader.points,
                            }))}
                            unavailableTitle="No standings yet"
                            unavailableDescription="Leaders appear once results are validated."
                        />
                    </div>

                    <div className="space-y-3">
                        <PortalSectionHeader title="Upcoming events" />
                        {upcomingEvents.length === 0 ? (
                            <PortalEmptyState
                                icon={CalendarClock}
                                title="Nothing scheduled next"
                            />
                        ) : (
                            <ul className="space-y-2">
                                {upcomingEvents.map((event) => (
                                    <li
                                        key={event.id}
                                        className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-3 text-sm"
                                    >
                                        <p className="font-medium">
                                            {event.event}
                                        </p>
                                        <p className="text-xs text-[var(--portal-muted-foreground)]">
                                            {event.date}, {event.starts_at} ·{' '}
                                            {event.venue}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    <div className="space-y-3">
                        <PortalSectionHeader title="Latest official result" />
                        {latestResult === null ? (
                            <PortalEmptyState
                                icon={Award}
                                title="No results yet"
                            />
                        ) : (
                            <div className="space-y-2 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-3">
                                <p className="text-sm font-medium">
                                    {latestResult.event}
                                </p>
                                {latestResult.official_as_of && (
                                    <p className="text-xs text-[var(--portal-muted-foreground)]">
                                        Official as of{' '}
                                        {latestResult.official_as_of}
                                    </p>
                                )}
                                <ol className="space-y-1 text-sm">
                                    {latestResult.placements.map(
                                        (placement) => (
                                            <li
                                                key={placement.rank}
                                                className="flex justify-between gap-2"
                                            >
                                                <span>
                                                    {placement.rank}.{' '}
                                                    {placement.athlete}
                                                </span>
                                                <span className="text-[var(--portal-muted-foreground)]">
                                                    {placement.school}
                                                </span>
                                            </li>
                                        ),
                                    )}
                                </ol>
                            </div>
                        )}
                    </div>
                </section>
            </div>
        </>
    );
}
