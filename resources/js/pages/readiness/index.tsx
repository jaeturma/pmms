import { Head } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import { PageHeader } from '@/components/page-header';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function Readiness({
    meet,
    summary,
    districts,
    needsAttention,
}: any) {
    return (
        <>
            <Head title="Athlete Readiness" />
            <PageHeader
                title="Athlete Readiness"
                description={`${meet.name} — monitoring only; DSAC and Medical Team decisions remain independent.`}
            />
            <div className="grid gap-4 md:grid-cols-3 lg:grid-cols-6">
                {Object.entries(summary).map(([key, value]) => (
                    <Card key={key}>
                        <CardHeader>
                            <CardTitle className="text-sm capitalize">
                                {key.replaceAll('_', ' ')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-bold">
                            {String(value)}
                        </CardContent>
                    </Card>
                ))}
            </div>
            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>Readiness by School District</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    {districts.map((d: any) => (
                        <div
                            key={d.name}
                            className="flex justify-between rounded-lg border p-3"
                        >
                            <span>{d.name}</span>
                            <span>
                                {d.ready} ready · {d.needs_attention} need
                                attention
                            </span>
                        </div>
                    ))}
                </CardContent>
            </Card>
            <Card className="mt-6">
                <CardHeader>
                    <CardTitle className="flex gap-2">
                        <AlertTriangle className="size-5" />
                        Needs Attention
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-2">
                    {needsAttention.map((a: any) => (
                        <div key={a.id} className="rounded border p-3">
                            <b>{a.name}</b> · {a.school}
                        </div>
                    ))}
                </CardContent>
            </Card>
        </>
    );
}
