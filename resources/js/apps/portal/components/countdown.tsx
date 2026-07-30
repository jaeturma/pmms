import { useEffect, useState } from 'react';

type PortalCountdownProps = {
    targetIso: string;
};

function remaining(targetIso: string): number {
    return Math.max(0, new Date(targetIso).getTime() - Date.now());
}

export function PortalCountdown({ targetIso }: PortalCountdownProps) {
    const [ms, setMs] = useState(() => remaining(targetIso));

    useEffect(() => {
        const interval = setInterval(() => setMs(remaining(targetIso)), 1000);

        return () => clearInterval(interval);
    }, [targetIso]);

    if (ms <= 0) {
        return <p className="text-sm text-[var(--portal-muted-foreground)]">Starting soon</p>;
    }

    const totalSeconds = Math.floor(ms / 1000);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    return (
        <p className="font-mono text-lg font-semibold tabular-nums">
            {String(hours).padStart(2, '0')}:{String(minutes).padStart(2, '0')}:{String(seconds).padStart(2, '0')}
        </p>
    );
}
