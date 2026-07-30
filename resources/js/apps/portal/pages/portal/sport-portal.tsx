import { Head, router } from '@inertiajs/react';
import { Trophy } from 'lucide-react';
import { useEffect, useState } from 'react';
import { poll as pollSportPortal } from '@/routes/public/sport-portal';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalLeadingScorers } from '@/apps/portal/components/leading-scorers';
import { PortalLiveScoreCard } from '@/apps/portal/components/live-score-card';
import { PortalScheduleList } from '@/apps/portal/components/schedule-list';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import { PortalStandingsTable } from '@/apps/portal/components/standings-table';
import { PortalTournamentBracket } from '@/apps/portal/components/tournament-bracket';
import { PortalVenueInformation } from '@/apps/portal/components/venue-information';
import { usePortalPageVisible } from '@/apps/portal/lib/use-page-visible';
import { capitalize, pluralize, PORTAL_SPORT_TERMINOLOGY } from '@/apps/portal/lib/sport-terminology';
import type { PortalGame, PortalLiveNow, PortalMeetSummary, PortalSport, PortalVenue } from '@/apps/portal/types';

const BACKGROUND_REFRESH_INTERVAL_MS = 45000;
const LIVE_POLL_INTERVAL_MS = 10000;

type Props = {
    sport: PortalSport;
    canonicalUrl: string;
    meet: PortalMeetSummary | null;
    liveNow: PortalLiveNow | null;
    otherLiveCount: number;
    todayGames: PortalGame[];
    completedGames: PortalGame[];
    upcomingGames: PortalGame[];
    venues: PortalVenue[];
};

export default function PortalSportPortal({
    sport,
    canonicalUrl,
    meet,
    liveNow: initialLiveNow,
    otherLiveCount: initialOtherLiveCount,
    todayGames,
    completedGames,
    upcomingGames,
    venues,
}: Props) {
    const visible = usePortalPageVisible();
    const [liveNow, setLiveNow] = useState(initialLiveNow);
    const [otherLiveCount, setOtherLiveCount] = useState(initialOtherLiveCount);

    // Real per-sport wording — "games" for Basketball, "matches" for
    // Volleyball, "bouts" for Boxing, etc. Falls back to "game" for
    // any slug not in the map, which never happens for the 12 real
    // routes this app registers.
    const gameTerm = PORTAL_SPORT_TERMINOLOGY[sport.slug]?.game ?? 'game';
    const gamesLabel = capitalize(pluralize(gameTerm));

    useEffect(() => {
        setLiveNow(initialLiveNow);
        setOtherLiveCount(initialOtherLiveCount);
    }, [initialLiveNow, initialOtherLiveCount]);

    // Live Now polling (Phase 12 pattern): a lightweight JSON endpoint,
    // separate from the background reload below, so the featured match
    // can change between polls without waiting on the slower interval.
    useEffect(() => {
        if (!visible || meet === null) {
            return;
        }

        const interval = setInterval(() => {
            fetch(pollSportPortal(sport.slug).url, { headers: { Accept: 'application/json' } })
                .then((response) => (response.ok ? response.json() : null))
                .then((data: { liveNow: PortalLiveNow | null; otherLiveCount: number } | null) => {
                    if (data === null) {
                        return;
                    }

                    setLiveNow(data.liveNow);
                    setOtherLiveCount(data.otherLiveCount);
                })
                .catch(() => {
                    /* transient network failure — next poll retries */
                });
        }, LIVE_POLL_INTERVAL_MS);

        return () => clearInterval(interval);
    }, [visible, meet, sport.slug]);

    useEffect(() => {
        if (!visible || meet === null) {
            return;
        }

        const interval = setInterval(() => {
            router.reload({ only: ['todayGames', 'completedGames', 'upcomingGames', 'venues'] });
        }, BACKGROUND_REFRESH_INTERVAL_MS);

        return () => clearInterval(interval);
    }, [visible, meet]);

    if (meet === null) {
        return (
            <>
                <Head title={`${sport.name} | Provincial Meet`}>
                    <link head-key="canonical" rel="canonical" href={canonicalUrl} />
                </Head>
                <div className="flex min-h-[60vh] flex-col gap-8">
                    <PortalHero title={sport.name} description="Live scores, schedules, and standings for this sport." />
                    <PortalEmptyState icon={Trophy} title="No meet is active right now" description="Check back here once the next meet is underway." />
                </div>
            </>
        );
    }

    return (
        <>
            <Head title={`${sport.name} — ${meet.name}`}>
                <link head-key="canonical" rel="canonical" href={canonicalUrl} />
            </Head>
            <div className="flex flex-col gap-8">
                <PortalHero title={sport.name} description={`Live scores, schedules, and standings for ${sport.name} at ${meet.name}.`} />

                <section className="space-y-3">
                    <PortalSectionHeader
                        title="Live now"
                        action={otherLiveCount > 0 && (
                            <span className="text-sm text-[var(--portal-muted-foreground)]">+{otherLiveCount} other live</span>
                        )}
                    />
                    {liveNow === null ? (
                        <PortalEmptyState icon={Trophy} title="Nothing live right now" />
                    ) : (
                        <PortalLiveScoreCard liveNow={liveNow} />
                    )}
                </section>

                <section className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-3">
                        <PortalSectionHeader title={`Today's ${gamesLabel}`} />
                        <PortalScheduleList
                            games={todayGames}
                            emptyTitle="Nothing scheduled today"
                            emptyDescription={`Check the upcoming ${gameTerm} list below.`}
                            showScore
                        />
                    </div>
                    <div className="space-y-3">
                        <PortalSectionHeader title={`Upcoming ${gamesLabel}`} />
                        <PortalScheduleList
                            games={upcomingGames}
                            emptyTitle={`No upcoming ${pluralize(gameTerm)} yet`}
                            emptyDescription={`New ${pluralize(gameTerm)} appear here once scheduled.`}
                        />
                    </div>
                </section>

                <section className="space-y-3">
                    <PortalSectionHeader title={`Completed ${gamesLabel}`} />
                    <PortalScheduleList
                        games={completedGames}
                        emptyTitle={`No completed ${pluralize(gameTerm)} yet`}
                        emptyDescription={`Results appear here once ${pluralize(gameTerm)} are completed.`}
                        showScore
                    />
                </section>

                <section className="grid gap-6 md:grid-cols-3">
                    <div className="space-y-3">
                        <PortalSectionHeader title="Standings" />
                        <PortalStandingsTable rows={null} />
                    </div>
                    <div className="space-y-3">
                        <PortalSectionHeader title="Leading scorers" />
                        <PortalLeadingScorers rows={null} />
                    </div>
                    <div className="space-y-3">
                        <PortalSectionHeader title="Tournament bracket" />
                        <PortalTournamentBracket rounds={null} />
                    </div>
                </section>

                <section className="space-y-3">
                    <PortalSectionHeader title="Venue information" />
                    <PortalVenueInformation venues={venues} />
                </section>
            </div>
        </>
    );
}
