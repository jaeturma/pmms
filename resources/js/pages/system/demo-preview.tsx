import { Head, Link } from '@inertiajs/react';
import { ExternalLink, FlaskConical } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

export default function DemoPreview({ scenario }: { scenario: any }) {
    return <><Head title={`DEMO — ${scenario.name}`} /><div className="space-y-6 p-4">
        <div className="rounded-lg border-2 border-amber-500 bg-amber-50 p-4 text-amber-950"><div className="flex items-center gap-2 font-bold"><FlaskConical /> DEMONSTRATION DATA</div><p>These schedules, scores, and results are for system demonstration only.</p></div>
        <div><Badge variant="destructive">DEMO</Badge><h1 className="mt-2 text-2xl font-bold">{scenario.name}</h1><p>{scenario.sport} · {scenario.meet}</p></div>
        {scenario.events.map((event: any) => <div key={event.id} className="rounded border p-4"><Badge>DEMO</Badge> {event.name}</div>)}
        {scenario.schedules.map((slot: any) => <div key={slot.id} className="rounded border p-4"><strong>DEMO Schedule</strong><p>{slot.date} · {slot.time} · {slot.venue}</p></div>)}
        {scenario.matches.map((match: any) => <div key={match.id} className="flex items-center justify-between rounded border p-4"><div><Badge variant="destructive">DEMO</Badge><strong className="ml-2">{match.label}</strong><p>Status: {match.status}</p></div><Button asChild><Link href={match.scoreboard_url}>Open real scoreboard <ExternalLink /></Link></Button></div>)}
        {scenario.results.map((result: any) => <div key={result.id} className="rounded border p-4"><strong>DEMO Result #{result.id}</strong><p>Status: {result.status}</p></div>)}
        <Button variant="outline" asChild><Link href="/system/demo-data">Back to Demo Data</Link></Button>
    </div></>;
}
