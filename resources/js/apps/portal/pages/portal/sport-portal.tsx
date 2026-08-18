import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, Trophy } from 'lucide-react';
import { useEffect, useState } from 'react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalLeadingScorers } from '@/apps/portal/components/leading-scorers';
import { PortalScheduleList } from '@/apps/portal/components/schedule-list';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import { PortalSportCategories } from '@/apps/portal/components/sport-categories';
import { PortalSportDescription } from '@/apps/portal/components/sport-description';
import { PortalSportEventStrip } from '@/apps/portal/components/sport-event-strip';
import { PortalSportIcon } from '@/apps/portal/components/sport-icon';
import { PortalSportPhoto } from '@/apps/portal/components/sport-photo';
import { PortalStandingsTable } from '@/apps/portal/components/standings-table';
import { PortalTechnicalOfficials } from '@/apps/portal/components/technical-officials';
import { PortalTournamentBracket } from '@/apps/portal/components/tournament-bracket';
import { PortalTournamentManagement } from '@/apps/portal/components/tournament-management';
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
import { liveSportPortal, scoreboard as publicScoreboard } from '@/routes/public';
import { poll as pollSportPortal } from '@/routes/public/sport-portal';

const BACKGROUND_REFRESH_INTERVAL_MS = 45000;
// Guest scoreboards cannot subscribe to the scorer's private Echo channel,
// so clock controls need a short polling interval to remain in sync.
const LIVE_POLL_INTERVAL_MS = 1000;

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
                cache: 'no-store',
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

    const heroIcon = (
        <PortalSportIcon slug={sport.slug} className="size-16 shrink-0 border-2 border-[var(--portal-ink-foreground)]/20 [&>svg]:size-8 sm:size-20 sm:[&>svg]:size-10" />
    );
    const heroMeta = (
        <>
            <span>{sport.is_paragames ? 'Paragames' : 'Regular Sport'}</span>
            <span>
                {sport.categories.length} {sport.categories.length === 1 ? 'category' : 'categories'}
            </span>
        </>
    );

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
                <div className="flex flex-col gap-8">
                    <PortalHero
                        icon={heroIcon}
                        title={sport.name}
                        description="Live scores, schedules, and standings for this sport."
                        meta={heroMeta}
                    />
                    <PortalSportPhoto photoUrl={sport.photo_url} sportName={sport.name} />
                    <section className="space-y-3">
                        <PortalSectionHeader title="About" />
                        <PortalSportDescription description={sport.description} />
                    </section>
                    <section className="space-y-3">
                        <PortalSectionHeader title="Competition categories" />
                        <PortalSportCategories categories={sport.categories} />
                    </section>
                    <section className="space-y-3">
                        <PortalSectionHeader title="Technical officials" />
                        <PortalTechnicalOfficials officials={sport.technical_officials} />
                    </section>
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
                        icon={heroIcon}
                        title={sport.name}
                        description={`Live scores, schedules, and standings for ${sport.name} at ${meet.name}.`}
                        meta={
                            <>
                                {heroMeta}
                                {liveNow !== null && (
                                    <span className="inline-flex items-center gap-1.5 rounded-full bg-[var(--portal-live)] px-2.5 py-1 text-xs font-bold text-[var(--portal-live-foreground)]">
                                        LIVE NOW
                                    </span>
                                )}
                            </>
                        }
                    />
                )}

                <PortalSportPhoto photoUrl={sport.photo_url} sportName={sport.name} />

                <section className="space-y-3">
                    <PortalSectionHeader title="About" />
                    <PortalSportDescription description={sport.description} />
                </section>

                <section className="space-y-3">
                    <PortalSectionHeader title="Competition categories" />
                    <PortalSportCategories categories={sport.categories} />
                </section>

                <section className="space-y-3">
                    <PortalSectionHeader title="Venue information" />
                    <PortalVenueInformation venues={venues} />
                </section>

                <section className="space-y-3">
                    <PortalSectionHeader title="Schedule summary" />
                    <PortalScheduleList
                        games={[...todayGames, ...upcomingGames].slice(0, 3)}
                        emptyTitle="Nothing scheduled yet"
                        emptyDescription="This sport's schedule appears here once set."
                    />
                </section>

                <section className="space-y-3">
                    <PortalSectionHeader title="Tournament management" />
                    <PortalTournamentManagement assignments={sport.tournament_management} />
                </section>

                <section className="space-y-3">
                    <PortalSectionHeader title="Technical officials" />
                    <PortalTechnicalOfficials officials={sport.technical_officials} />
                </section>

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
                    <Link
                        href={liveSportPortal(sport.slug).url}
                        className="flex items-center justify-between gap-3 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-5 transition-colors hover:border-[var(--portal-accent)]"
                    >
                        <span className="flex items-center gap-2 text-sm font-semibold">
                            {liveNow !== null && (
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-[var(--portal-live)] px-2.5 py-1 text-xs font-bold text-[var(--portal-live-foreground)]">
                                    LIVE
                                </span>
                            )}
                            {liveNow !== null
                                ? 'View the live scoreboard'
                                : 'Nothing live right now — view the live scoreboard page'}
                        </span>
                        <span className="inline-flex items-center gap-1 text-sm font-semibold text-[var(--portal-accent)]">
                            Open
                            <ArrowRight aria-hidden="true" className="size-4" />
                        </span>
                    </Link>
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
            </div>
        </>
    );
}
