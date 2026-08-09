import { Link } from '@inertiajs/react';
import { Trophy } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { MunicipalityCrest } from '@/apps/portal/components/municipality-crest';
import { cn } from '@/apps/portal/lib/utils';
import { show as teamShow } from '@/routes/public/teams';

type PortalStandingsRow = {
    label: string;
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
};

export function PortalStandingsTable({
    rows,
    nameLabel = 'Name',
    unavailableTitle = 'Standings not available',
    unavailableDescription = 'No standings data exists for this sport yet.',
    emphasized = false,
    showCrest = true,
}: PortalStandingsTableProps) {
    if (rows === null || rows.length === 0) {
        return <PortalEmptyState icon={Trophy} tone="ink" title={unavailableTitle} description={unavailableDescription} />;
    }

    const showMedals = rows[0].gold !== undefined;
    const showPoints = rows[0].points !== undefined;
    // Emphasized (the Overall standings ranking) scales up progressively
    // by breakpoint instead of a single large fixed size — a flat 28px
    // table would force horizontal scrolling on a phone; this keeps it
    // legible and unscrolled at every width, only reaching its full
    // "big readable ranking" size on a large screen.
    const cellPadding = emphasized ? 'px-2 py-2 sm:px-4 sm:py-3 lg:px-6 lg:py-4' : 'px-4 py-2';

    return (
        <div className="overflow-x-auto rounded-[var(--portal-radius)] border border-[var(--portal-border)]">
            <table className={cn('w-full', emphasized ? 'text-sm sm:text-lg md:text-xl lg:text-2xl xl:text-[28px]' : 'text-sm')}>
                <thead className="bg-[var(--portal-muted)] text-[var(--portal-muted-foreground)]">
                    <tr>
                        <th className={cn(cellPadding, 'text-left font-medium')}>#</th>
                        <th className={cn(cellPadding, 'text-left font-medium')}>{nameLabel}</th>
                        {showMedals && (
                            <>
                                <th className={cn(cellPadding, 'text-center font-medium')}>Gold</th>
                                <th className={cn(cellPadding, 'text-center font-medium')}>Silver</th>
                                <th className={cn(cellPadding, 'text-center font-medium')}>Bronze</th>
                                <th className={cn(cellPadding, 'text-center font-medium')}>Total</th>
                            </>
                        )}
                        {showPoints && <th className={cn(cellPadding, 'text-center font-medium')}>Points</th>}
                    </tr>
                </thead>
                <tbody className="divide-y divide-[var(--portal-border)] bg-[var(--portal-surface)] text-[var(--portal-surface-foreground)]">
                    {rows.map((row, index) => {
                        // The podium (top 3) reads stronger than the rest of
                        // the ranking — only meaningful on the official
                        // (emphasized) standings table, not the reference-only
                        // school list.
                        const isPodium = emphasized && index < 3;

                        return (
                            <tr key={row.label} className={isPodium ? 'font-bold text-[var(--portal-fg)]' : undefined}>
                                <td className={cn(cellPadding, 'tabular-nums')}>{index + 1}</td>
                                <td className={cellPadding}>
                                    {row.slug ? (
                                        <Link
                                            href={teamShow(row.slug).url}
                                            className="flex items-center gap-2 hover:underline sm:gap-3"
                                        >
                                            {showCrest && (
                                                <MunicipalityCrest
                                                    name={row.label}
                                                    logoUrl={row.teamLogoUrl ?? row.logoUrl}
                                                    size="sm"
                                                    shape={emphasized ? 'square' : 'circle'}
                                                    className={emphasized ? 'size-8 sm:size-12 md:size-14 lg:size-16' : undefined}
                                                />
                                            )}
                                            {row.label}
                                        </Link>
                                    ) : (
                                        <span className="flex items-center gap-2 sm:gap-3">
                                            {showCrest && (
                                                <MunicipalityCrest
                                                    name={row.label}
                                                    logoUrl={row.teamLogoUrl ?? row.logoUrl}
                                                    size="sm"
                                                    shape={emphasized ? 'square' : 'circle'}
                                                    className={emphasized ? 'size-8 sm:size-12 md:size-14 lg:size-16' : undefined}
                                                />
                                            )}
                                            {row.label}
                                        </span>
                                    )}
                                </td>
                                {showMedals && (
                                    <>
                                        <td className={cn(cellPadding, 'text-center tabular-nums')}>{row.gold}</td>
                                        <td className={cn(cellPadding, 'text-center tabular-nums')}>{row.silver}</td>
                                        <td className={cn(cellPadding, 'text-center tabular-nums')}>{row.bronze}</td>
                                        <td className={cn(cellPadding, 'text-center tabular-nums')}>{row.total}</td>
                                    </>
                                )}
                                {showPoints && <td className={cn(cellPadding, 'text-center font-semibold tabular-nums')}>{row.points}</td>}
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}
