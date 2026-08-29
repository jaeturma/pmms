import { useEffect, useState } from 'react';
import {
    MunicipalityCrest,
    MunicipalityWatermark,
} from '@/apps/portal/components/municipality-crest';
import { cn } from '@/apps/portal/lib/utils';
import type { PortalLiveNow } from '@/apps/portal/types';

type PortalLiveScoreCardProps = {
    liveNow: PortalLiveNow;
    className?: string;
};

function formatClock(totalSeconds: number): string {
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

export function PortalLiveScoreCard({
    liveNow,
    className,
}: PortalLiveScoreCardProps) {
    const { session } = liveNow;
    const [elapsed, setElapsed] = useState(session.elapsed_seconds);

    useEffect(() => {
        setElapsed(session.elapsed_seconds);

        if (!session.clock_running) {
            return;
        }

        const interval = setInterval(
            () => setElapsed((value) => value + 1),
            1000,
        );

        return () => clearInterval(interval);
    }, [session.elapsed_seconds, session.clock_running]);

    return (
        <div
            className={cn(
                'portal-animate-in relative overflow-hidden rounded-[var(--portal-radius)] border border-[var(--portal-live)]/30 bg-[var(--portal-surface)] text-[var(--portal-surface-foreground)]',
                className,
            )}
        >
            {session.side_a_label && (
                <MunicipalityWatermark
                    name={session.side_a_label}
                    className="-top-6 -left-6 size-32 text-6xl sm:size-40 sm:text-7xl"
                />
            )}
            {session.side_b_label && (
                <MunicipalityWatermark
                    name={session.side_b_label}
                    className="-right-6 -bottom-6 size-32 text-6xl sm:size-40 sm:text-7xl"
                />
            )}

            <div className="relative flex items-center justify-between gap-2 bg-[var(--portal-live)] px-4 py-2 text-[var(--portal-live-foreground)]">
                <span className="flex items-center gap-1.5 text-xs font-semibold tracking-wide uppercase">
                    <span
                        aria-hidden="true"
                        className="portal-live-dot size-2 rounded-full bg-current"
                    />
                    Live
                </span>
                <span className="text-xs">{liveNow.category}</span>
            </div>

            <div className="relative grid grid-cols-[1fr_auto_1fr] items-center gap-3 px-4 py-5">
                <span className="flex min-w-0 items-center gap-2">
                    {session.side_a_label && (
                        <MunicipalityCrest
                            name={session.side_a_label}
                            logoUrl={session.side_a_logo_url}
                            size="sm"
                            shape="square"
                        />
                    )}
                    <span className="truncate text-sm font-medium">
                        {session.side_a_label ?? 'TBD'}
                    </span>
                </span>
                <p className="text-2xl font-bold tabular-nums sm:text-4xl">
                    {session.score_a ?? 0}
                    <span className="mx-1 text-[var(--portal-muted-foreground)]">
                        –
                    </span>
                    {session.score_b ?? 0}
                </p>
                <span className="flex min-w-0 items-center justify-end gap-2">
                    <span className="truncate text-right text-sm font-medium">
                        {session.side_b_label ?? 'TBD'}
                    </span>
                    {session.side_b_label && (
                        <MunicipalityCrest
                            name={session.side_b_label}
                            logoUrl={session.side_b_logo_url}
                            size="sm"
                            shape="square"
                        />
                    )}
                </span>
            </div>

            <div className="relative flex items-center justify-between gap-2 border-t border-[var(--portal-border)] px-4 py-2 text-xs text-[var(--portal-muted-foreground)]">
                <span>{session.period_label ?? session.status_label}</span>
                {session.started_at && (
                    <span className="font-mono tabular-nums">
                        {formatClock(elapsed)}
                    </span>
                )}
                {liveNow.venue && <span>{liveNow.venue}</span>}
            </div>
        </div>
    );
}
