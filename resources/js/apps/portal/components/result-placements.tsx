import type { PortalLatestResult } from '@/apps/portal/types';

type PortalResultPlacementsProps = {
    result: PortalLatestResult;
    className?: string;
};

/** The validated placement list — shared by the results page (one card
 * per event) and the scoreboard's official-result fallback (one match
 * that never used live scoring). Same markup either way, since both are
 * just "the event's official podium." */
export function PortalResultPlacements({ result, className }: PortalResultPlacementsProps) {
    return (
        <div className={className}>
            <div className="flex flex-wrap items-baseline justify-between gap-2">
                <p className="font-semibold">{result.event}</p>
                <div className="flex items-center gap-2">
                    {result.status_label && (
                        <span
                            className={
                                result.status === 'official'
                                    ? 'rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800'
                                    : 'rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800'
                            }
                        >
                            {result.status_label}
                        </span>
                    )}
                    {result.official_as_of && (
                        <p className="text-xs text-[var(--portal-muted-foreground)]">
                            {result.status === 'official' ? 'Official' : 'Accepted'} as of {result.official_as_of}
                        </p>
                    )}
                </div>
            </div>
            <ol className="mt-3 space-y-1.5 text-sm">
                {result.placements.map((placement) => (
                    <li key={placement.rank} className="flex flex-wrap items-baseline justify-between gap-2">
                        <span>
                            <span className="mr-2 font-semibold tabular-nums">{placement.rank}.</span>
                            {placement.athlete}
                            {placement.is_tie && <span className="ml-1 text-xs text-[var(--portal-muted-foreground)]">(tie)</span>}
                        </span>
                        <span className="text-[var(--portal-muted-foreground)]">
                            {placement.school} · {placement.delegation}
                            {placement.mark && <span className="ml-2 font-medium text-[var(--portal-fg)]">{placement.mark}</span>}
                        </span>
                    </li>
                ))}
            </ol>
        </div>
    );
}
