import { useState } from 'react';
import { usePortalReducedMotion } from '@/apps/portal/lib/use-reduced-motion';
import { cn } from '@/apps/portal/lib/utils';

type PortalAnimatedNumberProps = {
    value: number;
    className?: string;
    /** Pass down from a parent that already resolved the preference so a
     * table of ~100 numbers doesn't open ~100 media-query listeners. */
    reduced?: boolean;
};

/** Renders a tally number and briefly pulses it (colour + gentle scale)
 * the moment it *increases*. Unchanged values never animate; a decrease
 * (a correction/reopen) updates silently. Honours
 * `prefers-reduced-motion`. Pure CSS transform/colour — no reflow, so it
 * can never widen or shift the column. */
export function PortalAnimatedNumber({
    value,
    className,
    reduced: reducedProp,
}: PortalAnimatedNumberProps) {
    const reducedFallback = usePortalReducedMotion();
    const reduced = reducedProp ?? reducedFallback;
    const [seen, setSeen] = useState(value);
    // A monotonic id: bumping it remounts the span (via `key`), which
    // replays the one-shot CSS animation from the start — no timer, no
    // "now clear the class" bookkeeping.
    const [pulse, setPulse] = useState(0);

    // "Adjust state while rendering" (react.dev) rather than an effect —
    // React re-renders immediately, before paint, so there is no flash.
    if (value !== seen) {
        setSeen(value);

        if (value > seen && !reduced) {
            setPulse((n) => n + 1);
        }
    }

    return (
        <span
            key={pulse}
            className={cn(
                'portal-tally-num',
                pulse > 0 && 'portal-tally-num--bump',
                className,
            )}
        >
            {value}
        </span>
    );
}
