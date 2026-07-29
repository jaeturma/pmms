import { Radio } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { EmptyState } from '@/components/empty-state';
import { LiveBadge } from '@/components/live-badge';
import type { LiveSession } from '@/components/live-score-display';
import { LiveScoreDisplay } from '@/components/live-score-display';
import { usePageVisible } from '@/hooks/use-page-visible';

export type SportPortalLiveNow = {
    match_id: number;
    round_label: string;
    category: string;
    venue: string | null;
    session: LiveSession;
};

type Props = {
    initialLiveNow: SportPortalLiveNow | null;
    initialOtherLiveCount: number;
    pollUrl: string;
};

/**
 * The sport portal's "Live Now" section (Phase 12) — unlike the
 * single-match public scoreboard, the featured match here can change
 * between polls (a different match for this sport may go live), so
 * this re-resolves fresh each poll rather than tracking one match id.
 * 7s polling (brief §9's 5-10s band), paused while the tab is hidden
 * (WP-12-06) via `usePageVisible()` — the effect's own cleanup already
 * clears the interval whenever `visible` flips, so pausing/resuming is
 * just "let the effect re-run," no separate stop/start bookkeeping.
 */
export function SportPortalLiveNowCard({
    initialLiveNow,
    initialOtherLiveCount,
    pollUrl,
}: Props) {
    const [liveNow, setLiveNow] = useState(initialLiveNow);
    const [otherLiveCount, setOtherLiveCount] = useState(initialOtherLiveCount);
    const [pollFailures, setPollFailures] = useState(0);
    const [lastUpdatedAt, setLastUpdatedAt] = useState(() => Date.now());
    const [fullscreen, setFullscreen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);
    const visible = usePageVisible();

    useEffect(() => {
        if (!visible) {
            return;
        }

        const interval = setInterval(() => {
            fetch(pollUrl, { headers: { Accept: 'application/json' } })
                .then((response) => response.json())
                .then(
                    (data: {
                        liveNow: SportPortalLiveNow | null;
                        otherLiveCount: number;
                    }) => {
                        setLiveNow(data.liveNow);
                        setOtherLiveCount(data.otherLiveCount);
                        setPollFailures(0);
                        setLastUpdatedAt(Date.now());
                    },
                )
                .catch(() => setPollFailures((n) => n + 1));
        }, 7000);

        return () => clearInterval(interval);
    }, [pollUrl, visible]);

    useEffect(() => {
        const onFullscreenChange = () =>
            setFullscreen(document.fullscreenElement !== null);
        document.addEventListener('fullscreenchange', onFullscreenChange);

        return () =>
            document.removeEventListener(
                'fullscreenchange',
                onFullscreenChange,
            );
    }, []);

    const toggleFullscreen = () => {
        if (document.fullscreenElement) {
            void document.exitFullscreen();
        } else {
            void containerRef.current?.requestFullscreen();
        }
    };

    if (liveNow === null) {
        return (
            <EmptyState
                icon={Radio}
                title="No live event right now"
                description="Check back once a match for this sport goes live."
            />
        );
    }

    return (
        <div className="flex flex-col gap-3">
            <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                <LiveBadge label="Live now" />
                <span>{liveNow.category}</span>
                <span aria-hidden="true">·</span>
                <span>{liveNow.round_label}</span>
                {liveNow.venue && (
                    <>
                        <span aria-hidden="true">·</span>
                        <span>{liveNow.venue}</span>
                    </>
                )}
                {otherLiveCount > 0 && (
                    <span className="text-xs">
                        +{otherLiveCount} other live{' '}
                        {otherLiveCount === 1 ? 'match' : 'matches'}
                    </span>
                )}
            </div>
            <div ref={containerRef}>
                <LiveScoreDisplay
                    session={liveNow.session}
                    fullscreen={fullscreen}
                    onToggleFullscreen={toggleFullscreen}
                    disconnected={pollFailures >= 2}
                    lastUpdatedAt={lastUpdatedAt}
                />
            </div>
        </div>
    );
}
