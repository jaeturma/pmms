import { Head } from '@inertiajs/react';
import { ListChecks } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
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
import { index as entriesIndex } from '@/routes/entries';
import { download } from '@/routes/reports/event-entries';

type EntryRow = {
    id: number;
    last_name: string;
    first_name: string;
    sex_label: string;
    age: number;
    grade_level: number;
    school: string;
    status_label: string;
};

type Props = {
    event: { id: number; label: string };
    entries: EntryRow[];
    generatedAt: string;
};

export default function EventEntries({ event, entries, generatedAt }: Props) {
    return (
        <>
            <Head title={`Entry list — ${event.label}`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Event entry list"
                    description={event.label}
                    actions={
                        <ReportActions downloadUrl={download(event.id).url} />
                    }
                />

                <p className="text-sm text-muted-foreground">
                    {entries.length} entries (withdrawn excluded) · Generated{' '}
                    {generatedAt}
                </p>

                {entries.length === 0 ? (
                    <EmptyState
                        icon={ListChecks}
                        title="No entries for this event"
                        description="Submitted and confirmed entries will appear here."
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>#</TableHead>
                                    <TableHead>Last name</TableHead>
                                    <TableHead>First name</TableHead>
                                    <TableHead>Sex</TableHead>
                                    <TableHead>Age</TableHead>
                                    <TableHead>Grade</TableHead>
                                    <TableHead>School</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {entries.map((entry, i) => (
                                    <TableRow key={entry.id}>
                                        <TableCell>{i + 1}</TableCell>
                                        <TableCell className="font-medium">
                                            {entry.last_name}
                                        </TableCell>
                                        <TableCell>
                                            {entry.first_name}
                                        </TableCell>
                                        <TableCell>{entry.sex_label}</TableCell>
                                        <TableCell>{entry.age}</TableCell>
                                        <TableCell>
                                            {entry.grade_level}
                                        </TableCell>
                                        <TableCell>{entry.school}</TableCell>
                                        <TableCell>
                                            {entry.status_label}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>
        </>
    );
}

EventEntries.layout = {
    breadcrumbs: [
        {
            title: 'Entries',
            href: entriesIndex(),
        },
    ],
};
