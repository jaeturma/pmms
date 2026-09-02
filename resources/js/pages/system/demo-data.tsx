import { Head, Link, useForm } from '@inertiajs/react';
import { FlaskConical, Plus, Trash2 } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Option = { id: number; name: string };
type Scenario = {
    id: number; name: string; template: string; meet: string; sport: string;
    created_by: string | null; created_at: string | null;
    events: number; schedules: number; matches: number; results: number;
};

export default function DemoData({ scenarios, meets, sports, venues }: {
    scenarios: Scenario[]; meets: Option[]; sports: Option[]; venues: Option[];
}) {
    const form = useForm({
        request_token: crypto.randomUUID(), meet_id: '', sport_id: '', venue_id: '',
        name: 'Competition Presentation', event_name: 'Sample Event', template: 'head_to_head',
        gender: 'boys', age_division: 'secondary', scheduled_date: new Date().toISOString().slice(0, 10),
        starts_at: '14:00', ends_at: '16:00', side_a_label: 'Demo Team A', side_b_label: 'Demo Team B',
    });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/system/demo-data', { preserveScroll: true });
    };

    return <>
        <Head title="Demo / Test Competition Data" />
        <div className="space-y-6 p-4">
            <PageHeader title="Demo / Test Competition Data" description="Create isolated scenarios using the real schedule, match, scoreboard, and result architecture." />
            <div className="rounded-lg border-2 border-amber-500 bg-amber-50 p-4 text-amber-950">
                <div className="flex items-center gap-2 font-bold"><FlaskConical /> DEMO DATA</div>
                <p>For system testing and presentation only. Demo records do not form part of official Provincial Meet results.</p>
            </div>
            <form onSubmit={submit} className="grid gap-4 rounded-lg border p-4 md:grid-cols-3">
                <h2 className="font-semibold md:col-span-3">Create Demo Scenario</h2>
                <Field label="Scenario name"><Input value={form.data.name} onChange={e => form.setData('name', e.target.value)} /></Field>
                <SelectField label="Meet" value={form.data.meet_id} onChange={v => form.setData('meet_id', v)} options={meets} />
                <SelectField label="Sport" value={form.data.sport_id} onChange={v => form.setData('sport_id', v)} options={sports} />
                <Field label="Demo event"><Input value={form.data.event_name} onChange={e => form.setData('event_name', e.target.value)} /></Field>
                <SelectField label="Venue" value={form.data.venue_id} onChange={v => form.setData('venue_id', v)} options={venues} />
                <label className="grid gap-2"><Label>Template</Label><select className="h-9 rounded-md border bg-background px-3" value={form.data.template} onChange={e => form.setData('template', e.target.value)}><option value="head_to_head">Head-to-head / scoreboard</option><option value="performance">Ranking / performance</option></select></label>
                <Field label="Date"><Input type="date" value={form.data.scheduled_date} onChange={e => form.setData('scheduled_date', e.target.value)} /></Field>
                <Field label="Starts"><Input type="time" value={form.data.starts_at} onChange={e => form.setData('starts_at', e.target.value)} /></Field>
                <Field label="Ends"><Input type="time" value={form.data.ends_at} onChange={e => form.setData('ends_at', e.target.value)} /></Field>
                <Field label="Participant / Team A"><Input value={form.data.side_a_label} onChange={e => form.setData('side_a_label', e.target.value)} /></Field>
                <Field label="Participant / Team B"><Input value={form.data.side_b_label} onChange={e => form.setData('side_b_label', e.target.value)} /></Field>
                <div className="flex items-end"><Button disabled={form.processing}><Plus /> Generate scenario</Button></div>
                {Object.keys(form.errors).length > 0 && <div className="md:col-span-3"><InputError message={Object.values(form.errors)[0]} /></div>}
            </form>
            <div className="grid gap-4 lg:grid-cols-2">
                {scenarios.map(scenario => <div key={scenario.id} className="space-y-3 rounded-lg border p-4">
                    <div className="flex items-center justify-between"><h3 className="font-semibold">{scenario.name}</h3><Badge variant="destructive">DEMO</Badge></div>
                    <p>{scenario.sport} · {scenario.meet}</p>
                    <div className="grid grid-cols-4 gap-2 text-center text-sm"><Stat label="Events" value={scenario.events}/><Stat label="Schedules" value={scenario.schedules}/><Stat label="Matches" value={scenario.matches}/><Stat label="Results" value={scenario.results}/></div>
                    <p className="text-xs text-muted-foreground">Created {scenario.created_at} by {scenario.created_by ?? 'Archived user'}</p>
                    <div className="flex gap-2"><Button asChild><Link href={`/system/demo-data/${scenario.id}`}>View Demo</Link></Button><RemoveScenario id={scenario.id} /></div>
                </div>)}
            </div>
        </div>
    </>;
}

function RemoveScenario({ id }: { id: number }) {
    const form = useForm({ confirmation: '' });
    return <form onSubmit={e => { e.preventDefault(); form.delete(`/system/demo-data/${id}`); }} className="flex gap-2"><Input className="w-44" placeholder="DELETE DEMO DATA" value={form.data.confirmation} onChange={e => form.setData('confirmation', e.target.value)} /><Button variant="destructive" disabled={form.processing || form.data.confirmation !== 'DELETE DEMO DATA'}><Trash2 /> Remove</Button></form>;
}
function Field({ label, children }: { label: string; children: ReactNode }) { return <label className="grid gap-2"><Label>{label}</Label>{children}</label>; }
function SelectField({ label, value, onChange, options }: { label: string; value: string; onChange: (v: string) => void; options: Option[] }) { return <label className="grid gap-2"><Label>{label}</Label><select required className="h-9 rounded-md border bg-background px-3" value={value} onChange={e => onChange(e.target.value)}><option value="">Select…</option>{options.map(o => <option key={o.id} value={o.id}>{o.name}</option>)}</select></label>; }
function Stat({ label, value }: { label: string; value: number }) { return <div className="rounded bg-muted p-2"><strong className="block text-lg">{value}</strong>{label}</div>; }
