import { Link } from '@inertiajs/react';
import { Trophy } from 'lucide-react';
import { useMemo } from 'react';
import { PortalAnimatedNumber } from '@/apps/portal/components/animated-number';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { MunicipalityCrest } from '@/apps/portal/components/municipality-crest';
import { usePortalFlipRows } from '@/apps/portal/lib/use-flip-rows';
import { cn } from '@/apps/portal/lib/utils';
import { show as teamShow } from '@/routes/public/teams';

type PortalStandingsRow = {
    label: string;
    /** Stable identity for animation — the delegation/team id or slug.
     * Falls back to `label` when omitted. */
    key?: string;
    logoUrl?: string | null;
    teamLogoUrl?: string | null;
    slug?: string | null;
    gold?: number;
    silver?: number;
    bronze?: number;
    total?: number;
    points?: number;
};

type PortalStandingsTableProps = {
    rows: PortalStandingsRow[] | null;
    nameLabel?: string;
    unavailableTitle?: string;
    unavailableDescription?: string;
    /** Doubled font sizes and a bigger, square (not circular) crest — used
     * for the district/municipality ranking tables only. School
     * standings and the by-sport breakdown stay at the normal size. */
    emphasized?: boolean;
    /** School standings has no real per-school crest data worth showing
     * (unlike the municipality-level tables, which do) — set false there
     * to drop the crest/initials badge entirely. Defaults true. */
    showCrest?: boolean;
    /** Opt-in (the live public `/tally` page only): pulse increased medal
     * numbers and FLIP-slide rows to their new rank on each poll instead
     * of jumping. Every other caller renders exactly as before. */
    animate?: boolean;
};

export function PortalStandingsTable({
    rows,
    nameLabel = 'Name',
    unavailableTitle = 'Standings not available',
    unavailableDescription = 'No standings data exists for this sport yet.',
    emphasized = false,
    showCrest = true,
    animate = false,
}: PortalStandingsTableProps) {
    // A row whose Total rises since the last poll gets a brief accent —
    // driven imperatively inside the FLIP hook (no React state / re-render).
    const totalsByKey = useMemo(() => {
        const map = new Map<string, number>();

        for (const row of rows ?? []) {
            if (row.total !== undefined) {
                map.set(row.key ?? row.label, row.total);
            }
        }

        return map;
    }, [rows]);

    const { rowRef, reduced } = usePortalFlipRows(animate, {
        flashOnIncrease: totalsByKey,
    });

    if (rows === null || rows.length === 0) {
        return (
            <PortalEmptyState
                icon={Trophy}
                tone="ink"
                title={unavailableTitle}
                description={unavailableDescription}
            />
        );
    }

    const showMedals = rows[0].gold !== undefined;
    const showPoints = rows[0].points !== undefined;
    // Emphasized (the Overall standings ranking) scales up progressively
    // by breakpoint instead of a single large fixed size — a flat 28px
    // table would force horizontal scrolling on a phone; this keeps it
    // legible and unscrolled at every width, only reaching its full
    // "big readable ranking" size on a large screen.
    const cellPadding = emphasized
        ? 'px-2 py-2 sm:px-4 sm:py-3 lg:px-6 lg:py-4'
        : 'px-4 py-2';

    const medalCell = (value: number | undefined) =>
        animate && value !== undefined ? (
            <PortalAnimatedNumber value={value} reduced={reduced} />
        ) : (
            value
        );

    return (
        <div className="overflow-x-auto rounded-[var(--portal-radius)] border border-[var(--portal-border)]">
            <table
                className={cn(
                    'w-full',
                    emphasized
                        ? 'text-sm sm:text-lg md:text-xl lg:text-2xl xl:text-[28px]'
                        : 'text-sm',
                )}
            >
                <thead className="bg-[var(--portal-muted)] text-[var(--portal-muted-foreground)]">
                    <tr>
                        <th
                            className={cn(cellPadding, 'text-left font-medium')}
                        >
                            #
                        </th>
                        <th
                            className={cn(cellPadding, 'text-left font-medium')}
                        >
                            {nameLabel}
                        </th>
                        {showMedals && (
                            <>
                                <th
                                    className={cn(
                                        cellPadding,
                                        'text-center font-medium',
                                    )}
                                >
                                    Gold
                                </th>
                                <th
                                    className={cn(
                                        cellPadding,
                                        'text-center font-medium',
                                    )}
                                >
                                    Silver
                                </th>
                                <th
                                    className={cn(
                                        cellPadding,
                                        'text-center font-medium',
                                    )}
                                >
                                    Bronze
                                </th>
                                <th
                                    className={cn(
                                        cellPadding,
                                        'text-center font-medium',
                                    )}
                                >
                                    Total
                                </th>
                            </>
                        )}
                        {showPoints && (
                            <th
                                className={cn(
                                    cellPadding,
                                    'text-center font-medium',
                                )}
                            >
                                Points
                            </th>
                        )}
                    </tr>
                </thead>
                <tbody className="divide-y divide-[var(--portal-border)] bg-[var(--portal-surface)] text-[var(--portal-surface-foreground)]">
                    {rows.map((row, index) => {
                        // The podium (top 3) reads stronger than the rest of
                        // the ranking — only meaningful on the official
                        // (emphasized) standings table, not the reference-only
                        // school list.
                        const isPodium = emphasized && index < 3;
                        const rowKey = row.key ?? row.label;

                        const nameContent = (
                            <>
                                {showCrest && (
                                    <MunicipalityCrest
                                        name={row.label}
                                        logoUrl={row.teamLogoUrl ?? row.logoUrl}
                                        size="sm"
                                        shape={emphasized ? 'square' : 'circle'}
                                        className={
                                            emphasized
                                                ? 'size-8 sm:size-12 md:size-14 lg:size-16'
                                                : undefined
                                        }
                                    />
                                )}
                                {row.label}
                            </>
                        );

                        return (
                            <tr
                                key={rowKey}
                                ref={animate ? rowRef(rowKey) : undefined}
                                className={cn(
                                    animate && 'portal-tally-row',
                                    isPodium &&
                                        'font-bold text-[var(--portal-fg)]',
                                )}
                            >
                                <td className={cn(cellPadding, 'tabular-nums')}>
                                    {index + 1}
                                </td>
                                <td className={cellPadding}>
                                    {row.slug ? (
                                        <Link
                                            href={teamShow(row.slug).url}
                                            className="flex items-center gap-2 hover:underline sm:gap-3"
                                        >
                                            {nameContent}
                                        </Link>
                                    ) : (
                                        <span className="flex items-center gap-2 sm:gap-3">
                                            {nameContent}
                                        </span>
                                    )}
                                </td>
                                {showMedals && (
                                    <>
                                        <td
                                            className={cn(
                                                cellPadding,
                                                'text-center tabular-nums',
                                            )}
                                        >
                                            {medalCell(row.gold)}
                                        </td>
                                        <td
                                            className={cn(
                                                cellPadding,
                                                'text-center tabular-nums',
                                            )}
                                        >
                                            {medalCell(row.silver)}
                                        </td>
                                        <td
                                            className={cn(
                                                cellPadding,
                                                'text-center tabular-nums',
                                            )}
                                        >
                                            {medalCell(row.bronze)}
                                        </td>
                                        <td
                                            className={cn(
                                                cellPadding,
                                                'text-center tabular-nums',
                                            )}
                                        >
                                            {medalCell(row.total)}
                                        </td>
                                    </>
                                )}
                                {showPoints && (
                                    <td
                                        className={cn(
                                            cellPadding,
                                            'text-center font-semibold tabular-nums',
                                        )}
                                    >
                                        {row.points}
                                    </td>
                                )}
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}
