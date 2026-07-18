import { Head, router } from '@inertiajs/react';
import { School } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { ReportActions } from '@/components/report-actions';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { participation } from '@/routes/reports';
import { download } from '@/routes/reports/participation';
import { index as schoolsIndex } from '@/routes/schools';

type Row = {
    id: number;
    school: string;
    district: string;
    delegations_count: number;
    athletes_count: number;
    personnel_count: number;
    entries_count: number;
};

type Props = {
    rows: Row[];
    filters: { meet_id: number | null };
    meetOptions: { id: number; name: string }[];
    generatedAt: string;
};

export default function SchoolParticipation({
    rows,
    filters,
    meetOptions,
    generatedAt,
}: Props) {
    const meetParams = filters.meet_id
        ? { meet_id: String(filters.meet_id) }
        : {};

    const applyMeet = (value: string) => {
        router.get(
            participation().url,
            value === 'all' ? {} : { meet_id: value },
            { preserveState: true },
        );
    };

    const totals = rows.reduce(
        (acc, row) => ({
            delegations: acc.delegations + row.delegations_count,
            athletes: acc.athletes + row.athletes_count,
            personnel: acc.personnel + row.personnel_count,
            entries: acc.entries + row.entries_count,
        }),
        { delegations: 0, athletes: 0, personnel: 0, entries: 0 },
    );

    return (
        <>
            <Head title="School participation" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="School participation summary"
                    description="Delegations, athletes, personnel, and entries per school."
                    actions={
                        <ReportActions
                            downloadUrl={download({ query: meetParams }).url}
                        />
                    }
                />

                <div className="flex flex-wrap items-center gap-3 print:hidden">
                    <Select
                        value={String(filters.meet_id ?? 'all')}
                        onValueChange={applyMeet}
                    >
                        <SelectTrigger
                            className="w-72"
                            aria-label="Filter by meet"
                        >
                            <SelectValue placeholder="All meets" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All meets</SelectItem>
                            {meetOptions.map((meet) => (
                                <SelectItem
                                    key={meet.id}
                                    value={String(meet.id)}
                                >
                                    {meet.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <p className="text-sm text-muted-foreground">
                    Generated {generatedAt}
                </p>

                {rows.length === 0 ? (
                    <EmptyState
                        icon={School}
                        title="No participation yet"
                        description="Schools appear here once they have a registered delegation."
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>School</TableHead>
                                    <TableHead>District</TableHead>
                                    <TableHead>Delegations</TableHead>
                                    <TableHead>Athletes</TableHead>
                                    <TableHead>Personnel</TableHead>
                                    <TableHead>Entries</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rows.map((row) => (
                                    <TableRow key={row.id}>
                                        <TableCell className="font-medium">
                                            {row.school}
                                        </TableCell>
                                        <TableCell>{row.district}</TableCell>
                                        <TableCell>
                                            {row.delegations_count}
                                        </TableCell>
                                        <TableCell>
                                            {row.athletes_count}
                                        </TableCell>
                                        <TableCell>
                                            {row.personnel_count}
                                        </TableCell>
                                        <TableCell>
                                            {row.entries_count}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                <TableRow>
                                    <TableCell
                                        className="font-semibold"
                                        colSpan={2}
                                    >
                                        Total ({rows.length} schools)
                                    </TableCell>
                                    <TableCell className="font-semibold">
                                        {totals.delegations}
                                    </TableCell>
                                    <TableCell className="font-semibold">
                                        {totals.athletes}
                                    </TableCell>
                                    <TableCell className="font-semibold">
                                        {totals.personnel}
                                    </TableCell>
                                    <TableCell className="font-semibold">
                                        {totals.entries}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>
        </>
    );
}

SchoolParticipation.layout = {
    breadcrumbs: [
        {
            title: 'Schools',
            href: schoolsIndex(),
        },
        {
            title: 'Participation',
            href: participation(),
        },
    ],
};
