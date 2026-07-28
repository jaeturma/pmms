import { Timer } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

/**
 * Countdown to a match's real scheduled start (WP-08.5-08's "opening
 * countdown") — shown only while no live session exists yet and the
 * scheduled instant is still in the future; the moment scoring starts,
 * the caller swaps this out for the real `LiveScoreDisplay`, so this
 * never competes with or delays the live-score authority.
 *
 * Ticks via a `now` state value updated once a second in an effect,
 * rather than reading `Date.now()` during render — the same purity
 * constraint `live-score-display.tsx`'s own ticking components
 * document (the React Compiler disallows an impure render), just solved
 * with an absolute target timestamp instead of that file's relative
 * remount-key technique (there is no server-provided "elapsed" base
 * here, only a fixed target instant to count down to).
 */
export function OpeningCountdown({ startsAt }: { startsAt: string }) {
    const target = useMemo(() => new Date(startsAt).getTime(), [startsAt]);
    const [now, setNow] = useState(() => Date.now());

    useEffect(() => {
        if (Number.isNaN(target)) {
            return;
        }

        const interval = setInterval(() => {
            const tick = Date.now();
            setNow(tick);

            // Stop ticking once the target has passed (WP-08.5-09) —
            // this component renders `null` from here on anyway; no
            // reason to keep waking the component up once a second for
            // as long as the parent keeps it mounted (e.g. an unattended
            // kiosk display sitting on this page after the scheduled
            // start time with scoring still not started).
            if (tick >= target) {
                clearInterval(interval);
            }
        }, 1000);

        return () => clearInterval(interval);
    }, [target]);

    // `Number.isNaN` guards a malformed `startsAt` (graceful failure,
    // WP-08.5-09) — unreachable with real data today (the backend only
    // ever sends a real `Carbon::toIso8601String()` or `null`, and the
    // caller already skips rendering this component for `null`), but a
    // parse failure should quietly render nothing rather than a "NaN:NaN"
    // clock, since `NaN <= 0` is `false` and would otherwise fall through.
    if (Number.isNaN(target)) {
        return null;
    }

    const remainingSeconds = Math.max(0, Math.floor((target - now) / 1000));

    if (remainingSeconds <= 0) {
        return null;
    }

    const hours = Math.floor(remainingSeconds / 3600);
    const minutes = Math.floor((remainingSeconds % 3600) / 60);
    const seconds = remainingSeconds % 60;

    return (
        <div className="flex flex-col items-center gap-3 rounded-2xl border-2 bg-card px-6 py-10 text-center shadow-sm">
            <Timer
                aria-hidden="true"
                className="size-8 text-muted-foreground"
            />
            <p className="text-sm font-medium text-muted-foreground">
                Starting in
            </p>
            <p
                className="text-clock text-5xl sm:text-7xl"
                aria-label={`Starts in ${hours} hours, ${minutes} minutes, ${seconds} seconds`}
            >
                {hours > 0 && `${String(hours).padStart(2, '0')}:`}
                {String(minutes).padStart(2, '0')}:
                {String(seconds).padStart(2, '0')}
            </p>
        </div>
    );
}
