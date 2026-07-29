import { Head, router } from '@inertiajs/react';
import { Medal, Network, Trophy } from 'lucide-react';
import { useEffect } from 'react';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { PublicPageHero } from '@/components/public-page-hero';
import type { SportPortalGame } from '@/components/sport-portal-game-list';
import { SportPortalGameList } from '@/components/sport-portal-game-list';
import type { SportPortalLiveNow } from '@/components/sport-portal-live-now';
import { SportPortalLiveNowCard } from '@/components/sport-portal-live-now';
import { SportPortalUnavailable } from '@/components/sport-portal-unavailable';
import type { SportPortalVenue } from '@/components/sport-portal-venue-info';
import { SportPortalVenueInfo } from '@/components/sport-portal-venue-info';
import { sportPortals } from '@/config/sport-portals';
import { usePageVisible } from '@/hooks/use-page-visible';
import { capitalize, pluralize } from '@/lib/utils';
import { poll as pollSportPortal } from '@/routes/public/sport-portal';

/** Today's Games refresh at the brief's own 30-60s band; Completed/
 * Upcoming/Venues rarely change mid-visit but share the same request
 * (one background reload, not five separate timers) at the same
 * interval — simpler than the brief's own per-section table without
 * meaningfully hurting freshness for the slower-changing sections. */
const BACKGROUND_REFRESH_INTERVAL_MS = 45000;

type Props = {
    sport: { slug: string; name: string };
    canonicalUrl: string;
    meet: {
        id: number;
        name: string;
        school_year: string;
        starts_at: string;
        ends_at: string;
        venue: string | null;
        status_label: string;
    } | null;
    liveNow: SportPortalLiveNow | null;
    otherLiveCount: number;
    todayGames: SportPortalGame[];
    completedGames: SportPortalGame[];
    upcomingGames: SportPortalGame[];
    venues: SportPortalVenue[];
};

export default function SportPortal({
    sport,
    canonicalUrl,
    meet,
    liveNow,
    otherLiveCount,
    todayGames,
    completedGames,
    upcomingGames,
    venues,
}: Props) {
    // Real per-sport wording (Phase 12 §4/§H) — "games" for Basketball,
    // "matches" for Volleyball, "bouts" for Boxing, etc. Falls back to
    // "games" for any slug not yet in the config, which never happens
    // for the 12 real routes this app registers.
    const gameTerm = sportPortals[sport.slug]?.terminology.game ?? 'game';
    const gamesLabel = capitalize(pluralize(gameTerm));
    const visible = usePageVisible();

    // Background refresh for the game lists/venues (WP-12-06) — Live
    // Now has its own faster, separate poll (`SportPortalLiveNowCard`).
    // Paused while the tab is hidden via the same `usePageVisible()`
    // gate: the effect's cleanup already clears the interval whenever
    // `visible` flips, so pausing/resuming needs no extra bookkeeping.
    // `only` fetches just these four props — never a different sport's
    // data, never the whole page re-rendering.
    useEffect(() => {
        if (!visible || meet === null) {
            return;
        }

        const interval = setInterval(() => {
            router.reload({
                only: [
                    'todayGames',
                    'completedGames',
                    'upcomingGames',
                    'venues',
                ],
            });
        }, BACKGROUND_REFRESH_INTERVAL_MS);

        return () => clearInterval(interval);
    }, [visible, meet]);

    if (meet === null) {
        return (
            <>
                <Head title={`${sport.name} | Provincial Meet`}>
                    <meta
                        head-key="description"
                        name="description"
                        content={`Live scores, schedules, and standings for ${sport.name} at the provincial meet.`}
                    />
                    <link
                        head-key="canonical"
                        rel="canonical"
                        href={canonicalUrl}
                    />
                </Head>
                <div className="flex min-h-[calc(100vh-10rem)] flex-col gap-6 sm:gap-8">
                    <PublicPageHero
                        title={sport.name}
                        description="Live scores, schedules, and standings for this sport."
                    />
                    <div className="flex flex-1 items-center justify-center">
                        <EmptyState
                            icon={Trophy}
                            title="No meet is active right now"
                            description="Check back here once the next meet is underway."
                        />
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title={`${sport.name} — ${meet.name}`}>
                <meta
                    head-key="description"
                    name="description"
                    content={`Live scores, schedules, and standings for ${sport.name} at ${meet.name}.`}
                />
                <link
                    head-key="canonical"
                    rel="canonical"
                    href={canonicalUrl}
                />
            </Head>
            <div className="flex animate-card-in flex-col gap-8 sm:gap-10">
                <PublicPageHero
                    title={sport.name}
                    description={`Live scores, schedules, and standings for ${sport.name} at ${meet.name}.`}
                />

                <section className="space-y-3">
                    <Heading variant="small" title="Live now" />
                    <SportPortalLiveNowCard
                        initialLiveNow={liveNow}
                        initialOtherLiveCount={otherLiveCount}
                        pollUrl={pollSportPortal(sport.slug).url}
                    />
                </section>

                <section className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div className="space-y-3">
                        <Heading
                            variant="small"
                            title={`Today's ${gamesLabel}`}
                        />
                        <SportPortalGameList
                            games={todayGames}
                            emptyTitle="Nothing scheduled today"
                            emptyDescription={`Check the upcoming ${gameTerm} list below.`}
                            showScore
                        />
                    </div>
                    <div className="space-y-3">
                        <Heading
                            variant="small"
                            title={`Upcoming ${gamesLabel}`}
                        />
                        <SportPortalGameList
                            games={upcomingGames}
                            emptyTitle={`No upcoming ${pluralize(gameTerm)} yet`}
                            emptyDescription={`New ${pluralize(gameTerm)} appear here once scheduled.`}
                        />
                    </div>
                </section>

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title={`Completed ${gamesLabel}`}
                    />
                    <SportPortalGameList
                        games={completedGames}
                        emptyTitle={`No completed ${pluralize(gameTerm)} yet`}
                        emptyDescription={`Results appear here once ${pluralize(gameTerm)} are completed.`}
                        showScore
                    />
                </section>

                <section className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div className="space-y-3">
                        <Heading variant="small" title="Standings" />
                        <SportPortalUnavailable
                            icon={Trophy}
                            title="Standings"
                        />
                    </div>
                    <div className="space-y-3">
                        <Heading variant="small" title="Leading scorers" />
                        <SportPortalUnavailable
                            icon={Medal}
                            title="Leading scorers"
                        />
                    </div>
                    <div className="space-y-3">
                        <Heading variant="small" title="Tournament bracket" />
                        <SportPortalUnavailable
                            icon={Network}
                            title="Tournament bracket"
                        />
                    </div>
                </section>

                <section className="space-y-3">
                    <Heading variant="small" title="Venue information" />
                    <SportPortalVenueInfo venues={venues} />
                </section>
            </div>
        </>
    );
}
