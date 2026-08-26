import { Head, router } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, Printer, XCircle } from 'lucide-react';
import { useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Rule = {
    rule: string;
    authority: string;
    description: string;
    expected: string;
    actual: string;
    status: string;
    remarks: string | null;
};
type Props = {
    athletes: Array<{ id: number; label: string }>;
    events: Array<{ id: number; label: string; category: string | null }>;
    selectedAthlete: any;
    evaluation: { result: string; rules: Rule[] } | null;
    meet: { id: number; name: string } | null;
};

export default function AthleteEligibility({
    athletes,
    events,
    selectedAthlete,
    evaluation,
    meet,
}: Props) {
    const [athleteId, setAthleteId] = useState(
        selectedAthlete?.id ? String(selectedAthlete.id) : '',
    );
    const [eventId, setEventId] = useState('');
    const check = () =>
        router.get(
            '/athletes/eligibility',
            { athlete_id: athleteId, event_id: eventId, meet_id: meet?.id },
            { preserveState: true },
        );
    const Icon =
        evaluation?.result === 'eligible'
            ? CheckCircle2
            : evaluation?.result === 'ineligible'
              ? XCircle
              : AlertTriangle;

    return (
        <>
            <Head title="Athlete Eligibility Checker" />
            <PageHeader
                title="Athlete Eligibility Checker"
                description="Verify whether an athlete is eligible to participate in a selected sport event and category."
            />
            <Card className="mb-6">
                <CardContent className="grid gap-3 pt-6 md:grid-cols-[1fr_1fr_auto]">
                    <select
                        className="h-10 rounded-md border px-3"
                        value={athleteId}
                        onChange={(e) => setAthleteId(e.target.value)}
                    >
                        <option value="">Search Athlete</option>
                        {athletes.map((a) => (
                            <option key={a.id} value={a.id}>
                                {a.label}
                            </option>
                        ))}
                    </select>
                    <select
                        className="h-10 rounded-md border px-3"
                        value={eventId}
                        onChange={(e) => setEventId(e.target.value)}
                    >
                        <option value="">Sport / Event / Category</option>
                        {events.map((e) => (
                            <option key={e.id} value={e.id}>
                                {e.label}
                                {e.category ? ` — ${e.category}` : ''}
                            </option>
                        ))}
                    </select>
                    <Button disabled={!athleteId} onClick={check}>
                        Check Eligibility
                    </Button>
                </CardContent>
            </Card>
            {selectedAthlete && (
                <div className="grid gap-6 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                {selectedAthlete.last_name},{' '}
                                {selectedAthlete.first_name}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <p>Athlete ID: {selectedAthlete.id}</p>
                            <p>Sex: {selectedAthlete.sex}</p>
                            <p>Date of Birth: {selectedAthlete.birthdate}</p>
                            <p>Grade: {selectedAthlete.grade_level}</p>
                            <p>School: {selectedAthlete.school?.name}</p>
                            <p>
                                Delegation:{' '}
                                {selectedAthlete.delegation?.district?.name ??
                                    '—'}
                            </p>
                        </CardContent>
                    </Card>
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Eligibility Check Results</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {evaluation?.rules.map((rule) => (
                                <div
                                    key={rule.rule}
                                    className="flex gap-3 rounded-lg border p-3"
                                >
                                    <span className="font-semibold">
                                        {rule.status === 'passed'
                                            ? '✓'
                                            : rule.status === 'failed'
                                              ? '✕'
                                              : '!'}
                                    </span>
                                    <div>
                                        <p className="font-medium">
                                            {rule.rule}
                                        </p>
                                        <p className="text-xs font-medium text-primary">
                                            Authority: {rule.authority}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Expected: {rule.expected} · Actual:{' '}
                                            {rule.actual}
                                        </p>
                                    </div>
                                </div>
                            ))}
                            {evaluation && (
                                <div className="flex items-center justify-between rounded-lg border p-4">
                                    <div className="flex items-center gap-2 font-bold">
                                        <Icon className="size-5" />
                                        RESULT:{' '}
                                        {evaluation.result
                                            .replace('_', ' ')
                                            .toUpperCase()}
                                    </div>
                                    <Button
                                        variant="outline"
                                        onClick={() => window.print()}
                                    >
                                        <Printer className="mr-2 size-4" />
                                        Print / PDF
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            )}
        </>
    );
}
