import { Trophy } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';

type PortalStandingsRow = {
    label: string;
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
};

export function PortalStandingsTable({
    rows,
    nameLabel = 'Name',
    unavailableTitle = 'Standings not available',
    unavailableDescription = 'No standings data exists for this sport yet.',
}: PortalStandingsTableProps) {
    if (rows === null || rows.length === 0) {
        return <PortalEmptyState icon={Trophy} tone="ink" title={unavailableTitle} description={unavailableDescription} />;
    }

    const showMedals = rows[0].gold !== undefined;
    const showPoints = rows[0].points !== undefined;

    return (
        <div className="overflow-x-auto rounded-[var(--portal-radius)] border border-[var(--portal-border)]">
            <table className="w-full text-sm">
                <thead className="bg-[var(--portal-muted)] text-[var(--portal-muted-foreground)]">
                    <tr>
                        <th className="px-4 py-2 text-left font-medium">#</th>
                        <th className="px-4 py-2 text-left font-medium">{nameLabel}</th>
                        {showMedals && (
                            <>
                                <th className="px-4 py-2 text-right font-medium">Gold</th>
                                <th className="px-4 py-2 text-right font-medium">Silver</th>
                                <th className="px-4 py-2 text-right font-medium">Bronze</th>
                                <th className="px-4 py-2 text-right font-medium">Total</th>
                            </>
                        )}
                        {showPoints && <th className="px-4 py-2 text-right font-medium">Points</th>}
                    </tr>
                </thead>
                <tbody className="divide-y divide-[var(--portal-border)] bg-[var(--portal-surface)] text-[var(--portal-surface-foreground)]">
                    {rows.map((row, index) => (
                        <tr key={row.label}>
                            <td className="px-4 py-2 tabular-nums">{index + 1}</td>
                            <td className="px-4 py-2">{row.label}</td>
                            {showMedals && (
                                <>
                                    <td className="px-4 py-2 text-right tabular-nums">{row.gold}</td>
                                    <td className="px-4 py-2 text-right tabular-nums">{row.silver}</td>
                                    <td className="px-4 py-2 text-right tabular-nums">{row.bronze}</td>
                                    <td className="px-4 py-2 text-right tabular-nums">{row.total}</td>
                                </>
                            )}
                            {showPoints && <td className="px-4 py-2 text-right font-semibold tabular-nums">{row.points}</td>}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
