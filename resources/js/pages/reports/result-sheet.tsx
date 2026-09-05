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
    tally_quantity: number | null;
    attribution: {
        players: string[];
        coaches: { name: string; role: string }[];
    };
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
        documents: { name: string; url: string }[];
        result_type: string | null;
        measurement_type: string | null;
        status: string;
        submitted_at: string | null;
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
                    {result.result_type === 'versus' && <span>Versus result · {result.measurement_type} · {result.status === 'official' ? 'Accepted' : result.status} · {result.submitted_at} · </span>}
                    Encoded by {result.encoded_by ?? '—'} · Validated by{' '}
                    {result.validated_by ?? '—'} on {result.validated_at} ·
                    Generated {generatedAt}
                </p>

                <div className="overflow-x-auto rounded-xl border">
                    {result.documents.map((document) => (
                        <a
                            key={document.url}
                            href={document.url}
                            className="block p-2 underline"
                        >
                            Result document: {document.name}
                        </a>
                    ))}
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-16">Rank</TableHead>
                                <TableHead>Athlete / Team</TableHead>
                                <TableHead>Delegation / School</TableHead>
                                <TableHead>Score / mark</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {placements.map((placement, i) => (
                                <TableRow key={i}>
                                    <TableCell className="font-medium">
                                        {result.result_type === 'versus' ? (placement.rank === 1 ? 'Winner' : 'Loser') : placement.rank}
                                        {result.result_type !== 'versus' && (placement.tally_quantity ?? 0) > 0 &&
                                            ` (${['Gold', 'Silver', 'Bronze'][placement.rank - 1] ?? ''})`}
                                        {placement.is_tie && ' (tie)'}
                                    </TableCell>
                                    <TableCell>
                                        {placement.athlete}
                                        {placement.attribution.players.length >
                                            0 && (
                                            <p>
                                                Players:{' '}
                                                {placement.attribution.players.join(
                                                    ', ',
                                                )}
                                            </p>
                                        )}
                                        {placement.attribution.coaches.map(
                                            (c, i) => (
                                                <p key={i}>
                                                    {c.role}: {c.name}
                                                </p>
                                            ),
                                        )}
                                        {result.result_type !== 'versus' && <p>
                                            Medal tally count:{' '}
                                            {placement.tally_quantity ?? 1}
                                        </p>}
                                    </TableCell>
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
