import { useEffect, useState } from 'react';

/**
 * The current time, refreshed every 30 seconds — enough for a
 * "May 14, 2025 · 9:45 AM"-style header clock (WP-08-03) without the
 * unnecessary per-second re-renders a `setInterval(..., 1000)` would cause.
 */
export function useClock(): Date {
    const [now, setNow] = useState(() => new Date());

    useEffect(() => {
        const interval = setInterval(() => setNow(new Date()), 1_000);

        return () => clearInterval(interval);
    }, []);

    return now;
}
