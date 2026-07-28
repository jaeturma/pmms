import { Crown } from 'lucide-react';
import { medalToneClasses } from '@/components/rank-badge';
import { TeamLogo } from '@/components/team-logo';
import { cn } from '@/lib/utils';

export type PodiumPlacement = {
    rank: number;
    athlete: string;
    school: string;
    delegation: string;
    mark: string | null;
    is_tie: boolean;
};

const STEP_HEIGHT: Record<1 | 2 | 3, string> = {
    1: 'h-28 sm:h-36',
    2: 'h-20 sm:h-28',
    3: 'h-14 sm:h-20',
};

/** Left-center-right visual order for a podium — silver left, gold
 * center (tallest), bronze right, the conventional broadcast staging. */
const STEP_ORDER: (1 | 2 | 3)[] = [2, 1, 3];

function PodiumStep({ placement }: { placement: PodiumPlacement }) {
    const position = placement.rank as 1 | 2 | 3;

    return (
        <div className="flex flex-1 flex-col items-center gap-2 text-center">
            {position === 1 && (
                <Crown
                    aria-hidden="true"
                    className="size-5 text-medal-gold sm:size-6"
                />
            )}
            <TeamLogo
                name={placement.delegation}
                shape="circle"
                className="size-12 text-base sm:size-16 sm:text-xl"
            />
            <div className="min-w-0">
                <p className="max-w-24 truncate text-sm font-semibold sm:max-w-32 sm:text-base">
                    {placement.athlete}
                    {placement.is_tie && (
                        <span className="text-muted-foreground"> (tie)</span>
                    )}
                </p>
                <p className="max-w-24 truncate text-xs text-muted-foreground sm:max-w-32">
                    {placement.school}
                </p>
                <p className="max-w-24 truncate text-xs font-medium text-muted-foreground sm:max-w-32">
                    {placement.delegation}
                </p>
                {placement.mark && (
                    <p className="text-xs font-semibold tabular-nums sm:text-sm">
                        {placement.mark}
                    </p>
                )}
            </div>
            <div
                className={cn(
                    'flex w-16 items-start justify-center rounded-t-lg pt-1 text-lg font-bold sm:w-24 sm:text-2xl',
                    STEP_HEIGHT[position],
                    medalToneClasses[position],
                )}
            >
                {position}
            </div>
        </div>
    );
}

/**
 * Top-3 podium staging for a finalized event result (WP-08.5-08) — real
 * data only (`athlete`/`school`/`delegation`/`mark`), never a live or
 * provisional score; every caller already filters to validated results
 * before reaching this component. Silver-gold-bronze left-to-right,
 * gold tallest and center, the conventional broadcast order. Renders
 * nothing for an empty list — the caller decides what to show instead
 * (an event can have zero, one, two, or three top placements).
 */
export function PodiumDisplay({
    placements,
}: {
    placements: PodiumPlacement[];
}) {
    if (placements.length === 0) {
        return null;
    }

    const byRank = new Map(placements.map((p) => [p.rank, p]));

    return (
        <div className="flex flex-col items-center gap-3 py-2">
            {byRank.has(1) && (
                <p className="text-sm text-muted-foreground">
                    Champion delegation:{' '}
                    <span className="font-semibold text-foreground">
                        {byRank.get(1)?.delegation}
                    </span>
                </p>
            )}
            <div className="flex w-full max-w-md items-end justify-center gap-2 sm:gap-4">
                {STEP_ORDER.filter((rank) => byRank.has(rank)).map((rank) => (
                    <PodiumStep key={rank} placement={byRank.get(rank)!} />
                ))}
            </div>
        </div>
    );
}
