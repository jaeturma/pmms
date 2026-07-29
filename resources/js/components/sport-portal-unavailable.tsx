import type { LucideIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';

/**
 * The honest "not available yet" state for Standings, Leading Scorers,
 * and Tournament Bracket (Phase 12) — no team win/loss aggregation,
 * per-athlete point attribution, or bracket-tree data exists anywhere
 * in this schema for any sport (`docs/phases/phase-12-lightweight-
 * sport-mini-portals/DATA-CONTRACT-MAP.md` §D/E/F), so this never
 * fabricates a number. Same resolution `public/athletics.tsx` already
 * uses for its own real data gap.
 */
export function SportPortalUnavailable({
    icon,
    title,
}: {
    icon: LucideIcon;
    title: string;
}) {
    return (
        <EmptyState
            icon={icon}
            title={`${title} not available yet`}
            description="This system doesn't track this yet — there's simply no data to show, nothing is being hidden."
        />
    );
}
