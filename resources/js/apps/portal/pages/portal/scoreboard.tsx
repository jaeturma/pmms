import { Head, router } from '@inertiajs/react';
import { Radio } from 'lucide-react';
import { useEffect, useState } from 'react';
import publicRoutes from '@/routes/public';
import { PortalCountdown } from '@/apps/portal/components/countdown';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalLiveScoreCard } from '@/apps/portal/components/live-score-card';
import type { PortalLiveSession, PortalMatchSummary, PortalMeetSummary } from '@/apps/portal/types';

const POLL_INTERVAL_MS = 5000;

type Props = {
    meet: PortalMeetSummary;
    match: PortalMatchSummary;
    session: PortalLiveSession | null;
};

export default function PortalScoreboard({ meet, match, session: initialSession }: Props) {
    const [session, setSession] = useState(initialSession);

    useEffect(() => setSession(initialSession), [initialSession]);

    useEffect(() => {
        const interval = setInterval(() => {
            fetch(publicRoutes.scoreboard.poll({ meet: meet.id, match: match.id }).url, { headers: { Accept: 'application/json' } })
                .then((response) => (response.ok ? response.json() : null))
                .then((data: { session: PortalLiveSession | null } | null) => {
                    if (data) {
                        setSession(data.session);
                    }
                })
                .catch(() => {
                    /* transient network failure — next poll retries */
                });
        }, POLL_INTERVAL_MS);

        return () => clearInterval(interval);
    }, [meet.id, match.id]);

    return (
        <>
            <Head title={`${match.event} — Live Scoreboard`} />
            <div className="flex flex-col gap-6">
                <PortalHero
                    eyebrow="Live scoreboard"
                    title={match.event}
                    description={`${match.category}${match.round_label ? ` · ${match.round_label}` : ''} — always provisional, never the official result.`}
                />

                {session === null ? (
                    <div className="flex flex-col items-center gap-3">
                        <PortalEmptyState icon={Radio} title="Not live yet" description="This match hasn't started scoring." />
                        {match.scheduled_start_at && <PortalCountdown targetIso={match.scheduled_start_at} />}
                    </div>
                ) : (
                    <PortalLiveScoreCard
                        liveNow={{
                            match_id: match.id,
                            round_label: match.round_label,
                            category: match.category,
                            venue: match.venue,
                            session,
                        }}
                    />
                )}

                <button
                    type="button"
                    onClick={() => router.reload()}
                    className="self-start text-xs text-[var(--portal-muted-foreground)] underline underline-offset-2"
                >
                    Refresh
                </button>
            </div>
        </>
    );
}
