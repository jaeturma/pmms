import { Head, router } from '@inertiajs/react';
import { Award, Radio } from 'lucide-react';
import { useEffect, useState } from 'react';
import { PortalBasketballScoreboard } from '@/apps/portal/components/basketball-scoreboard';
import { PortalBasketballSidebar } from '@/apps/portal/components/basketball-sidebar';
import { PortalBoxingScoreboard } from '@/apps/portal/components/boxing-scoreboard';
import { PortalBoxingSidebar } from '@/apps/portal/components/boxing-sidebar';
import { PortalCountdown } from '@/apps/portal/components/countdown';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalLiveScoreCard } from '@/apps/portal/components/live-score-card';
import { PortalResultPlacements } from '@/apps/portal/components/result-placements';
import { PortalSoftballScoreboard } from '@/apps/portal/components/softball-scoreboard';
import { PortalSoftballSidebar } from '@/apps/portal/components/softball-sidebar';
import { PortalSportEventStrip } from '@/apps/portal/components/sport-event-strip';
import type {
    PortalLatestResult,
    PortalLiveSession,
    PortalMatchSummary,
    PortalMeetSummary,
} from '@/apps/portal/types';
import publicRoutes from '@/routes/public';

// Public scoreboards do not have access to the authenticated scoring Echo
// channel. Poll frequently enough that a clock pause is reflected on the
// display on the next visible second rather than continuing for 5 seconds.
const POLL_INTERVAL_MS = 1000;

type Props = {
    meet: PortalMeetSummary;
    match: PortalMatchSummary;
    session: PortalLiveSession | null;
    officialResult: PortalLatestResult | null;
};

export default function PortalScoreboard({
    meet,
    match,
    session: initialSession,
    officialResult,
}: Props) {
    const [session, setSession] = useState(initialSession);
    const [syncedSession, setSyncedSession] = useState(initialSession);
    // Same `pollFailures >= 2` visible-after-a-couple-of-misses pattern
    // as the operator console (`resources/js/pages/scoring/show.tsx`,
    // WP-08-10) — this page previously discarded every poll failure
    // silently (including a non-OK HTTP response, which was treated the
    // same as "nothing changed" rather than a failure), so a run of
    // transient errors here looked identical to a healthy connection:
    // the score just stopped updating, with nothing on screen to explain
    // why, until a manual reload.
    const [pollFailures, setPollFailures] = useState(0);

    // Adjust local state during render when a fresh Inertia prop arrives
    // (e.g. navigating from one match to another) — same
    // React-recommended alternative to syncing props into state via an
    // effect that `scoring/show.tsx` already uses. Between such visits,
    // polling updates `session` locally.
    if (initialSession !== syncedSession) {
        setSyncedSession(initialSession);
        setSession(initialSession);
    }

    useEffect(() => {
        const interval = setInterval(() => {
            fetch(
                publicRoutes.scoreboard.poll({ meet: meet.id, match: match.id })
                    .url,
                {
                    cache: 'no-store',
                    headers: { Accept: 'application/json' },
                },
            )
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`poll failed: ${response.status}`);
                    }

                    return response.json() as Promise<{
                        session: PortalLiveSession | null;
                    }>;
                })
                .then((data) => {
                    setSession(data.session);
                    setPollFailures(0);
                })
                .catch(() => {
                    // Retries on its own next tick — no user action
                    // needed, but the banner below flags it after a
                    // couple of misses.
                    setPollFailures((n) => n + 1);
                });
        }, POLL_INTERVAL_MS);

        return () => clearInterval(interval);
    }, [meet.id, match.id]);

    const isBasketball =
        session !== null && session.board_type === 'basketball';
    const isSoftball =
        session !== null && session.board_type === 'softball_baseball';
    const isBoxing = session !== null && session.board_type === 'boxing';

    return (
        <>
            <Head title={`${match.event} — Live Scoreboard`} />
            <div className="flex flex-col gap-6">
                {isBasketball || isSoftball || isBoxing ? (
                    <PortalSportEventStrip
                        sportEmoji={
                            isBasketball ? '🏀' : isSoftball ? '🥎' : '🥊'
                        }
                        sportName={match.sport}
                        category={match.category}
                        roundLabel={match.round_label}
                        live={session?.status !== 'ended'}
                        scheduledDate={match.scheduled_date}
                        startsAt={match.starts_at}
                    />
                ) : (
                    <PortalHero
                        eyebrow="Live scoreboard"
                        title={match.event}
                        description={`${match.category}${match.round_label ? ` · ${match.round_label}` : ''} — always provisional, never the official result.`}
                    />
                )}

                {session === null && match.status === 'scheduled' ? (
                    <div className="flex flex-col items-center gap-3">
                        <PortalEmptyState
                            icon={Radio}
                            title="Not live yet"
                            description="This match hasn't started scoring."
                        />
                        {match.scheduled_start_at && (
                            <PortalCountdown
                                targetIso={match.scheduled_start_at}
                            />
                        )}
                    </div>
                ) : session === null ? (
                    // This match concluded without ever using live scoring
                    // (no `ScoringSession` row exists at all) — its record
                    // is whatever the official encode/validate pipeline
                    // produced for the whole event, not a per-match log.
                    officialResult ? (
                        <PortalResultPlacements
                            result={officialResult}
                            className="portal-animate-in rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-5"
                        />
                    ) : (
                        <PortalEmptyState
                            icon={Award}
                            title="No result recorded"
                            description="This match concluded without live scoring, and no official result has been validated for this event yet."
                        />
                    )
                ) : isBasketball ? (
                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-[2.15fr_0.9fr] lg:items-start">
                        <PortalBasketballScoreboard
                            liveNow={{
                                match_id: match.id,
                                round_label: match.round_label,
                                category: match.category,
                                venue: match.venue,
                                scheduled_date: match.scheduled_date,
                                starts_at: match.starts_at,
                                session,
                            }}
                        />
                        <PortalBasketballSidebar session={session} />
                    </div>
                ) : isSoftball ? (
                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-[2.15fr_0.95fr] lg:items-start">
                        <PortalSoftballScoreboard
                            liveNow={{
                                match_id: match.id,
                                round_label: match.round_label,
                                category: match.category,
                                venue: match.venue,
                                scheduled_date: match.scheduled_date,
                                starts_at: match.starts_at,
                                session,
                            }}
                        />
                        <PortalSoftballSidebar session={session} />
                    </div>
                ) : isBoxing ? (
                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-[2.15fr_0.95fr] lg:items-start">
                        <PortalBoxingScoreboard
                            liveNow={{
                                match_id: match.id,
                                round_label: match.round_label,
                                category: match.category,
                                venue: match.venue,
                                scheduled_date: match.scheduled_date,
                                starts_at: match.starts_at,
                                session,
                            }}
                        />
                        <PortalBoxingSidebar
                            liveNow={{
                                match_id: match.id,
                                round_label: match.round_label,
                                category: match.category,
                                venue: match.venue,
                                scheduled_date: match.scheduled_date,
                                starts_at: match.starts_at,
                                session,
                            }}
                        />
                    </div>
                ) : (
                    <PortalLiveScoreCard
                        liveNow={{
                            match_id: match.id,
                            round_label: match.round_label,
                            category: match.category,
                            venue: match.venue,
                            scheduled_date: match.scheduled_date,
                            starts_at: match.starts_at,
                            session,
                        }}
                    />
                )}

                <div className="flex items-center gap-3 self-start">
                    {pollFailures >= 2 && (
                        <span className="text-xs font-medium text-[var(--portal-warning)]">
                            Reconnecting — scores may be behind
                        </span>
                    )}
                    <button
                        type="button"
                        onClick={() => router.reload()}
                        className="text-xs text-[var(--portal-muted-foreground)] underline underline-offset-2"
                    >
                        Refresh
                    </button>
                </div>
            </div>
        </>
    );
}
