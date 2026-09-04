import { Head, Link, router } from '@inertiajs/react';
import { ExternalLink, RefreshCw } from 'lucide-react';
import { useMemo, useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';

type Issue = { type: 'athlete' | 'coach' | 'coach_account' | 'game'; id: number; name: string; code: string; problem: string; recommendation: string; repair: { code: string; label: string } | null; manualUrl: string };

export default function DataRepair({ issues, summary }: { issues: Issue[]; summary: { total: number; athletes: number; coaches: number; games: number; automatic: number } }) {
    const [search, setSearch] = useState('');
    const [type, setType] = useState('all');
    const visible = useMemo(() => issues.filter((issue) => (type === 'all' || (type === 'coach' ? issue.type !== 'athlete' : issue.type === type)) && `${issue.name} ${issue.problem} ${issue.recommendation}`.toLowerCase().includes(search.toLowerCase())), [issues, search, type]);
    const repair = (issue: Issue) => issue.repair && router.post('/data-repair/repair', { type: issue.type, id: issue.id, code: issue.repair.code }, { preserveScroll: true });

    return <AppLayout breadcrumbs={[{ title: 'Data Repair', href: '/data-repair' }]}>
        <Head title="Data Repair" />
        <div className="space-y-6 p-4 md:p-6">
            <PageHeader title="Registration Data Repair" description="Find records that cannot be extracted reliably and repair their source information." />
            <div className="grid gap-3 sm:grid-cols-5">
                {[['Problems', summary.total], ['Athlete issues', summary.athletes], ['Coach issues', summary.coaches], ['Game-data issues', summary.games], ['Safe auto-repairs', summary.automatic]].map(([label, value]) => <div key={label} className="rounded-lg border bg-card p-4"><div className="text-sm text-muted-foreground">{label}</div><div className="text-2xl font-semibold">{value}</div></div>)}
            </div>
            <div className="flex flex-col gap-3 sm:flex-row">
                <Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search person or problem…" className="sm:max-w-sm" />
                <div className="flex gap-2">{[['all', 'All'], ['athlete', 'Athletes'], ['coach', 'Coaches'], ['game', 'Game data']].map(([value, label]) => <Button key={value} type="button" variant={type === value ? 'default' : 'outline'} onClick={() => setType(value)}>{label}</Button>)}</div>
            </div>
            <div className="overflow-hidden rounded-lg border"><Table>
                <TableHeader><TableRow><TableHead>Record</TableHead><TableHead>Detected problem</TableHead><TableHead>Recommended correction</TableHead><TableHead className="text-right">Actions</TableHead></TableRow></TableHeader>
                <TableBody>{visible.map((issue) => <TableRow key={`${issue.type}-${issue.id}-${issue.code}`}>
                    <TableCell><div className="font-medium">{issue.name}</div><Badge variant="outline">{issue.type === 'athlete' ? 'Athlete' : issue.type === 'game' ? 'Game data' : 'Coach'}</Badge></TableCell>
                    <TableCell><div className="max-w-md text-sm">{issue.problem}</div><code className="text-xs text-muted-foreground">{issue.code}</code></TableCell>
                    <TableCell className="max-w-md text-sm">{issue.recommendation}</TableCell>
                    <TableCell><div className="flex justify-end gap-2">{issue.repair && <Button size="sm" onClick={() => repair(issue)}><RefreshCw className="mr-1 size-4" />{issue.repair.label}</Button>}<Button size="sm" variant="outline" asChild><Link href={issue.manualUrl}><ExternalLink className="mr-1 size-4" />Open record</Link></Button></div></TableCell>
                </TableRow>)}{visible.length === 0 && <TableRow><TableCell colSpan={4} className="py-12 text-center text-muted-foreground">No inconsistent records match this filter.</TableCell></TableRow>}</TableBody>
            </Table></div>
        </div>
    </AppLayout>;
}
