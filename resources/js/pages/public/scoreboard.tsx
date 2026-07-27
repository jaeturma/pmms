import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Radio } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { EmptyState } from '@/components/empty-state';
import type { LiveSession } from '@/components/live-score-display';
import { LiveScoreDisplay } from '@/components/live-score-display';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { meet as publicMeet } from '@/routes/public';
import { poll as pollScoreboard } from '@/routes/public/scoreboard';

type Props = {
    meet: {
        id: number;
        name: string;
        school_year: string;
        starts_at: string;
        ends_at: string;
        venue: string | null;
        status_label: string;
    };
    match: {
        id: number;
        event: string;
        sport: string;
        category: string;
        round_label: string;
        venue: string | null;
        scheduled_date: string | null;
    };
    session: LiveSession | null;
};

export default function PublicScoreboard({
    meet,
    match,
    session: initialSession,
}: Props) {
    const [session, setSession] = useState(initialSession);
    const [pollFailures, setPollFailures] = useState(0);
    const [fullscreen, setFullscreen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    // Public page — polling only, no Reverb subscription for guests. Same
    // 5-second baseline every internal live-scoring page already proves
    // works standalone.
    useEffect(() => {
        const interval = setInterval(() => {
            fetch(pollScoreboard({ meet: meet.id, match: match.id }).url, {
                headers: { Accept: 'application/json' },
            })
                .then((response) => response.json())
                .then((data: { session: LiveSession | null }) => {
                    setSession(data.session);
                    setPollFailures(0);
                })
                .catch(() => {
                    // Polling retries on its own next tick — no user
                    // action needed, but the display flags it after a
                    // couple of misses (WP-08-10).
                    setPollFailures((n) => n + 1);
                });
        }, 5000);

        return () => clearInterval(interval);
    }, [meet.id, match.id]);

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

    return (
        <>
            <Head title={`Live scoring — ${match.event}`} />
            <div className="flex flex-col gap-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {match.event}
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {meet.name} · {match.round_label}
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted-foreground">
                    <span className="font-medium text-foreground">
                        {match.sport}
                    </span>
                    <span aria-hidden="true">›</span>
                    <span>{match.category}</span>
                    <span aria-hidden="true">›</span>
                    <span>{match.round_label}</span>
                    {session !== null && session.status !== 'ended' && (
                        <Badge variant="destructive" className="ml-1">
                            Live now
                        </Badge>
                    )}
                    {(match.scheduled_date || match.venue) && (
                        <span className="w-full text-xs">
                            {[match.scheduled_date, match.venue]
                                .filter(Boolean)
                                .join(' · ')}
                        </span>
                    )}
                </div>

                <Badge variant="outline" className="w-fit">
                    Live score — provisional, not the official result
                </Badge>

                <Button variant="outline" size="sm" className="w-fit" asChild>
                    <Link href={publicMeet(meet.id)}>
                        <ArrowLeft aria-hidden="true" />
                        Back to schedule
                    </Link>
                </Button>

                {session === null ? (
                    <EmptyState
                        icon={Radio}
                        title="No live session"
                        description="Live scoring hasn't started for this match yet."
                    />
                ) : (
                    <div
                        ref={containerRef}
                        className={
                            fullscreen
                                ? 'flex flex-1 flex-col items-center justify-center gap-8 bg-background p-8'
                                : 'flex flex-col gap-4'
                        }
                    >
                        <LiveScoreDisplay
                            session={session}
                            fullscreen={fullscreen}
                            onToggleFullscreen={toggleFullscreen}
                            disconnected={pollFailures >= 2}
                        />
                    </div>
                )}
            </div>
        </>
    );
}
