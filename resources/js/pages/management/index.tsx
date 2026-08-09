import { Head, Link, usePage } from '@inertiajs/react';
import {
    Contact,
    Crown,
    LayoutDashboard,
    ListChecks,
    MapPin,
    Printer,
    TriangleAlert,
    UserCog,
    UsersRound,
} from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index as districtsIndex } from '@/routes/districts';
import { index as eligibilityIndex } from '@/routes/eligibility';
import { index as incidentsIndex } from '@/routes/incidents';
import { index } from '@/routes/management';
import { index as protestsIndex } from '@/routes/protests';
import { management as managementReport } from '@/routes/reports';
import { index as resultsIndex } from '@/routes/results';
import { index as schoolsIndex } from '@/routes/schools';
import { index as venuesIndex } from '@/routes/venues';

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

type Participation = {
    rows: ParticipationRow[];
    totals: {
        delegations: number;
        athletes: number;
        personnel: number;
        entries: number;
    };
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

type Performance = {
    districts: DistrictStandingRow[];
    schools: SchoolStandingRow[];
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
    participation: Participation;
    operations: OperationsRow[];
    performance: Performance;
    venues: VenueRow[];
    generatedAt: string;
};

export default function ManagementDashboard({
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
            <Head title="Management dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Management dashboard"
                    description="Oversight for administrators and organizers."
                    actions={
                        <Button variant="outline" asChild>
                            <Link href={managementReport().url}>
                                <Printer aria-hidden="true" />
                                Printable report
                            </Link>
                        </Button>
                    }
                />

                <p className="text-sm text-muted-foreground">
                    Generated {generatedAt}.
                </p>

                {meets.length === 0 ? (
                    <EmptyState
                        icon={LayoutDashboard}
                        title="No meets yet"
                        description="Cross-meet trends appear once meets exist."
                    />
                ) : (
                    <>
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

                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <StatCard
                                label="Delegations registered"
                                value={participation.totals.delegations}
                                icon={UsersRound}
                                description="Across meets in scope, any status"
                            />
                            <StatCard
                                label="Athletes"
                                value={participation.totals.athletes}
                                icon={Contact}
                            />
                            <StatCard
                                label="Personnel"
                                value={participation.totals.personnel}
                                icon={UserCog}
                            />
                            <StatCard
                                label="Entries"
                                value={participation.totals.entries}
                                icon={ListChecks}
                            />
                        </div>

                        <section className="space-y-3">
                            <Heading
                                variant="small"
                                title="Delegations by status"
                                description="Registering units (schools or municipalities), per meet."
                            />
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
                                            <TableHead className="text-center font-medium">
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
                                                <TableCell className="text-center font-medium">
                                                    {row.delegations.total}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </section>

                        <section className="space-y-3">
                            <Heading
                                variant="small"
                                title="Participation"
                                description="Individuals (their own home school) and event entries, per meet."
                            />
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
                            <Heading
                                variant="small"
                                title="Operations progress & risk"
                                description="Encoding/validation, eligibility, protests, and incidents, per meet. Stalled = Active meet with an encoded result unvalidated for over 24 hours."
                            />
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
                                                Eligibility returned
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Protests open
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Protests decided
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Incidents open
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Risk
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
                                                    <Link
                                                        className="underline-offset-2 hover:underline"
                                                        aria-label={`${row.results.encoded} encoded results for ${row.meet}`}
                                                        href={
                                                            resultsIndex({
                                                                query: {
                                                                    meet_id:
                                                                        row.meet_id,
                                                                },
                                                            }).url
                                                        }
                                                    >
                                                        {row.results.encoded}
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.results.validated}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Link
                                                        className="underline-offset-2 hover:underline"
                                                        aria-label={`${row.eligibility.pending} pending eligibility reviews`}
                                                        href={
                                                            eligibilityIndex()
                                                                .url
                                                        }
                                                    >
                                                        {
                                                            row.eligibility
                                                                .pending
                                                        }
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.eligibility.returned}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Link
                                                        className="underline-offset-2 hover:underline"
                                                        aria-label={`${row.protests.filed + row.protests.under_review} open protests`}
                                                        href={
                                                            protestsIndex().url
                                                        }
                                                    >
                                                        {row.protests.filed +
                                                            row.protests
                                                                .under_review}
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.protests.upheld +
                                                        row.protests.dismissed}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Link
                                                        className="underline-offset-2 hover:underline"
                                                        aria-label={`${row.incidents.open} open incidents for ${row.meet}`}
                                                        href={
                                                            incidentsIndex({
                                                                query: {
                                                                    meet_id:
                                                                        row.meet_id,
                                                                },
                                                            }).url
                                                        }
                                                    >
                                                        {row.incidents.open}
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.is_stalled ? (
                                                        <Badge variant="destructive">
                                                            <TriangleAlert
                                                                aria-hidden="true"
                                                                className="size-3"
                                                            />
                                                            Stalled
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="outline">
                                                            OK
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </section>

                        {performance.districts.length === 0 ? (
                            <EmptyState
                                icon={Crown}
                                title="No medal history yet"
                                description="Performance history appears once results are validated."
                            />
                        ) : (
                            <>
                                <section className="space-y-3">
                                    <Heading
                                        title={`${areaLabel} performance history`}
                                        description="Medal standings aggregated across the meets in scope — the official verdict."
                                    />
                                    <div className="overflow-x-auto rounded-xl border">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead className="w-12">
                                                        #
                                                    </TableHead>
                                                    <TableHead>
                                                        {areaLabel}
                                                    </TableHead>
                                                    <TableHead className="text-center">
                                                        Gold
                                                    </TableHead>
                                                    <TableHead className="text-center">
                                                        Silver
                                                    </TableHead>
                                                    <TableHead className="text-center">
                                                        Bronze
                                                    </TableHead>
                                                    <TableHead className="text-center font-medium">
                                                        Total
                                                    </TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {performance.districts.map(
                                                    (row) => (
                                                        <TableRow
                                                            key={row.district}
                                                        >
                                                            <TableCell>
                                                                {row.position}
                                                            </TableCell>
                                                            <TableCell className="font-medium">
                                                                <Link
                                                                    className="underline-offset-2 hover:underline"
                                                                    href={
                                                                        districtsIndex(
                                                                            {
                                                                                query: {
                                                                                    search: row.district,
                                                                                },
                                                                            },
                                                                        ).url
                                                                    }
                                                                >
                                                                    {
                                                                        row.district
                                                                    }
                                                                </Link>
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
                                                            <TableCell className="text-center font-medium">
                                                                {row.total}
                                                            </TableCell>
                                                        </TableRow>
                                                    ),
                                                )}
                                            </TableBody>
                                        </Table>
                                    </div>
                                </section>

                                <section className="space-y-3">
                                    <Heading
                                        variant="small"
                                        title="School performance history"
                                        description="Reference only — shows which school each medal came from, across the meets in scope."
                                    />
                                    <div className="overflow-x-auto rounded-xl border">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead className="w-12">
                                                        #
                                                    </TableHead>
                                                    <TableHead>
                                                        School
                                                    </TableHead>
                                                    <TableHead>
                                                        {areaLabel}
                                                    </TableHead>
                                                    <TableHead className="text-center">
                                                        Gold
                                                    </TableHead>
                                                    <TableHead className="text-center">
                                                        Silver
                                                    </TableHead>
                                                    <TableHead className="text-center">
                                                        Bronze
                                                    </TableHead>
                                                    <TableHead className="text-center font-medium">
                                                        Total
                                                    </TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {performance.schools.map(
                                                    (row) => (
                                                        <TableRow
                                                            key={`${row.school}-${row.district}`}
                                                        >
                                                            <TableCell>
                                                                {row.position}
                                                            </TableCell>
                                                            <TableCell className="font-medium">
                                                                <Link
                                                                    className="underline-offset-2 hover:underline"
                                                                    href={
                                                                        schoolsIndex(
                                                                            {
                                                                                query: {
                                                                                    search: row.school,
                                                                                },
                                                                            },
                                                                        ).url
                                                                    }
                                                                >
                                                                    {row.school}
                                                                </Link>
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
                                                            <TableCell className="text-center font-medium">
                                                                {row.total}
                                                            </TableCell>
                                                        </TableRow>
                                                    ),
                                                )}
                                            </TableBody>
                                        </Table>
                                    </div>
                                </section>
                            </>
                        )}

                        <section className="space-y-3">
                            <Heading
                                variant="small"
                                title="Venue utilization"
                                description="Scheduled slots and hours per venue, across the meets in scope."
                            />
                            {venues.length === 0 ? (
                                <EmptyState
                                    icon={MapPin}
                                    title="No scheduled slots yet"
                                    description="Venue utilization appears once slots are scheduled."
                                />
                            ) : (
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
                                                        <Link
                                                            className="underline-offset-2 hover:underline"
                                                            href={
                                                                venuesIndex({
                                                                    query: {
                                                                        search: venue.venue,
                                                                    },
                                                                }).url
                                                            }
                                                        >
                                                            {venue.venue}
                                                        </Link>
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
                            )}
                        </section>
                    </>
                )}
            </div>
        </>
    );
}

ManagementDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Management',
            href: index(),
        },
    ],
};
