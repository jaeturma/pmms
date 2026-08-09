import { Head, usePage } from '@inertiajs/react';
import { LayoutDashboard } from 'lucide-react';
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
import { index as managementIndex } from '@/routes/management';
import { download } from '@/routes/reports/management';

type MeetRow = {
    id: number;
    name: string;
    school_year: string;
    starts_at: string;
    ends_at: string;
    status_label: string;
};

type ParticipationRow = {
    meet_id: number;
    meet: string;
    school_year: string;
    delegations: {
        draft: number;
        submitted: number;
        approved: number;
        total: number;
    };
    athletes: number;
    personnel: number;
    entries: number;
};

type OperationsRow = {
    meet_id: number;
    meet: string;
    results: { encoded: number; validated: number };
    eligibility: { pending: number; approved: number; returned: number };
    protests: {
        filed: number;
        under_review: number;
        upheld: number;
        dismissed: number;
    };
    incidents: { open: number; resolved: number };
    is_stalled: boolean;
};

type DistrictStandingRow = {
    position: number;
    district: string;
    gold: number;
    silver: number;
    bronze: number;
    total: number;
};

type SchoolStandingRow = {
    position: number;
    school: string;
    district: string;
    gold: number;
    silver: number;
    bronze: number;
    total: number;
};

type VenueRow = {
    venue_id: number;
    venue: string;
    slots: number;
    hours: number;
    meets: number;
    events: number;
};

type Props = {
    meets: MeetRow[];
    participation: { rows: ParticipationRow[] };
    operations: OperationsRow[];
    performance: {
        districts: DistrictStandingRow[];
        schools: SchoolStandingRow[];
    };
    venues: VenueRow[];
    generatedAt: string;
};

export default function ManagementReport({
    meets,
    participation,
    operations,
    performance,
    venues,
    generatedAt,
}: Props) {
    const { division } = usePage().props;
    const areaLabel = division.areaLabel;

    return (
        <>
            <Head title="Management dashboard report" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Management dashboard report"
                    actions={
                        <ReportActions downloadUrl={download().url} />
                    }
                />

                <p className="text-sm text-muted-foreground">
                    Generated {generatedAt}
                </p>

                {meets.length === 0 ? (
                    <EmptyState
                        icon={LayoutDashboard}
                        title="No meets in scope"
                        description="Nothing to report for this school year."
                    />
                ) : (
                    <>
                        <section className="space-y-3">
                            <h2 className="text-base font-medium">
                                Meets in scope
                            </h2>
                            <div className="overflow-x-auto rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Meet</TableHead>
                                            <TableHead>School year</TableHead>
                                            <TableHead>Dates</TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {meets.map((meet) => (
                                            <TableRow key={meet.id}>
                                                <TableCell className="font-medium">
                                                    {meet.name}
                                                </TableCell>
                                                <TableCell>
                                                    {meet.school_year}
                                                </TableCell>
                                                <TableCell>
                                                    {meet.starts_at} –{' '}
                                                    {meet.ends_at}
                                                </TableCell>
                                                <TableCell>
                                                    {meet.status_label}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </section>

                        <section className="space-y-3">
                            <h2 className="text-base font-medium">
                                Delegations by status
                            </h2>
                            <div className="overflow-x-auto rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Meet</TableHead>
                                            <TableHead className="text-center">
                                                Draft
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Submitted
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Approved
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Total
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {participation.rows.map((row) => (
                                            <TableRow key={row.meet_id}>
                                                <TableCell className="font-medium">
                                                    {row.meet}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.delegations.draft}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.delegations.submitted}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.delegations.approved}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.delegations.total}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </section>

                        <section className="space-y-3">
                            <h2 className="text-base font-medium">
                                Participation
                            </h2>
                            <div className="overflow-x-auto rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Meet</TableHead>
                                            <TableHead className="text-center">
                                                Athletes
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Personnel
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Entries
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {participation.rows.map((row) => (
                                            <TableRow key={row.meet_id}>
                                                <TableCell className="font-medium">
                                                    {row.meet}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.athletes}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.personnel}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.entries}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </section>

                        <section className="space-y-3">
                            <h2 className="text-base font-medium">
                                Operations progress &amp; risk
                            </h2>
                            <div className="overflow-x-auto rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Meet</TableHead>
                                            <TableHead className="text-center">
                                                Encoded
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Validated
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Eligibility pending
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Protests open
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Incidents open
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Stalled
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {operations.map((row) => (
                                            <TableRow key={row.meet_id}>
                                                <TableCell className="font-medium">
                                                    {row.meet}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.results.encoded}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.results.validated}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.eligibility.pending}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.protests.filed +
                                                        row.protests
                                                            .under_review}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.incidents.open}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.is_stalled
                                                        ? 'Yes'
                                                        : 'No'}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </section>

                        <section className="space-y-3">
                            <h2 className="text-base font-medium">
                                {areaLabel} performance history
                            </h2>
                            <div className="overflow-x-auto rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-12">
                                                #
                                            </TableHead>
                                            <TableHead>{areaLabel}</TableHead>
                                            <TableHead className="text-center">
                                                Gold
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Silver
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Bronze
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Total
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {performance.districts.map((row) => (
                                            <TableRow key={row.district}>
                                                <TableCell>
                                                    {row.position}
                                                </TableCell>
                                                <TableCell className="font-medium">
                                                    {row.district}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.gold}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.silver}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.bronze}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.total}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </section>

                        <section className="space-y-3">
                            <h2 className="text-base font-medium">
                                School performance history (reference)
                            </h2>
                            <div className="overflow-x-auto rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-12">
                                                #
                                            </TableHead>
                                            <TableHead>School</TableHead>
                                            <TableHead>{areaLabel}</TableHead>
                                            <TableHead className="text-center">
                                                Gold
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Silver
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Bronze
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Total
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {performance.schools.map((row) => (
                                            <TableRow
                                                key={`${row.school}-${row.district}`}
                                            >
                                                <TableCell>
                                                    {row.position}
                                                </TableCell>
                                                <TableCell className="font-medium">
                                                    {row.school}
                                                </TableCell>
                                                <TableCell>
                                                    {row.district}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.gold}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.silver}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.bronze}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.total}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </section>

                        <section className="space-y-3">
                            <h2 className="text-base font-medium">
                                Venue utilization
                            </h2>
                            <div className="overflow-x-auto rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Venue</TableHead>
                                            <TableHead className="text-center">
                                                Slots
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Hours
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Meets
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Events
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {venues.map((venue) => (
                                            <TableRow key={venue.venue_id}>
                                                <TableCell className="font-medium">
                                                    {venue.venue}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {venue.slots}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {venue.hours}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {venue.meets}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {venue.events}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </section>
                    </>
                )}
            </div>
        </>
    );
}

ManagementReport.layout = {
    breadcrumbs: [
        {
            title: 'Management',
            href: managementIndex(),
        },
        {
            title: 'Report',
            href: managementIndex(),
        },
    ],
};
