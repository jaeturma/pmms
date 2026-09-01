import { Head, router } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    Printer,
    RefreshCw,
    XCircle,
} from 'lucide-react';
import { PageHeader } from '@/components/page-header';
import { SearchBar } from '@/components/search-bar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

type Status = 'ready' | 'needs_attention' | 'not_ready';
type Props = {
    meet: {
        name: string;
        starts_at: string;
        ends_at: string;
        days_until_start: number;
    };
    calculated_at: string;
    overall: number;
    overall_status: Status;
    print: boolean;
    scope_label: string;
    summary: Record<string, number>;
    domains: Array<{
        label: string;
        total: number;
        ready: number;
        applicable: boolean;
        score: number;
    }>;
    sports: Array<{
        id: number;
        sport: string;
        events: number;
        ready_events: number;
        attention_events: number;
        not_ready_events: number;
        venues: number;
        entries: number;
        coaches: number;
        athletes: number;
        schedules: number;
        status: Status;
        issues: number;
    }>;
    teams: Array<{ id: number; team: string; athletes: number }>;
    events: Page<{
        id: number;
        sport: string;
        event: string;
        event_classification: string;
        venue: boolean;
        entries: number;
        delegations_with_entry: number;
        delegations_total: number;
        missing_delegations: string[];
        coaches: number;
        registered_athletes: number;
        eligible_roster_athletes: number;
        pending_eligibility: number;
        athletes_with_entries: number;
        athletes: number;
        eligible: number;
        medical_cleared: number;
        schedule: boolean;
        medal: boolean;
        personnel_count: number;
        technical_officials: number;
        status: Status;
        reasons: string[];
    }>;
    issues: Page<{
        severity: string;
        sport: string;
        event: string;
        message: string;
    }>;
    options: {
        sports: Array<{ id: number; name: string }>;
        events: Array<{ id: number; name: string }>;
    };
    filters: {
        sport_id?: string;
        event_id?: string;
        status?: string;
        search?: string;
        events_page?: string;
        issues_page?: string;
    };
};
type Page<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

const labels: Record<Status, string> = {
    ready: 'READY',
    needs_attention: 'NEEDS ATTENTION',
    not_ready: 'NOT READY',
};
const icons = {
    ready: CheckCircle2,
    needs_attention: AlertTriangle,
    not_ready: XCircle,
};
const summaryLabels: Record<string, string> = {
    sports_total: 'Total Sports',
    sports_ready: 'Sports Ready',
    events_total: 'Total Sports Events',
    events_ready: 'Events Ready',
    venues_assigned: 'Venues Assigned',
    events_with_entries: 'Events With Entries',
    coaches_registered: 'Registered Coaches',
    coaches_accredited: 'Accredited Coaches',
    athletes_total: 'Sport Roster Athletes',
    athletes_eligible: 'Eligible Athletes',
    athletes_with_entries: 'Athletes With Entries',
    athletes_pending_eligibility: 'Pending Eligibility',
    medical_cleared: 'Medical Cleared',
    schedules_ready: 'Schedules Ready',
    open_issues: 'Open Issues',
};

function StatusBadge({ status }: { status: Status }) {
    const Icon = icons[status];
    return (
        <Badge
            variant={
                status === 'not_ready'
                    ? 'destructive'
                    : status === 'ready'
                      ? 'secondary'
                      : 'outline'
            }
        >
            <Icon className="mr-1 size-3" />
            {labels[status]}
        </Badge>
    );
}

export default function Readiness(props: Props) {
    const update = (key: string, value: string) =>
        router.get(
            '/monitoring/readiness',
            {
                ...props.filters,
                [key]: value === 'all' ? undefined : value,
                ...(key === 'sport_id' || key === 'search'
                    ? { events_page: undefined, issues_page: undefined }
                    : {}),
            },
            { preserveState: true, replace: true },
        );
    return (
        <div
            className={
                props.print
                    ? 'p-6 print:p-0'
                    : 'flex h-full flex-1 flex-col gap-6 p-4'
            }
        >
            <Head title={`${props.meet.name} Readiness`} />
            <PageHeader
                title={`${props.meet.name.toUpperCase()} READINESS`}
                description={`${props.scope_label} · Calculated ${props.calculated_at} · ${props.meet.starts_at} to ${props.meet.ends_at} · ${props.meet.days_until_start >= 0 ? `${props.meet.days_until_start} days until opening` : 'Meet already started'}`}
                actions={
                    !props.print && (
                        <>
                            <Button
                                variant="outline"
                                onClick={() =>
                                    router.get('/monitoring/readiness', {
                                        ...props.filters,
                                        refresh: 1,
                                    })
                                }
                            >
                                <RefreshCw />
                                Refresh
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => window.print()}
                            >
                                <Printer />
                                Print report
                            </Button>
                        </>
                    )
                }
            />
            <Card className="border-2">
                <CardContent className="grid gap-5 p-6 md:grid-cols-[15rem_1fr] md:items-center">
                    <div>
                        <div className="text-sm text-muted-foreground">
                            OVERALL READINESS
                        </div>
                        <div className="text-6xl font-black">
                            {props.overall}%
                        </div>
                        <StatusBadge status={props.overall_status} />
                    </div>
                    <div>
                        <div className="h-6 overflow-hidden rounded-full bg-muted">
                            <div
                                className="h-full bg-primary"
                                style={{ width: `${props.overall}%` }}
                            />
                        </div>
                        <p className="mt-3 text-lg font-semibold">
                            {props.overall_status === 'ready'
                                ? 'Ready for meet'
                                : `${props.summary.open_issues} item(s) require management attention`}
                        </p>
                    </div>
                </CardContent>
            </Card>
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
                {Object.entries(props.summary).map(([key, value]) => (
                    <Card key={key}>
                        <CardContent className="p-4">
                            <div className="text-xs text-muted-foreground">
                                {summaryLabels[key] ?? key}
                            </div>
                            <div className="text-2xl font-bold">{value}</div>
                        </CardContent>
                    </Card>
                ))}
            </div>
            <Card>
                <CardHeader>
                    <CardTitle>Transparent domain scores</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-3 md:grid-cols-3">
                    {props.domains.map((d) => (
                        <div key={d.label} className="rounded-lg border p-3">
                            <div className="flex justify-between font-medium">
                                <span>{d.label}</span>
                                <span>
                                    {d.applicable ? `${d.score}%` : 'N/A'}
                                </span>
                            </div>
                            <div className="text-xs text-muted-foreground">
                                {d.ready} of {d.total} ready
                            </div>
                        </div>
                    ))}
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>Athletes per municipality / team</CardTitle>
                </CardHeader>
                <CardContent className="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Municipality / Team</TableHead>
                                <TableHead className="text-right">Athletes</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {props.teams.map((team) => (
                                <TableRow key={team.id}>
                                    <TableCell className="font-medium">{team.team}</TableCell>
                                    <TableCell className="text-right font-bold">{team.athletes}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
            {!props.print && (
                <div className="flex flex-wrap gap-2">
                    <SearchBar
                        initial={props.filters.search ?? ''}
                        placeholder="Search sport, Sports Event, or issue"
                        url="/monitoring/readiness"
                        extraParams={{
                            ...(props.filters.sport_id
                                ? { sport_id: props.filters.sport_id }
                                : {}),
                            ...(props.filters.event_id
                                ? { event_id: props.filters.event_id }
                                : {}),
                            ...(props.filters.status
                                ? { status: props.filters.status }
                                : {}),
                        }}
                    />
                    <Select
                        value={String(props.filters.sport_id ?? 'all')}
                        onValueChange={(v) => update('sport_id', v)}
                    >
                        <SelectTrigger className="w-56">
                            <SelectValue placeholder="All sports" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All sports</SelectItem>
                            {props.options.sports.map((s) => (
                                <SelectItem key={s.id} value={String(s.id)}>
                                    {s.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={String(props.filters.event_id ?? 'all')}
                        onValueChange={(v) => update('event_id', v)}
                    >
                        <SelectTrigger className="w-72">
                            <SelectValue placeholder="All Sports Events" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                All Sports Events
                            </SelectItem>
                            {props.options.events.map((e) => (
                                <SelectItem key={e.id} value={String(e.id)}>
                                    {e.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={props.filters.status ?? 'all'}
                        onValueChange={(v) => update('status', v)}
                    >
                        <SelectTrigger className="w-52">
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            <SelectItem value="ready">Ready</SelectItem>
                            <SelectItem value="needs_attention">
                                Needs attention
                            </SelectItem>
                            <SelectItem value="not_ready">Not ready</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            )}
            <Card>
                <CardHeader>
                    <CardTitle>Sport readiness</CardTitle>
                </CardHeader>
                <CardContent className="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Sport</TableHead>
                                <TableHead>Events</TableHead>
                                <TableHead>Venue</TableHead>
                                <TableHead>Entries</TableHead>
                                <TableHead>Coaches</TableHead>
                                <TableHead>Athletes</TableHead>
                                <TableHead>Schedule</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {props.sports.map((s) => (
                                <TableRow key={s.id}>
                                    <TableCell className="font-medium">
                                        {s.sport}
                                    </TableCell>
                                    <TableCell>{s.events}</TableCell>
                                    <TableCell>
                                        {s.venues}/{s.events}
                                    </TableCell>
                                    <TableCell>
                                        {s.entries}/{s.events}
                                    </TableCell>
                                    <TableCell>{s.coaches}</TableCell>
                                    <TableCell>{s.athletes}</TableCell>
                                    <TableCell>
                                        {s.schedules}/{s.events}
                                    </TableCell>
                                    <TableCell>
                                        <StatusBadge status={s.status} />
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>
                        Sports Event readiness and delegation coverage (
                        {props.events.total})
                    </CardTitle>
                </CardHeader>
                <CardContent className="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Sports Event</TableHead>
                                <TableHead>Venue</TableHead>
                                <TableHead>Entry coverage</TableHead>
                                <TableHead>Athletes</TableHead>
                                <TableHead>Personnel</TableHead>
                                <TableHead>Schedule</TableHead>
                                <TableHead>Status / reasons</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {props.events.data.map((e) => (
                                <TableRow key={e.id} className="align-top">
                                    <TableCell>
                                        <b>
                                            {e.sport} — {e.event}
                                        </b>
                                        <div className="text-xs text-muted-foreground">
                                            {e.event_classification}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {e.venue ? '✓' : 'NO VENUE'}
                                    </TableCell>
                                    <TableCell>
                                        {e.delegations_with_entry} of{' '}
                                        {e.delegations_total} delegations
                                        <div
                                            className="text-xs text-muted-foreground"
                                            title={e.missing_delegations.join(
                                                ', ',
                                            )}
                                        >
                                            {e.entries} entries
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {e.registered_athletes} rostered
                                        <div className="text-xs">
                                            {e.eligible_roster_athletes}{' '}
                                            eligible · {e.athletes_with_entries}{' '}
                                            entered
                                        </div>
                                        {e.pending_eligibility > 0 && (
                                            <div className="text-xs text-amber-700">
                                                {e.pending_eligibility} pending
                                                eligibility
                                            </div>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {e.personnel_count} assigned
                                        <div className="text-xs">
                                            {e.technical_officials} officials
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {e.schedule ? '✓' : 'NO SCHEDULE'}
                                    </TableCell>
                                    <TableCell>
                                        <StatusBadge status={e.status} />
                                        {e.reasons.map((r) => (
                                            <div
                                                key={r}
                                                className="mt-1 max-w-sm text-xs"
                                            >
                                                {r}
                                            </div>
                                        ))}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                    <Pager
                        page={props.events}
                        param="events_page"
                        filters={props.filters}
                    />
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>
                        Readiness issues / action center ({props.issues.total})
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-2">
                    {props.issues.data.length === 0 ? (
                        <p>No open readiness issues.</p>
                    ) : (
                        props.issues.data.map((i, n) => (
                            <div
                                key={`${i.event}-${i.message}-${n}`}
                                className="rounded-lg border p-3"
                            >
                                <Badge
                                    variant={
                                        i.severity === 'critical'
                                            ? 'destructive'
                                            : 'outline'
                                    }
                                >
                                    {i.severity.toUpperCase()}
                                </Badge>
                                <b className="ml-2">
                                    {i.sport} — {i.event}
                                </b>
                                <div className="mt-1 text-sm">{i.message}</div>
                            </div>
                        ))
                    )}
                    <Pager
                        page={props.issues}
                        param="issues_page"
                        filters={props.filters}
                    />
                </CardContent>
            </Card>
        </div>
    );
}

function Pager({
    page,
    param,
    filters,
}: {
    page: Page<unknown>;
    param: string;
    filters: Props['filters'];
}) {
    if (page.last_page <= 1) return null;
    const go = (value: number) =>
        router.get(
            '/monitoring/readiness',
            { ...filters, [param]: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    return (
        <div className="mt-4 flex items-center justify-between border-t pt-3 text-sm">
            <span>
                Page {page.current_page} of {page.last_page}
            </span>
            <div className="flex gap-2">
                <Button
                    size="sm"
                    variant="outline"
                    disabled={page.current_page === 1}
                    onClick={() => go(page.current_page - 1)}
                >
                    Previous
                </Button>
                <Button
                    size="sm"
                    variant="outline"
                    disabled={page.current_page === page.last_page}
                    onClick={() => go(page.current_page + 1)}
                >
                    Next
                </Button>
            </div>
        </div>
    );
}

Readiness.layout = {
    breadcrumbs: [
        { title: 'Monitoring', href: '/management' },
        { title: 'Readiness', href: '/monitoring/readiness' },
    ],
};
