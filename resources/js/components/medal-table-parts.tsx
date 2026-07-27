import { TableCell, TableHead } from '@/components/ui/table';

export type MedalCounts = {
    gold: number;
    silver: number;
    bronze: number;
    total: number;
};

/**
 * The four gold/silver/bronze/total cells shared by every medal table on
 * both the internal (`tally/index.tsx`) and public (`public/tally.tsx`)
 * pages — extracted on its second use so the two don't drift apart.
 */
export function MedalCells({ row }: { row: MedalCounts }) {
    return (
        <>
            <TableCell className="text-center">{row.gold}</TableCell>
            <TableCell className="text-center">{row.silver}</TableCell>
            <TableCell className="text-center">{row.bronze}</TableCell>
            <TableCell className="text-center font-medium">
                {row.total}
            </TableCell>
        </>
    );
}

export function MedalHeader() {
    return (
        <>
            <TableHead className="w-16 text-center">Gold</TableHead>
            <TableHead className="w-16 text-center">Silver</TableHead>
            <TableHead className="w-16 text-center">Bronze</TableHead>
            <TableHead className="w-16 text-center">Total</TableHead>
        </>
    );
}
