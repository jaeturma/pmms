import { Crown } from 'lucide-react';
import { cn } from '@/lib/utils';

export const medalToneClasses: Record<1 | 2 | 3, string> = {
    1: 'bg-medal-gold text-medal-gold-foreground',
    2: 'bg-medal-silver text-medal-silver-foreground',
    3: 'bg-medal-bronze text-medal-bronze-foreground',
};

/**
 * Rank 1 gets a small crown instead of the bare number (Phase 8.5,
 * WP-08.5-02's "medal visuals" — the number alone read as just another
 * table cell, not a podium moment). Ranks 2/3 keep their medal-toned
 * number; the crown stays `aria-hidden` since "1" is still the
 * meaningful value for assistive tech — this is a visual reinforcement,
 * not new information.
 */
export function RankBadge({
    position,
    celebrate = false,
}: {
    position: number;
    /** WP-08.5-06's "winner celebration only after finalization" — the
     * caller opts in explicitly, rather than this component guessing, so
     * it only ever plays where the caller can vouch the data is a
     * validated/finalized placement (never the live scoreboard, which
     * never passes this). A one-time pop on mount, not the full podium
     * staging (WP-08.5-08's own scope). */
    celebrate?: boolean;
}) {
    const tone = medalToneClasses[position as 1 | 2 | 3];

    return (
        <span
            className={cn(
                'flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                tone ?? 'bg-muted text-muted-foreground',
                celebrate && position === 1 && 'animate-winner-in',
            )}
        >
            {position === 1 ? (
                <Crown aria-hidden="true" className="size-3.5" />
            ) : (
                position
            )}
        </span>
    );
}
