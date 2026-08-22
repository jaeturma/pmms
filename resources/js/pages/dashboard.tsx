import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    Award,
    CalendarDays,
    Contact,
    Crown,
    FileCheck,
    Gavel,
    IdCard,
    ListChecks,
    Medal,
    Megaphone,
    School,
    TriangleAlert,
    UserCog,
    Users,
    UsersRound,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { PublicAnnouncements } from '@/components/public-announcements';
import { RankBadge } from '@/components/rank-badge';
import type { StatCardTone } from '@/components/stat-card';
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
import { useClock } from '@/hooks/use-clock';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as athletesIndex } from '@/routes/athletes';
import { index as delegationsIndex } from '@/routes/delegations';
import { index as eventsIndex } from '@/routes/events';
import { index as incidentsIndex } from '@/routes/incidents';
import { index as personnelIndex } from '@/routes/personnel';
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

type Announcement = {
    id: number;
    title: string;
    body: string;
    meet: string | null;
    published_at: string | null;
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
    district: string;
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

type EventsOverview = {
    completed: number;
    ongoing: number;
    upcoming: number;
    total: number;
};

type Operations = {
    meet: { id: number; name: string };
    todaySlots: TodaySlot[];
    tallyTop: TallyRow[];
    eventsOverview: EventsOverview;
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
    coachAccreditation: CoachAccreditation | null;
    stats: Stat[];
    recentActivity: ActivityEntry[];
    announcements: Announcement[];
};

type CoachAccreditation = {
    status: string;
    accredited: boolean;
    number: string | null;
    accredited_on: string | null;
    team: string | null;
    school: string | null;
    sport: string | null;
    event: string | null;
    review_notes: string | null;
};

const statIcons: Record<string, LucideIcon> = {
    schools: School,
    delegations: UsersRound,
    athletes: Contact,
    entries: ListChecks,
    users: Users,
    activity_today: Activity,
};

const statTones: Record<string, StatCardTone> = {
    schools: 'primary',
    delegations: 'success',
    athletes: 'gold',
    entries: 'warning',
    users: 'primary',
    activity_today: 'success',
};

/**
 * "HH:MM" strings compare lexicographically the same as chronologically
 * — no need to parse into a real time value just to order/compare them.
 */
function slotStatus(
    slot: TodaySlot,
    nowLabel: string,
): { label: string; variant: 'default' | 'secondary' | 'outline' } {
    if (nowLabel < slot.starts_at) {
        return { label: 'Upcoming', variant: 'outline' };
    }

    if (nowLabel > slot.ends_at) {
        return { label: 'Completed', variant: 'secondary' };
    }

    return { label: 'Ongoing', variant: 'default' };
}

const quickActions: Array<{
    label: string;
    href: string;
    icon: LucideIcon;
}> = [
    {
        label: 'Register delegation',
        href: delegationsIndex().url,
        icon: UsersRound,
    },
    { label: 'Register athlete', href: athletesIndex().url, icon: Contact },
    { label: 'Add official', href: personnelIndex().url, icon: UserCog },
    { label: 'Manage events', href: eventsIndex().url, icon: Medal },
    { label: 'Encode results', href: resultsIndex().url, icon: Award },
    { label: 'Medal tally', href: tallyIndex().url, icon: Crown },
];

function QuickActions() {
    return (
        <section className="flex flex-col gap-3">
            <h2 className="text-base font-semibold">Quick actions</h2>
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-6">
                {quickActions.map((action) => (
                    <Button
                        key={action.label}
                        variant="outline"
                        className="h-auto flex-col gap-2 py-5"
                        asChild
                    >
                        <Link href={action.href}>
                            <action.icon
                                aria-hidden="true"
                                className="size-5 text-primary"
                            />
                            <span className="text-xs font-medium text-wrap">
                                {action.label}
                            </span>
                        </Link>
                    </Button>
                ))}
            </div>
        </section>
    );
}

function EventsOverviewCard({ overview }: { overview: EventsOverview }) {
    const segments: Array<{
        key: string;
        label: string;
        value: number;
        barClass: string;
        dotClass: string;
    }> = [
        {
            key: 'completed',
            label: 'Completed',
            value: overview.completed,
            barClass: 'bg-success',
            dotClass: 'bg-success',
        },
        {
            key: 'ongoing',
            label: 'Ongoing',
            value: overview.ongoing,
            barClass: 'bg-primary',
            dotClass: 'bg-primary',
        },
        {
            key: 'upcoming',
            label: 'Upcoming',
            value: overview.upcoming,
            barClass: 'bg-warning',
            dotClass: 'bg-warning',
        },
    ];

    return (
        <Card data-tone="violet">
            <CardHeader>
                <CardTitle className="text-sm font-medium">
                    Events overview
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-5">
                {overview.total === 0 ? (
                    <EmptyState
                        icon={CalendarDays}
                        title="No events attached to this meet yet"
                    />
                ) : (
                    <>
                        <div className="flex h-2.5 w-full overflow-hidden rounded-full bg-muted">
                            {segments.map((segment) => (
                                <div
                                    key={segment.key}
                                    className={segment.barClass}
                                    style={{
                                        width: `${(segment.value / overview.total) * 100}%`,
                                    }}
                                />
                            ))}
                        </div>
                        <dl className="grid grid-cols-3 gap-3 text-sm">
                            {segments.map((segment) => (
                                <div key={segment.key}>
                                    <dt className="flex items-center gap-1.5 text-muted-foreground">
                                        <span
                                            className={cn(
                                                'size-2 rounded-full',
                                                segment.dotClass,
                                            )}
                                            aria-hidden="true"
                                        />
                                        {segment.label}
                                    </dt>
                                    <dd className="text-lg font-semibold tracking-tight">
                                        {segment.value}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </>
                )}
            </CardContent>
        </Card>
    );
}

const activityMeta: Record<string, { icon: LucideIcon; toneClass: string }> = {
    athlete: { icon: Contact, toneClass: 'bg-primary/10 text-primary' },
    delegation: { icon: UsersRound, toneClass: 'bg-primary/10 text-primary' },
    personnel: { icon: UserCog, toneClass: 'bg-primary/10 text-primary' },
    school: { icon: School, toneClass: 'bg-primary/10 text-primary' },
    district: { icon: UsersRound, toneClass: 'bg-primary/10 text-primary' },
    division: { icon: UsersRound, toneClass: 'bg-primary/10 text-primary' },
    result: { icon: Award, toneClass: 'bg-success/15 text-success' },
    eligibility: { icon: FileCheck, toneClass: 'bg-success/15 text-success' },
    accreditation: { icon: IdCard, toneClass: 'bg-success/15 text-success' },
    protest: { icon: Gavel, toneClass: 'bg-destructive/10 text-destructive' },
    incident: {
        icon: TriangleAlert,
        toneClass: 'bg-destructive/10 text-destructive',
    },
    schedule: { icon: CalendarDays, toneClass: 'bg-primary/10 text-primary' },
    match: { icon: CalendarDays, toneClass: 'bg-primary/10 text-primary' },
    meet: { icon: CalendarDays, toneClass: 'bg-primary/10 text-primary' },
    venue: { icon: CalendarDays, toneClass: 'bg-primary/10 text-primary' },
    event: { icon: CalendarDays, toneClass: 'bg-primary/10 text-primary' },
    sport: { icon: CalendarDays, toneClass: 'bg-primary/10 text-primary' },
    scoring: { icon: Activity, toneClass: 'bg-warning/15 text-warning' },
    report: { icon: FileCheck, toneClass: 'bg-primary/10 text-primary' },
    announcement: { icon: Megaphone, toneClass: 'bg-primary/10 text-primary' },
    entry: { icon: ListChecks, toneClass: 'bg-primary/10 text-primary' },
};

/** "athlete.created" -> "Athlete created" — a general transform, not a
 * per-action lookup table, so newly added audit-log actions never need
 * this file updated to read correctly. */
function formatActionLabel(action: string): string {
    const words = action.replace(/[._]/g, ' ');

    return words.charAt(0).toUpperCase() + words.slice(1);
}

function ActivityIcon({ action }: { action: string }) {
    const prefix = action.split('.')[0];
    const meta = activityMeta[prefix];
    const Icon = meta?.icon ?? Activity;

    return (
        <span
            className={cn(
                'flex size-8 shrink-0 items-center justify-center rounded-full',
                meta?.toneClass ?? 'bg-muted text-muted-foreground',
            )}
        >
            <Icon aria-hidden="true" className="size-4" />
        </span>
    );
}

function MeetOperations({ operations }: { operations: Operations }) {
    const { division } = usePage().props;
    const areaLabel = division.areaLabel;
    const { meet, todaySlots, tallyTop, eventsOverview, queues, myProtests } =
        operations;
    const now = useClock();
    const nowLabel = now.toLocaleTimeString(undefined, {
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
    });

    return (
        <section className="flex flex-col gap-4 sm:gap-6">
            <h2 className="text-base font-semibold">
                Meet operations — {meet.name}
            </h2>

            {queues && (
                <div className="grid gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-4">
                    <Link href={resultsIndex()} className="block">
                        <StatCard
                            label="Results requiring TM confirmation"
                            value={queues.pending_results}
                            icon={Award}
                            tone="warning"
                        />
                    </Link>
                    <Link href={protestsIndex()} className="block">
                        <StatCard
                            label="Open protests"
                            value={queues.open_protests}
                            icon={Gavel}
                            tone="destructive"
                        />
                    </Link>
                    <Link href={incidentsIndex()} className="block">
                        <StatCard
                            label="Open incidents"
                            value={queues.open_incidents}
                            icon={TriangleAlert}
                            tone="destructive"
                        />
                    </Link>
                    <StatCard
                        label="Accreditation progress"
                        value={`${queues.accredited} / ${queues.accreditable}`}
                        icon={IdCard}
                        description="Accredited participants"
                        tone="success"
                    />
                </div>
            )}

            <div className="grid gap-4 md:grid-cols-2 md:gap-6">
                <Card data-tone="cyan">
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
                                        {todaySlots.map((slot) => {
                                            const status = slotStatus(
                                                slot,
                                                nowLabel,
                                            );

                                            return (
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
                                                    <TableCell className="text-right">
                                                        <Badge
                                                            variant={
                                                                status.variant
                                                            }
                                                        >
                                                            {status.label}
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card data-tone="gold">
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
                                            <TableHead>{areaLabel}</TableHead>
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
                                            <TableRow key={row.district}>
                                                <TableCell>
                                                    <RankBadge
                                                        position={row.position}
                                                    />
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

            <EventsOverviewCard overview={eventsOverview} />

            {myProtests && (
                <Card data-tone="destructive">
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
    coachAccreditation,
    stats,
    recentActivity,
    announcements,
}: Props) {
    return (
        <>
            <Head title="Dashboard" />
            <div
                data-dashboard="true"
                className="flex h-full flex-1 flex-col gap-6 p-4"
            >
                <PageHeader
                    title="Dashboard"
                    description="Overview of activity across the system."
                />

                {currentMeet && (
                    <Card data-tone="primary">
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

                {coachAccreditation && (
                    <Card data-tone="success">
                        <CardHeader className="flex flex-row items-center justify-between gap-3">
                            <div className="flex items-center gap-3">
                                <span className="flex size-9 items-center justify-center rounded-[5px] bg-primary/10 text-primary">
                                    <IdCard className="size-5" aria-hidden="true" />
                                </span>
                                <div>
                                    <CardTitle>Accreditation status</CardTitle>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Your coach credential for {currentMeet?.name}.
                                    </p>
                                </div>
                            </div>
                            <Badge variant={coachAccreditation.accredited ? 'default' : 'secondary'}>
                                {coachAccreditation.status}
                            </Badge>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <dt className="text-muted-foreground">Accreditation no.</dt>
                                    <dd className="font-medium">{coachAccreditation.number ?? 'Not assigned'}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">Municipality / Team</dt>
                                    <dd className="font-medium">{coachAccreditation.team ?? 'Not assigned'}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">School</dt>
                                    <dd className="font-medium">{coachAccreditation.school ?? 'Not assigned'}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">Sport / Event</dt>
                                    <dd className="font-medium">
                                        {[coachAccreditation.sport, coachAccreditation.event].filter(Boolean).join(' — ') || 'Not assigned'}
                                    </dd>
                                </div>
                            </dl>
                            {(coachAccreditation.accredited_on || coachAccreditation.review_notes) && (
                                <p className="mt-4 border-t pt-3 text-sm text-muted-foreground">
                                    {coachAccreditation.accredited_on && `Accredited on ${coachAccreditation.accredited_on}.`}
                                    {coachAccreditation.review_notes && ` ${coachAccreditation.review_notes}`}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                )}

                <QuickActions />

                {operations && <MeetOperations operations={operations} />}

                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    {stats.map((stat) => (
                        <StatCard
                            key={stat.key}
                            label={stat.label}
                            value={stat.value}
                            icon={statIcons[stat.key]}
                            tone={statTones[stat.key]}
                        />
                    ))}
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <section className="flex flex-col gap-3">
                        <h2 className="text-base font-semibold">
                            Recent Activity
                        </h2>
                        {recentActivity.length === 0 ? (
                            <EmptyState
                                icon={Activity}
                                title="No activity yet"
                                description="Actions performed in the system will appear here."
                            />
                        ) : (
                            <ul className="flex flex-col gap-1 rounded-xl border p-2">
                                {recentActivity.map((entry) => (
                                    <li
                                        key={entry.id}
                                        className="flex items-center gap-3 rounded-lg px-2 py-2"
                                    >
                                        <ActivityIcon action={entry.action} />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium">
                                                {formatActionLabel(
                                                    entry.action,
                                                )}
                                            </p>
                                            <p className="truncate text-xs text-muted-foreground">
                                                {entry.user ?? 'System'}
                                            </p>
                                        </div>
                                        <span className="shrink-0 text-xs text-muted-foreground">
                                            {entry.created_at_human}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <PublicAnnouncements announcements={announcements} />
                </div>
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
