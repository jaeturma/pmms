import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { ReportActions } from '@/components/report-actions';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { download } from '@/routes/reports/result-sheet';
import { index as resultsIndex } from '@/routes/results';

type Placement = {
    rank: number;
    athlete: string;
    school: string;
    mark: string | null;
    is_tie: boolean;
};

type Props = {
    result: {
        id: number;
        meet: string;
        school_year: string;
        event: string;
        encoded_by: string | null;
        validated_by: string | null;
        validated_at: string | null;
    };
    placements: Placement[];
    generatedAt: string;
};

export default function ResultSheet({
    result,
    placements,
    generatedAt,
}: Props) {
    return (
        <>
            <Head title={`Result sheet — ${result.event}`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Official result"
                    description={`${result.event} — ${result.meet} (SY ${result.school_year})`}
                    actions={
                        <ReportActions downloadUrl={download(result.id).url} />
                    }
                />

                <p className="text-sm text-muted-foreground">
                    Encoded by {result.encoded_by ?? '—'} · Validated by{' '}
                    {result.validated_by ?? '—'} on {result.validated_at} ·
                    Generated {generatedAt}
                </p>

                <div className="overflow-x-auto rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-16">Rank</TableHead>
                                <TableHead>Athlete</TableHead>
                                <TableHead>School</TableHead>
                                <TableHead>Score / mark</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {placements.map((placement, i) => (
                                <TableRow key={i}>
                                    <TableCell className="font-medium">
                                        {placement.rank}
                                        {placement.is_tie && ' (tie)'}
                                    </TableCell>
                                    <TableCell>{placement.athlete}</TableCell>
                                    <TableCell>{placement.school}</TableCell>
                                    <TableCell>
                                        {placement.mark ?? '—'}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

ResultSheet.layout = {
    breadcrumbs: [
        {
            title: 'Results',
            href: resultsIndex(),
        },
    ],
};
