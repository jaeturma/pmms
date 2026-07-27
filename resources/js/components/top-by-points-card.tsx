import { Award } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { pluralizeAreaLabel } from '@/lib/utils';

export type PointsRow = { district: string; points: number };

/**
 * Top-5-by-points bar list — a secondary, explicitly-labeled cut of the
 * standings, never the official rank (see the caption on the standings
 * table that reuses this data). Shared by the internal and public tally
 * pages — extracted on its second use.
 */
export function TopByPointsCard({
    rows,
    areaLabel,
}: {
    rows: PointsRow[];
    areaLabel: string;
}) {
    const max = Math.max(1, ...rows.map((row) => row.points));

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-sm font-medium">
                    Top {pluralizeAreaLabel(areaLabel).toLowerCase()} by points
                </CardTitle>
            </CardHeader>
            <CardContent>
                {rows.length === 0 ? (
                    <EmptyState icon={Award} title="No medals yet" />
                ) : (
                    <ol className="space-y-3">
                        {rows.map((row, i) => (
                            <li key={row.district} className="space-y-1">
                                <div className="flex items-center justify-between gap-2 text-sm">
                                    <span className="font-medium">
                                        {i + 1}. {row.district}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {row.points}
                                    </span>
                                </div>
                                <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        className="h-full bg-primary"
                                        style={{
                                            width: `${(row.points / max) * 100}%`,
                                        }}
                                    />
                                </div>
                            </li>
                        ))}
                    </ol>
                )}
            </CardContent>
        </Card>
    );
}
