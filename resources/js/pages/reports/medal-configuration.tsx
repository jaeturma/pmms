import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type EventRow = { sport: string; event: string; entry_type: string; gold_physical: number | null; silver_physical: number | null; bronze_physical: number | null; gold_tally: number | null; silver_tally: number | null; bronze_tally: number | null; status: string };
type AwardRow = { sport: string; event: string; physical_pieces: number; official_tally: number };

export default function MedalConfigurationReport({ events, officialAwards, generatedAt }: { events: EventRow[]; officialAwards: AwardRow[]; generatedAt: string }) {
    return <div className="space-y-6 p-4">
        <Head title="Medal Configuration Review" />
        <PageHeader title="Medal Configuration Review" description={`Physical Medal Pieces and Official Medal Tally · Generated ${generatedAt}`} actions={<Button asChild><a href="/reports/medal-configuration/download">Export CSV</a></Button>} />
        <div className="overflow-x-auto rounded-xl border"><Table><TableHeader><TableRow><TableHead>Sport / Event</TableHead><TableHead>Entry type</TableHead><TableHead>Physical G / S / B</TableHead><TableHead>Official tally G / S / B</TableHead><TableHead>Status</TableHead></TableRow></TableHeader><TableBody>{events.map((row) => <TableRow key={`${row.sport}-${row.event}`}><TableCell><strong>{row.sport}</strong><br />{row.event}</TableCell><TableCell>{row.entry_type}</TableCell><TableCell>{row.gold_physical ?? '—'} / {row.silver_physical ?? '—'} / {row.bronze_physical ?? '—'}</TableCell><TableCell>{row.gold_tally ?? '—'} / {row.silver_tally ?? '—'} / {row.bronze_tally ?? '—'}</TableCell><TableCell><Badge variant={row.status === 'MISSING_CONFIGURATION' ? 'destructive' : 'outline'}>{row.status.replaceAll('_', ' ')}</Badge></TableCell></TableRow>)}</TableBody></Table></div>
        <section className="space-y-2"><h2 className="text-lg font-semibold">Official result snapshots</h2><div className="overflow-x-auto rounded-xl border"><Table><TableHeader><TableRow><TableHead>Sport / Event</TableHead><TableHead>Physical Medal Pieces</TableHead><TableHead>Official Medal Tally</TableHead></TableRow></TableHeader><TableBody>{officialAwards.map((row) => <TableRow key={`${row.sport}-${row.event}`}><TableCell>{row.sport} — {row.event}</TableCell><TableCell>{row.physical_pieces}</TableCell><TableCell>{row.official_tally}</TableCell></TableRow>)}</TableBody></Table></div></section>
    </div>;
}
