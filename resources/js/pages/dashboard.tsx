import { Head, Link } from '@inertiajs/react';
import {
    Activity,
    Award,
    CalendarDays,
    Contact,
    Crown,
    Gavel,
    IdCard,
    ListChecks,
    School,
    TriangleAlert,
    Users,
    UsersRound,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes';
import { index as incidentsIndex } from '@/routes/incidents';
import { index as protestsIndex } from '@/routes/protests';
import { schedule as scheduleSheet } from '@/routes/reports';
import { index as resultsIndex } from '@/routes/results';
import { index as scheduleIndex } from '@/routes/schedule';
import { index as tallyIndex } from '@/routes/tally';

type Stat = {
    key: string;
    label: string;
    value: number;
};

type ActivityEntry = {
    id: number;
    action: string;
    user: string | null;
    created_at_human: string | null;
};

type CurrentMeet = {
    name: string;
    school_year: string;
    status: string;
    status_label: string;
    starts_at: string;
    ends_at: string;
    venue: string | null;
    events_count: number;
};

type TodaySlot = {
    id: number;
    starts_at: string;
    ends_at: string;
    event: string;
    venue: string;
};

type TallyRow = {
    position: number;
    school: string;
    gold: number;
    silver: number;
    bronze: number;
    total: number;
};

type MyProtest = {
    id: number;
    grounds: string;
    status_label: string;
};

type Operations = {
    meet: { id: number; name: string };
    todaySlots: TodaySlot[];
    tallyTop: TallyRow[];
    queues: {
        pending_results: number;
        open_protests: number;
        open_incidents: number;
        accredited: number;
        accreditable: number;
    } | null;
    myProtests: MyProtest[] | null;
};

type Props = {
    currentMeet: CurrentMeet | null;
    operations: Operations | null;
    stats: Stat[];
    recentActivity: ActivityEntry[];
};

const statIcons: Record<string, LucideIcon> = {
    schools: School,
    delegations: UsersRound,
    athletes: Contact,
    entries: ListChecks,
    users: Users,
    activity_today: Activity,
};

function MeetOperations({ operations }: { operations: Operations }) {
    const { meet, todaySlots, tallyTop, queues, myProtests } = operations;

    return (
        <section className="flex flex-col gap-4">
            <h2 className="text-base font-medium">
                Meet operations — {meet.name}
            </h2>

            {queues && (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Link href={resultsIndex()} className="block">
                        <StatCard
                            label="Results awaiting validation"
                            value={queues.pending_results}
                            icon={Award}
                        />
                    </Link>
                    <Link href={protestsIndex()} className="block">
                        <StatCard
                            label="Open protests"
                            value={queues.open_protests}
                            icon={Gavel}
                        />
                    </Link>
                    <Link href={incidentsIndex()} className="block">
                        <StatCard
                            label="Open incidents"
                            value={queues.open_incidents}
                            icon={TriangleAlert}
                        />
                    </Link>
                    <StatCard
                        label="Accreditation progress"
                        value={`${queues.accredited} / ${queues.accreditable}`}
                        icon={IdCard}
                        description="Accredited participants"
                    />
                </div>
            )}

            <div className="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-2">
                        <CardTitle className="text-sm font-medium">
                            Today's schedule
                        </CardTitle>
                        <div className="flex gap-2">
                            <Button variant="outline" size="sm" asChild>
                                <Link href={scheduleIndex()}>Schedule</Link>
                            </Button>
                            <Button variant="outline" size="sm" asChild>
                                <Link href={scheduleSheet()}>Daily sheet</Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {todaySlots.length === 0 ? (
                            <EmptyState
                                icon={CalendarDays}
                                title="Nothing scheduled today"
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableBody>
                                        {todaySlots.map((slot) => (
                                            <TableRow key={slot.id}>
                                                <TableCell className="font-medium whitespace-nowrap">
                                                    {slot.starts_at}–
                                                    {slot.ends_at}
                                                </TableCell>
                                                <TableCell>
                                                    {slot.event}
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {slot.venue}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-2">
                        <CardTitle className="text-sm font-medium">
                            Medal tally — top five
                        </CardTitle>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={tallyIndex()}>Full tally</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {tallyTop.length === 0 ? (
                            <EmptyState icon={Crown} title="No medals yet" />
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-10">
                                                #
                                            </TableHead>
                                            <TableHead>School</TableHead>
                                            <TableHead className="w-10 text-center">
                                                G
                                            </TableHead>
                                            <TableHead className="w-10 text-center">
                                                S
                                            </TableHead>
                                            <TableHead className="w-10 text-center">
                                                B
                                            </TableHead>
                                            <TableHead className="w-12 text-center">
                                                Total
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {tallyTop.map((row) => (
                                            <TableRow key={row.school}>
                                                <TableCell>
                                                    {row.position}
                                                </TableCell>
                                                <TableCell className="font-medium">
                                                    {row.school}
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
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {myProtests && (
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-2">
                        <CardTitle className="text-sm font-medium">
                            Your delegation's protests
                        </CardTitle>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={protestsIndex()}>All protests</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {myProtests.length === 0 ? (
                            <EmptyState
                                icon={Gavel}
                                title="No protests filed"
                            />
                        ) : (
                            <ul className="space-y-2">
                                {myProtests.map((protest) => (
                                    <li
                                        key={protest.id}
                                        className="flex items-center justify-between gap-3 text-sm"
                                    >
                                        <span className="truncate">
                                            #{protest.id} — {protest.grounds}
                                        </span>
                                        <Badge variant="outline">
                                            {protest.status_label}
                                        </Badge>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            )}
        </section>
    );
}

export default function Dashboard({
    currentMeet,
    operations,
    stats,
    recentActivity,
}: Props) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Dashboard"
                    description="Overview of activity across the system."
                />

                {currentMeet && (
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between gap-2">
                            <CardTitle>{currentMeet.name}</CardTitle>
                            <Badge>{currentMeet.status_label}</Badge>
                        </CardHeader>
                        <CardContent className="text-sm text-muted-foreground">
                            <p>
                                SY {currentMeet.school_year} ·{' '}
                                {currentMeet.starts_at} → {currentMeet.ends_at}
                                {currentMeet.venue &&
                                    ` · ${currentMeet.venue}`}{' '}
                                · {currentMeet.events_count} event
                                {currentMeet.events_count === 1 ? '' : 's'}
                            </p>
                        </CardContent>
                    </Card>
                )}

                {operations && <MeetOperations operations={operations} />}

                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    {stats.map((stat) => (
                        <StatCard
                            key={stat.key}
                            label={stat.label}
                            value={stat.value}
                            icon={statIcons[stat.key]}
                        />
                    ))}
                </div>

                <section className="flex flex-col gap-3">
                    <h2 className="text-base font-medium">Recent Activity</h2>
                    {recentActivity.length === 0 ? (
                        <EmptyState
                            icon={Activity}
                            title="No activity yet"
                            description="Actions performed in the system will appear here."
                        />
                    ) : (
                        <div className="rounded-xl border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Action</TableHead>
                                        <TableHead>User</TableHead>
                                        <TableHead>When</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recentActivity.map((entry) => (
                                        <TableRow key={entry.id}>
                                            <TableCell className="font-medium">
                                                {entry.action}
                                            </TableCell>
                                            <TableCell>
                                                {entry.user ?? 'System'}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {entry.created_at_human}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
