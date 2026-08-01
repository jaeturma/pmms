import { useEffect, useState } from 'react';
import { cn } from '@/apps/portal/lib/utils';

type PortalCountdownProps = {
    targetIso: string;
    /** 'inline' (default) is the original compact `HH:MM:SS` mono line
     * used by the scoreboard's "not live yet" empty state. 'display' is
     * a larger, boxed Days/Hours/Minutes/Seconds layout for the landing
     * hero — same ticking logic, different presentation. */
    variant?: 'inline' | 'display';
    /** When the target has already passed: 'inline' shows "Starting
     * soon" (the original behavior); 'hide' renders nothing, for a hero
     * that already has its own "meet is underway" messaging elsewhere. */
    onElapsed?: 'message' | 'hide';
    className?: string;
};

function remaining(targetIso: string): number {
    return Math.max(0, new Date(targetIso).getTime() - Date.now());
}

function displayUnits(ms: number): Array<{ label: string; value: number }> {
    const totalSeconds = Math.floor(ms / 1000);

    return [
        { label: 'Days', value: Math.floor(totalSeconds / 86400) },
        { label: 'Hours', value: Math.floor((totalSeconds % 86400) / 3600) },
        { label: 'Min', value: Math.floor((totalSeconds % 3600) / 60) },
        { label: 'Sec', value: totalSeconds % 60 },
    ];
}

export function PortalCountdown({ targetIso, variant = 'inline', onElapsed = 'message', className }: PortalCountdownProps) {
    const [ms, setMs] = useState(() => remaining(targetIso));

    useEffect(() => {
        const interval = setInterval(() => setMs(remaining(targetIso)), 1000);

        return () => clearInterval(interval);
    }, [targetIso]);

    if (ms <= 0) {
        if (onElapsed === 'hide') {
            return null;
        }

        return <p className={cn('text-sm text-[var(--portal-muted-foreground)]', className)}>Starting soon</p>;
    }

    if (variant === 'display') {
        return (
            <div className={cn('flex items-start gap-2.5 sm:gap-4', className)} role="timer" aria-label="Time until the meet begins">
                {displayUnits(ms).map((unit) => (
                    <div
                        key={unit.label}
                        className="flex w-[58px] flex-col items-center rounded-[var(--portal-radius)] border border-white/15 bg-white/10 py-2.5 backdrop-blur-sm sm:w-[76px] sm:py-3.5"
                    >
                        <span className="font-mono text-2xl font-bold tabular-nums text-white sm:text-4xl">{String(unit.value).padStart(2, '0')}</span>
                        <span className="mt-0.5 text-[10px] font-semibold tracking-wide text-white/70 uppercase sm:text-xs">{unit.label}</span>
                    </div>
                ))}
            </div>
        );
    }

    const totalSeconds = Math.floor(ms / 1000);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    return (
        <p className={cn('font-mono text-lg font-semibold tabular-nums', className)}>
            {String(hours).padStart(2, '0')}:{String(minutes).padStart(2, '0')}:{String(seconds).padStart(2, '0')}
        </p>
    );
}
