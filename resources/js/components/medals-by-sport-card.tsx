import { Medal } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { MedalCells, MedalHeader } from '@/components/medal-table-parts';
import type { MedalCounts } from '@/components/medal-table-parts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

export type SportRow = MedalCounts & { sport: string };

/**
 * Gold/silver/bronze/total per sport. Shared by the internal and public
 * tally pages — extracted on its second use.
 */
export function MedalsBySportCard({ rows }: { rows: SportRow[] }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-sm font-medium">
                    Medals by sport
                </CardTitle>
            </CardHeader>
            <CardContent>
                {rows.length === 0 ? (
                    <EmptyState icon={Medal} title="No medals yet" />
                ) : (
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Sport</TableHead>
                                    <MedalHeader />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rows.map((row) => (
                                    <TableRow key={row.sport}>
                                        <TableCell className="font-medium">
                                            {row.sport}
                                        </TableCell>
                                        <MedalCells row={row} />
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
