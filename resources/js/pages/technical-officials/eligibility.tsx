import { Head, router } from '@inertiajs/react';
import { Printer } from 'lucide-react';
import { useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
export default function OfficialEligibility({
    official,
    sports,
    selectedSport,
    evaluation,
    meet,
}: any) {
    const [sportId, setSportId] = useState(
        selectedSport?.id ? String(selectedSport.id) : '',
    );

    return (
        <>
            <Head title="Technical Official Eligibility" />
            <PageHeader
                title="Technical Official Eligibility"
                description={`Review registration, assignment, accreditation and medical clearance for ${official.name}.`}
            />
            <Card className="mb-6">
                <CardContent className="flex gap-3 pt-6">
                    <select
                        className="h-10 flex-1 rounded-md border px-3"
                        value={sportId}
                        onChange={(e) => setSportId(e.target.value)}
                    >
                        <option value="">Select sport</option>
                        {sports.map((s: any) => (
                            <option key={s.id} value={s.id}>
                                {s.name}
                            </option>
                        ))}
                    </select>
                    <Button
                        onClick={() =>
                            router.get(
                                `/technical-officials/${official.id}/eligibility`,
                                { sport_id: sportId, meet_id: meet.id },
                            )
                        }
                    >
                        Check Eligibility
                    </Button>
                </CardContent>
            </Card>
            {evaluation && (
                <Card>
                    <CardHeader>
                        <CardTitle>
                            {official.name} —{' '}
                            {evaluation.result.replace('_', ' ').toUpperCase()}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {evaluation.rules.map((r: any) => (
                            <div key={r.rule} className="rounded-lg border p-3">
                                <b>
                                    {r.status === 'passed'
                                        ? '✓'
                                        : r.status === 'failed'
                                          ? '✕'
                                          : '!'}{' '}
                                    {r.rule}
                                </b>
                                <p className="text-sm text-muted-foreground">
                                    Expected: {r.expected} · Actual: {r.actual}
                                </p>
                            </div>
                        ))}
                        <Button
                            variant="outline"
                            onClick={() => window.print()}
                        >
                            <Printer className="mr-2 size-4" />
                            Print / PDF
                        </Button>
                    </CardContent>
                </Card>
            )}
        </>
    );
}
