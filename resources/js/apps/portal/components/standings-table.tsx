import { Trophy } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { MunicipalityCrest } from '@/apps/portal/components/municipality-crest';
import { cn } from '@/apps/portal/lib/utils';

type PortalStandingsRow = {
    label: string;
    logoUrl?: string | null;
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
};

export function PortalStandingsTable({
    rows,
    nameLabel = 'Name',
    unavailableTitle = 'Standings not available',
    unavailableDescription = 'No standings data exists for this sport yet.',
    emphasized = false,
}: PortalStandingsTableProps) {
    if (rows === null || rows.length === 0) {
        return <PortalEmptyState icon={Trophy} tone="ink" title={unavailableTitle} description={unavailableDescription} />;
    }

    const showMedals = rows[0].gold !== undefined;
    const showPoints = rows[0].points !== undefined;
    const cellPadding = emphasized ? 'px-6 py-4' : 'px-4 py-2';

    return (
        <div className="overflow-x-auto rounded-[var(--portal-radius)] border border-[var(--portal-border)]">
            <table className={cn('w-full', emphasized ? 'text-[28px]' : 'text-sm')}>
                <thead className="bg-[var(--portal-muted)] text-[var(--portal-muted-foreground)]">
                    <tr>
                        <th className={cn(cellPadding, 'text-left font-medium')}>#</th>
                        <th className={cn(cellPadding, 'text-left font-medium')}>{nameLabel}</th>
                        {showMedals && (
                            <>
                                <th className={cn(cellPadding, 'text-right font-medium')}>Gold</th>
                                <th className={cn(cellPadding, 'text-right font-medium')}>Silver</th>
                                <th className={cn(cellPadding, 'text-right font-medium')}>Bronze</th>
                                <th className={cn(cellPadding, 'text-right font-medium')}>Total</th>
                            </>
                        )}
                        {showPoints && <th className={cn(cellPadding, 'text-right font-medium')}>Points</th>}
                    </tr>
                </thead>
                <tbody className="divide-y divide-[var(--portal-border)] bg-[var(--portal-surface)] text-[var(--portal-surface-foreground)]">
                    {rows.map((row, index) => (
                        <tr key={row.label}>
                            <td className={cn(cellPadding, 'tabular-nums')}>{index + 1}</td>
                            <td className={cellPadding}>
                                <span className="flex items-center gap-3">
                                    <MunicipalityCrest
                                        name={row.label}
                                        logoUrl={row.logoUrl}
                                        size="sm"
                                        shape={emphasized ? 'square' : 'circle'}
                                        className={emphasized ? 'size-16' : undefined}
                                    />
                                    {row.label}
                                </span>
                            </td>
                            {showMedals && (
                                <>
                                    <td className={cn(cellPadding, 'text-right tabular-nums')}>{row.gold}</td>
                                    <td className={cn(cellPadding, 'text-right tabular-nums')}>{row.silver}</td>
                                    <td className={cn(cellPadding, 'text-right tabular-nums')}>{row.bronze}</td>
                                    <td className={cn(cellPadding, 'text-right tabular-nums')}>{row.total}</td>
                                </>
                            )}
                            {showPoints && <td className={cn(cellPadding, 'text-right font-semibold tabular-nums')}>{row.points}</td>}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
