import { Head, router } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, Printer, RefreshCw, XCircle } from 'lucide-react';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Status = 'ready' | 'needs_attention' | 'not_ready';
type Props = {
    meet: { name: string; starts_at: string; ends_at: string; days_until_start: number };
    calculated_at: string; overall: number; overall_status: Status; print: boolean; scope_label: string;
    summary: Record<string, number>;
    domains: Array<{ label: string; total: number; ready: number; applicable: boolean; score: number }>;
    sports: Array<{ id: number; sport: string; events: number; venues: number; entries: number; coaches: number; athletes: number; schedules: number; status: Status; issues: number }>;
    events: Array<{ id: number; sport: string; event: string; category: string; venue: boolean; entries: number; delegations_with_entry: number; delegations_total: number; missing_delegations: string[]; coaches: number; athletes: number; eligible: number; medical_cleared: number; schedule: boolean; medal: boolean; personnel_count: number; technical_officials: number; status: Status; reasons: string[] }>;
    issues: Array<{ severity: string; sport: string; event: string; message: string }>;
    options: { sports: Array<{ id: number; name: string }>; events: Array<{ id: number; name: string }> };
    filters: { sport_id?: string; event_id?: string; status?: string };
};

const labels: Record<Status, string> = { ready: 'READY', needs_attention: 'NEEDS ATTENTION', not_ready: 'NOT READY' };
const icons = { ready: CheckCircle2, needs_attention: AlertTriangle, not_ready: XCircle };
const summaryLabels: Record<string, string> = { sports_total: 'Total Sports', sports_ready: 'Sports Ready', events_total: 'Total Events', events_ready: 'Events Ready', venues_assigned: 'Venues Assigned', events_with_entries: 'Events With Entries', coaches_registered: 'Registered Coaches', coaches_accredited: 'Accredited Coaches', athletes_total: 'Registered Athletes', athletes_eligible: 'Eligible Athletes', medical_cleared: 'Medical Cleared', schedules_ready: 'Schedules Ready', open_issues: 'Open Issues' };

function StatusBadge({ status }: { status: Status }) {
    const Icon = icons[status];
    return <Badge variant={status === 'not_ready' ? 'destructive' : status === 'ready' ? 'secondary' : 'outline'}><Icon className="mr-1 size-3" />{labels[status]}</Badge>;
}

export default function Readiness(props: Props) {
    const update = (key: string, value: string) => router.get('/monitoring/readiness', { ...props.filters, [key]: value === 'all' ? undefined : value }, { preserveState: true, replace: true });
    return <div className={props.print ? 'p-6 print:p-0' : 'flex h-full flex-1 flex-col gap-6 p-4'}>
        <Head title={`${props.meet.name} Readiness`} />
        <PageHeader title={`${props.meet.name.toUpperCase()} READINESS`} description={`${props.scope_label} · Calculated ${props.calculated_at} · ${props.meet.starts_at} to ${props.meet.ends_at} · ${props.meet.days_until_start >= 0 ? `${props.meet.days_until_start} days until opening` : 'Meet already started'}`} actions={!props.print && <><Button variant="outline" onClick={() => router.get('/monitoring/readiness', { ...props.filters, refresh: 1 })}><RefreshCw />Refresh</Button><Button variant="outline" onClick={() => window.print()}><Printer />Print report</Button></>} />
        <Card className="border-2"><CardContent className="grid gap-5 p-6 md:grid-cols-[15rem_1fr] md:items-center"><div><div className="text-sm text-muted-foreground">OVERALL READINESS</div><div className="text-6xl font-black">{props.overall}%</div><StatusBadge status={props.overall_status} /></div><div><div className="h-6 overflow-hidden rounded-full bg-muted"><div className="h-full bg-primary" style={{ width: `${props.overall}%` }} /></div><p className="mt-3 text-lg font-semibold">{props.overall_status === 'ready' ? 'Ready for meet' : `${props.summary.open_issues} item(s) require management attention`}</p></div></CardContent></Card>
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">{Object.entries(props.summary).map(([key, value]) => <Card key={key}><CardContent className="p-4"><div className="text-xs text-muted-foreground">{summaryLabels[key] ?? key}</div><div className="text-2xl font-bold">{value}</div></CardContent></Card>)}</div>
        <Card><CardHeader><CardTitle>Transparent domain scores</CardTitle></CardHeader><CardContent className="grid gap-3 md:grid-cols-3">{props.domains.map(d => <div key={d.label} className="rounded-lg border p-3"><div className="flex justify-between font-medium"><span>{d.label}</span><span>{d.applicable ? `${d.score}%` : 'N/A'}</span></div><div className="text-xs text-muted-foreground">{d.ready} of {d.total} ready</div></div>)}</CardContent></Card>
        {!props.print && <div className="flex flex-wrap gap-2"><Select value={String(props.filters.sport_id ?? 'all')} onValueChange={v => update('sport_id', v)}><SelectTrigger className="w-56"><SelectValue placeholder="All sports" /></SelectTrigger><SelectContent><SelectItem value="all">All sports</SelectItem>{props.options.sports.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}</SelectContent></Select><Select value={String(props.filters.event_id ?? 'all')} onValueChange={v => update('event_id', v)}><SelectTrigger className="w-72"><SelectValue placeholder="All events" /></SelectTrigger><SelectContent><SelectItem value="all">All events</SelectItem>{props.options.events.map(e => <SelectItem key={e.id} value={String(e.id)}>{e.name}</SelectItem>)}</SelectContent></Select><Select value={props.filters.status ?? 'all'} onValueChange={v => update('status', v)}><SelectTrigger className="w-52"><SelectValue placeholder="All statuses" /></SelectTrigger><SelectContent><SelectItem value="all">All statuses</SelectItem><SelectItem value="ready">Ready</SelectItem><SelectItem value="needs_attention">Needs attention</SelectItem><SelectItem value="not_ready">Not ready</SelectItem></SelectContent></Select></div>}
        <Card><CardHeader><CardTitle>Sport readiness</CardTitle></CardHeader><CardContent className="overflow-x-auto"><Table><TableHeader><TableRow><TableHead>Sport</TableHead><TableHead>Events</TableHead><TableHead>Venue</TableHead><TableHead>Entries</TableHead><TableHead>Coaches</TableHead><TableHead>Athletes</TableHead><TableHead>Schedule</TableHead><TableHead>Status</TableHead></TableRow></TableHeader><TableBody>{props.sports.map(s => <TableRow key={s.id}><TableCell className="font-medium">{s.sport}</TableCell><TableCell>{s.events}</TableCell><TableCell>{s.venues}/{s.events}</TableCell><TableCell>{s.entries}/{s.events}</TableCell><TableCell>{s.coaches}</TableCell><TableCell>{s.athletes}</TableCell><TableCell>{s.schedules}/{s.events}</TableCell><TableCell><StatusBadge status={s.status} /></TableCell></TableRow>)}</TableBody></Table></CardContent></Card>
        <Card><CardHeader><CardTitle>Event readiness and delegation coverage</CardTitle></CardHeader><CardContent className="overflow-x-auto"><Table><TableHeader><TableRow><TableHead>Event</TableHead><TableHead>Venue</TableHead><TableHead>Entry coverage</TableHead><TableHead>Athletes</TableHead><TableHead>Personnel</TableHead><TableHead>Schedule</TableHead><TableHead>Status / reasons</TableHead></TableRow></TableHeader><TableBody>{props.events.map(e => <TableRow key={e.id} className="align-top"><TableCell><b>{e.sport} — {e.event}</b><div className="text-xs text-muted-foreground">{e.category}</div></TableCell><TableCell>{e.venue ? '✓' : 'NO VENUE'}</TableCell><TableCell>{e.delegations_with_entry} of {e.delegations_total} delegations<div className="text-xs text-muted-foreground" title={e.missing_delegations.join(', ')}>{e.entries} entries</div></TableCell><TableCell>{e.eligible}/{e.athletes} eligible<div className="text-xs">{e.medical_cleared}/{e.athletes} medical</div></TableCell><TableCell>{e.personnel_count} assigned<div className="text-xs">{e.technical_officials} officials</div></TableCell><TableCell>{e.schedule ? '✓' : 'NO SCHEDULE'}</TableCell><TableCell><StatusBadge status={e.status} />{e.reasons.map(r => <div key={r} className="mt-1 max-w-sm text-xs">{r}</div>)}</TableCell></TableRow>)}</TableBody></Table></CardContent></Card>
        <Card><CardHeader><CardTitle>Readiness issues / action center</CardTitle></CardHeader><CardContent className="space-y-2">{props.issues.length === 0 ? <p>No open readiness issues.</p> : props.issues.map((i, n) => <div key={`${i.event}-${i.message}-${n}`} className="rounded-lg border p-3"><Badge variant={i.severity === 'critical' ? 'destructive' : 'outline'}>{i.severity.toUpperCase()}</Badge><b className="ml-2">{i.sport} — {i.event}</b><div className="mt-1 text-sm">{i.message}</div></div>)}</CardContent></Card>
    </div>;
}

Readiness.layout = { breadcrumbs: [{ title: 'Monitoring', href: '/management' }, { title: 'Readiness', href: '/monitoring/readiness' }] };
