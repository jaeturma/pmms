import { Head, router } from '@inertiajs/react';
import { Trophy } from 'lucide-react';
import { useEffect, useState } from 'react';
import { PortalBasketballScoreboard } from '@/apps/portal/components/basketball-scoreboard';
import { PortalBasketballSidebar } from '@/apps/portal/components/basketball-sidebar';
import { PortalBoxingScoreboard } from '@/apps/portal/components/boxing-scoreboard';
import { PortalBoxingSidebar } from '@/apps/portal/components/boxing-sidebar';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalLeadingScorers } from '@/apps/portal/components/leading-scorers';
import { PortalLiveScoreCard } from '@/apps/portal/components/live-score-card';
import { PortalScheduleList } from '@/apps/portal/components/schedule-list';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import { PortalSoftballScoreboard } from '@/apps/portal/components/softball-scoreboard';
import { PortalSoftballSidebar } from '@/apps/portal/components/softball-sidebar';
import { PortalSportEventStrip } from '@/apps/portal/components/sport-event-strip';
import { PortalStandingsTable } from '@/apps/portal/components/standings-table';
import { PortalTournamentBracket } from '@/apps/portal/components/tournament-bracket';
import { PortalVenueInformation } from '@/apps/portal/components/venue-information';
import {
    capitalize,
    pluralize,
    PORTAL_SPORT_TERMINOLOGY,
} from '@/apps/portal/lib/sport-terminology';
import { usePortalPageVisible } from '@/apps/portal/lib/use-page-visible';
import type {
    PortalGame,
    PortalLiveNow,
    PortalMeetSummary,
    PortalSport,
    PortalVenue,
} from '@/apps/portal/types';
import { scoreboard as publicScoreboard } from '@/routes/public';
import { poll as pollSportPortal } from '@/routes/public/sport-portal';

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
    const [syncedInitialLiveNow, setSyncedInitialLiveNow] =
        useState(initialLiveNow);
    // Same `pollFailures >= 2` pattern as `portal/scoreboard.tsx` and the
    // operator console (`scoring/show.tsx`, WP-08-10) — this poll
    // previously discarded every failure (including a non-OK response)
    // completely silently, so a run of transient errors here looked
    // identical to a healthy connection: the Live Now card just stopped
    // updating, for every sport including Softball/Baseball/Boxing, with
    // nothing on screen to explain why.
    const [pollFailures, setPollFailures] = useState(0);

    // Real per-sport wording — "games" for Basketball, "matches" for
    // Volleyball, "bouts" for Boxing, etc. Falls back to "game" for
    // any slug not in the map, which never happens for the 12 real
    // routes this app registers.
    const gameTerm = PORTAL_SPORT_TERMINOLOGY[sport.slug]?.game ?? 'game';
    const gamesLabel = capitalize(pluralize(gameTerm));

    // Adjust local state during render when a fresh Inertia prop arrives
    // (e.g. navigating from one sport to another) — same
    // React-recommended alternative to syncing props into state via an
    // effect that `scoring/show.tsx` already uses. Between such visits,
    // polling updates `liveNow`/`otherLiveCount` locally.
    if (initialLiveNow !== syncedInitialLiveNow) {
        setSyncedInitialLiveNow(initialLiveNow);
        setLiveNow(initialLiveNow);
        setOtherLiveCount(initialOtherLiveCount);
    }

    // Live Now polling (Phase 12 pattern): a lightweight JSON endpoint,
    // separate from the background reload below, so the featured match
    // can change between polls without waiting on the slower interval.
    useEffect(() => {
        if (!visible || meet === null) {
            return;
        }

        const interval = setInterval(() => {
            fetch(pollSportPortal(sport.slug).url, {
                headers: { Accept: 'application/json' },
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`poll failed: ${response.status}`);
                    }

                    return response.json() as Promise<{
                        liveNow: PortalLiveNow | null;
                        otherLiveCount: number;
                    }>;
                })
                .then((data) => {
                    setLiveNow(data.liveNow);
                    setOtherLiveCount(data.otherLiveCount);
                    setPollFailures(0);
                })
                .catch(() => {
                    // Retries on its own next tick — no user action
                    // needed, but the banner below flags it after a
                    // couple of misses.
                    setPollFailures((n) => n + 1);
                });
        }, LIVE_POLL_INTERVAL_MS);

        return () => clearInterval(interval);
    }, [visible, meet, sport.slug]);

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
                    <link
                        head-key="canonical"
                        rel="canonical"
                        href={canonicalUrl}
                    />
                </Head>
                <div className="flex min-h-[60vh] flex-col gap-8">
                    <PortalHero
                        title={sport.name}
                        description="Live scores, schedules, and standings for this sport."
                    />
                    <PortalEmptyState
                        icon={Trophy}
                        title="No meet is active right now"
                        description="Check back here once the next meet is underway."
                    />
                </div>
            </>
        );
    }

    const showBasketballLive =
        liveNow !== null && liveNow.session.board_type === 'basketball';
    const showSoftballLive =
        liveNow !== null && liveNow.session.board_type === 'softball_baseball';
    const showBoxingLive =
        liveNow !== null && liveNow.session.board_type === 'boxing';
    const showSportStrip =
        showBasketballLive || showSoftballLive || showBoxingLive;

    // Athletics/Swimming's "completed games" are built from
    // EventSchedule/EventResult rows, not EventMatch (PortalController::
    // INDIVIDUAL_EVENT_SPORTS) — their `id` isn't a match id, so the
    // per-match scoreboard route doesn't apply to them.
    const completedGamesLinkTo =
        sport.slug === 'athletics' || sport.slug === 'swimming'
            ? undefined
            : (game: PortalGame) =>
                  publicScoreboard({ meet: meet.id, match: game.id }).url;

    return (
        <>
            <Head title={`${sport.name} — ${meet.name}`}>
                <link
                    head-key="canonical"
                    rel="canonical"
                    href={canonicalUrl}
                />
            </Head>
            <div className="flex flex-col gap-8">
                {showSportStrip && liveNow ? (
                    <div className="flex flex-col gap-2">
                        <PortalSportEventStrip
                            sportEmoji={
                                showBasketballLive
                                    ? '🏀'
                                    : showSoftballLive
                                      ? '🥎'
                                      : '🥊'
                            }
                            sportName={sport.name}
                            category={liveNow.category}
                            roundLabel={liveNow.round_label}
                            live={liveNow.session.status !== 'ended'}
                            scheduledDate={liveNow.scheduled_date}
                            startsAt={liveNow.starts_at}
                        />
                        {otherLiveCount > 0 && (
                            <span className="text-sm text-[var(--portal-muted-foreground)]">
                                +{otherLiveCount} other {gameTerm} live
                            </span>
                        )}
                    </div>
                ) : (
                    <PortalHero
                        title={sport.name}
                        description={`Live scores, schedules, and standings for ${sport.name} at ${meet.name}.`}
                    />
                )}

                <section className="space-y-3">
                    {!showSportStrip && (
                        <PortalSectionHeader
                            title="Live now"
                            action={
                                otherLiveCount > 0 && (
                                    <span className="text-sm text-[var(--portal-muted-foreground)]">
                                        +{otherLiveCount} other live
                                    </span>
                                )
                            }
                        />
                    )}
                    {liveNow !== null && pollFailures >= 2 && (
                        <span className="text-xs font-medium text-[var(--portal-warning)]">
                            Reconnecting — scores may be behind
                        </span>
                    )}
                    {liveNow === null ? (
                        <PortalEmptyState
                            icon={Trophy}
                            title="Nothing live right now"
                        />
                    ) : showBasketballLive ? (
                        <div className="grid grid-cols-1 gap-5 lg:grid-cols-[2.15fr_0.9fr] lg:items-start">
                            <PortalBasketballScoreboard liveNow={liveNow} />
                            <PortalBasketballSidebar
                                session={liveNow.session}
                            />
                        </div>
                    ) : showSoftballLive ? (
                        <div className="grid grid-cols-1 gap-5 lg:grid-cols-[2.15fr_0.95fr] lg:items-start">
                            <PortalSoftballScoreboard liveNow={liveNow} />
                            <PortalSoftballSidebar session={liveNow.session} />
                        </div>
                    ) : showBoxingLive ? (
                        <div className="grid grid-cols-1 gap-5 lg:grid-cols-[2.15fr_0.95fr] lg:items-start">
                            <PortalBoxingScoreboard liveNow={liveNow} />
                            <PortalBoxingSidebar liveNow={liveNow} />
                        </div>
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
                        linkTo={completedGamesLinkTo}
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
