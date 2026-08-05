import { Trophy } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import type { PortalTopMedalistRow } from '@/apps/portal/types';

type PortalTopMedalistTableProps = {
    rows: PortalTopMedalistRow[];
};

export function PortalTopMedalistTable({ rows }: PortalTopMedalistTableProps) {
    if (rows.length === 0) {
        return (
            <PortalEmptyState
                icon={Trophy}
                tone="ink"
                title="No medalists yet"
                description="Individual leaders appear once results are validated."
            />
        );
    }

    return (
        <div className="overflow-x-auto rounded-[var(--portal-radius)] border border-[var(--portal-border)]">
            <table className="w-full text-sm">
                <thead className="bg-[var(--portal-muted)] text-[var(--portal-muted-foreground)]">
                    <tr>
                        <th className="px-4 py-2 text-left font-medium">#</th>
                        <th className="px-4 py-2 text-left font-medium">
                            Athlete
                        </th>
                        <th className="px-4 py-2 text-left font-medium">
                            Grade Level
                        </th>
                        <th className="px-4 py-2 text-left font-medium">
                            Sport
                        </th>
                        <th className="px-4 py-2 text-left font-medium">
                            School
                        </th>
                        <th className="px-4 py-2 text-left font-medium">
                            Municipality
                        </th>
                        <th className="px-4 py-2 text-left font-medium">
                            District
                        </th>
                        <th className="px-4 py-2 text-right font-medium">
                            Gold
                        </th>
                        <th className="px-4 py-2 text-right font-medium">
                            Silver
                        </th>
                        <th className="px-4 py-2 text-right font-medium">
                            Bronze
                        </th>
                        <th className="px-4 py-2 text-right font-medium">
                            Total
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-[var(--portal-border)] bg-[var(--portal-surface)] text-[var(--portal-surface-foreground)]">
                    {rows.map((row) => (
                        <tr key={row.position}>
                            <td className="px-4 py-2 tabular-nums">
                                {row.position}
                            </td>
                            <td className="px-4 py-2 font-medium">
                                {row.athlete}
                            </td>
                            <td className="px-4 py-2 tabular-nums">
                                {row.grade_level}
                            </td>
                            <td className="px-4 py-2">{row.sport}</td>
                            <td className="px-4 py-2">{row.school}</td>
                            <td className="px-4 py-2">{row.municipality}</td>
                            <td className="px-4 py-2">{row.district}</td>
                            <td className="px-4 py-2 text-right tabular-nums">
                                {row.gold}
                            </td>
                            <td className="px-4 py-2 text-right tabular-nums">
                                {row.silver}
                            </td>
                            <td className="px-4 py-2 text-right tabular-nums">
                                {row.bronze}
                            </td>
                            <td className="px-4 py-2 text-right font-semibold tabular-nums">
                                {row.total}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
