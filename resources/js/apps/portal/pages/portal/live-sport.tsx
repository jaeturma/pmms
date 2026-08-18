import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Trophy } from 'lucide-react';
import { useEffect, useState } from 'react';
import { PortalBasketballScoreboard } from '@/apps/portal/components/basketball-scoreboard';
import { PortalBasketballSidebar } from '@/apps/portal/components/basketball-sidebar';
import { PortalBoxingScoreboard } from '@/apps/portal/components/boxing-scoreboard';
import { PortalBoxingSidebar } from '@/apps/portal/components/boxing-sidebar';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalLiveScoreCard } from '@/apps/portal/components/live-score-card';
import { PortalSoftballScoreboard } from '@/apps/portal/components/softball-scoreboard';
import { PortalSoftballSidebar } from '@/apps/portal/components/softball-sidebar';
import { PortalSportIcon } from '@/apps/portal/components/sport-icon';
import { usePortalPageVisible } from '@/apps/portal/lib/use-page-visible';
import type { PortalLiveNow, PortalMeetSummary, PortalSport } from '@/apps/portal/types';
import { sportPortal } from '@/routes/public';
import { poll as pollSportPortal } from '@/routes/public/sport-portal';

// Guest scoreboards cannot subscribe to the scorer's private Echo channel,
// so clock controls need a short polling interval to remain in sync.
const LIVE_POLL_INTERVAL_MS = 1000;

type Props = {
    sport: PortalSport;
    canonicalUrl: string;
    meet: PortalMeetSummary | null;
    liveNow: PortalLiveNow | null;
    otherLiveCount: number;
};

/**
 * The dedicated live-scoreboard page (`/live/{sportSlug}`) — the sport
 * portal page's own "Live now" section links out here instead of
 * embedding the full scoreboard inline (basketball's mega scoreboard was
 * crowding that page). Same board-type branching and polling contract as
 * `sport-portal.tsx`'s own Live Now section, just on its own page.
 */
export default function PortalLiveSport({
    sport,
    canonicalUrl,
    meet,
    liveNow: initialLiveNow,
    otherLiveCount: initialOtherLiveCount,
}: Props) {
    const visible = usePortalPageVisible();
    const [liveNow, setLiveNow] = useState(initialLiveNow);
    const [otherLiveCount, setOtherLiveCount] = useState(initialOtherLiveCount);
    const [pollFailures, setPollFailures] = useState(0);

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
                    setPollFailures((n) => n + 1);
                });
        }, LIVE_POLL_INTERVAL_MS);

        return () => clearInterval(interval);
    }, [visible, meet, sport.slug]);

    const showBasketballLive = liveNow !== null && liveNow.session.board_type === 'basketball';
    const showSoftballLive = liveNow !== null && liveNow.session.board_type === 'softball_baseball';
    const showBoxingLive = liveNow !== null && liveNow.session.board_type === 'boxing';

    return (
        <>
            <Head title={`${sport.name} — Live`}>
                <link head-key="canonical" rel="canonical" href={canonicalUrl} />
            </Head>
            <div className="flex flex-col gap-6">
                <PortalHero
                    icon={<PortalSportIcon slug={sport.slug} className="size-16 shrink-0 border-2 border-[var(--portal-ink-foreground)]/20 [&>svg]:size-8 sm:size-20 sm:[&>svg]:size-10" />}
                    title={`${sport.name} — Live`}
                    description={meet ? `Live scoreboard for ${sport.name} at ${meet.name}.` : undefined}
                    actions={
                        <Link
                            href={sportPortal(sport.slug).url}
                            className="inline-flex items-center gap-1.5 text-sm font-semibold text-[var(--portal-ink-foreground)]/80 hover:text-[var(--portal-ink-foreground)]"
                        >
                            <ArrowLeft aria-hidden="true" className="size-4" />
                            Back to {sport.name}
                        </Link>
                    }
                />

                {liveNow !== null && pollFailures >= 2 && (
                    <span className="text-xs font-medium text-[var(--portal-warning)]">
                        Reconnecting — scores may be behind
                    </span>
                )}

                {meet === null || liveNow === null ? (
                    <PortalEmptyState icon={Trophy} title="Nothing live right now" />
                ) : (
                    <>
                        {otherLiveCount > 0 && (
                            <span className="text-sm text-[var(--portal-muted-foreground)]">
                                +{otherLiveCount} other game live
                            </span>
                        )}

                        {showBasketballLive ? (
                            <div className="grid grid-cols-1 gap-5 lg:grid-cols-[2.15fr_0.9fr] lg:items-start">
                                <PortalBasketballScoreboard liveNow={liveNow} />
                                <PortalBasketballSidebar session={liveNow.session} />
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
                    </>
                )}
            </div>
        </>
    );
}
