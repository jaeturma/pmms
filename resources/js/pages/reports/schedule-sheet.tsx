import { Head, router } from '@inertiajs/react';
import { CalendarDays } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { PageHeader } from '@/components/page-header';
import { ReportActions } from '@/components/report-actions';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { schedule } from '@/routes/reports';
import { download } from '@/routes/reports/schedule';
import { index as scheduleIndex } from '@/routes/schedule';

type Slot = {
    id: number;
    starts_at: string;
    ends_at: string;
    event: string;
    meet: string;
    note: string | null;
};

type VenueGroup = {
    venue: string;
    slots: Slot[];
};

type Props = {
    date: string;
    venues: VenueGroup[];
    generatedAt: string;
};

export default function ScheduleSheet({ date, venues, generatedAt }: Props) {
    return (
        <>
            <Head title={`Schedule — ${date}`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Daily schedule"
                    description={`All venues on ${date}`}
                    actions={
                        <ReportActions
                            downloadUrl={download({ query: { date } }).url}
                        />
                    }
                />

                <div className="flex flex-wrap items-center gap-3 print:hidden">
                    <Input
                        type="date"
                        className="w-44"
                        aria-label="Sheet date"
                        value={date}
                        onChange={(e) =>
                            router.get(
                                schedule().url,
                                { date: e.target.value },
                                { preserveState: true },
                            )
                        }
                    />
                    <p className="text-sm text-muted-foreground">
                        Generated {generatedAt}
                    </p>
                </div>

                {venues.length === 0 ? (
                    <EmptyState
                        icon={CalendarDays}
                        title="Nothing scheduled"
                        description="No slots are scheduled on this day."
                    />
                ) : (
                    venues.map((group) => (
                        <section key={group.venue} className="space-y-3">
                            <Heading variant="small" title={group.venue} />
                            <div className="overflow-x-auto rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-32">
                                                Time
                                            </TableHead>
                                            <TableHead>Event</TableHead>
                                            <TableHead>Meet</TableHead>
                                            <TableHead>Note</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {group.slots.map((slot) => (
                                            <TableRow key={slot.id}>
                                                <TableCell className="font-medium whitespace-nowrap">
                                                    {slot.starts_at}–
                                                    {slot.ends_at}
                                                </TableCell>
                                                <TableCell>
                                                    {slot.event}
                                                </TableCell>
                                                <TableCell>
                                                    {slot.meet}
                                                </TableCell>
                                                <TableCell>
                                                    {slot.note ?? '—'}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </section>
                    ))
                )}
            </div>
        </>
    );
}

ScheduleSheet.layout = {
    breadcrumbs: [
        {
            title: 'Schedule',
            href: scheduleIndex(),
        },
    ],
};
