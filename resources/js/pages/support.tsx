import { Head } from '@inertiajs/react';
import { ClipboardCheck, Laptop, Trophy, UserRoundCheck } from 'lucide-react';
import type { ReactNode } from 'react';
import { PageHeader } from '@/components/page-header';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { support } from '@/routes';

type GuideProps = {
    icon: ReactNode;
    title: string;
    description: string;
    steps: string[];
};

function Guide({ icon, title, description, steps }: GuideProps) {
    return (
        <Card>
            <CardHeader>
                <div className="mb-2 flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    {icon}
                </div>
                <CardTitle>{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent>
                <ol className="space-y-4">
                    {steps.map((step, index) => (
                        <li key={step} className="flex gap-3 text-sm leading-6">
                            <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground">
                                {index + 1}
                            </span>
                            <span>{step}</span>
                        </li>
                    ))}
                </ol>
            </CardContent>
        </Card>
    );
}

export default function Support() {
    return (
        <>
            <Head title="Support" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Support"
                    description="Process guides for Tournament Management personnel and Coaches."
                />

                <section>
                    <div className="mb-4 flex items-center gap-3">
                        <Trophy className="size-6 text-primary" />
                        <div>
                            <h2 className="text-xl font-semibold">
                                Tournament Management
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Coordinate entries, schedules, scoring, and
                                official results.
                            </p>
                        </div>
                    </div>
                    <div className="grid gap-5 lg:grid-cols-3">
                        <Guide
                            icon={<Trophy className="size-5" />}
                            title="Tournament Manager"
                            description="Lead the assigned sport through official results."
                            steps={[
                                'Confirm your meet and sport assignment on the Dashboard.',
                                'Review eligible entries before preparing schedules and matches.',
                                'Arrange events, venues, competition areas, dates, and times.',
                                'Monitor each contest and review encoded results against signed source records.',
                                'Return incorrect records for correction and complete the required validation.',
                                'Verify validated results and medal standings before closing the event.',
                            ]}
                        />
                        <Guide
                            icon={<ClipboardCheck className="size-5" />}
                            title="Tournament Secretary"
                            description="Maintain complete and accurate tournament records."
                            steps={[
                                'Verify the meet, sport, and approved participant list before competition.',
                                'Report missing or duplicate entries before scheduling begins.',
                                'Keep schedules and matches updated after approved changes.',
                                'Encode placements, scores, marks, and remarks from the signed result sheet.',
                                'Submit completed results for review and never publish an incomplete source record.',
                                'Apply requested corrections and resubmit returned results promptly.',
                            ]}
                        />
                        <Guide
                            icon={<Laptop className="size-5" />}
                            title="Tournament ICT"
                            description="Operate event data and live-scoring tools reliably."
                            steps={[
                                'Check account access, devices, power, and venue connectivity before competition.',
                                'Confirm the scheduled match and participant names before opening live scoring.',
                                'Update scores and game state only as confirmed by officials.',
                                'End the scoring session after the final signal and preserve the signed record.',
                                'Coordinate corrections with the Manager and Secretary instead of editing approved results independently.',
                                'Verify completed matches and validated results in the published views.',
                            ]}
                        />
                    </div>
                </section>

                <section>
                    <div className="mb-4 flex items-center gap-3">
                        <UserRoundCheck className="size-6 text-primary" />
                        <div>
                            <h2 className="text-xl font-semibold">Coaches</h2>
                            <p className="text-sm text-muted-foreground">
                                Register athletes and submit complete
                                eligibility records.
                            </p>
                        </div>
                    </div>
                    <Guide
                        icon={<UserRoundCheck className="size-5" />}
                        title="How to register an athlete"
                        description="Prepare clear document images before starting."
                        steps={[
                            'Sign in with an approved Coach account and open Athletes.',
                            'Select Register athlete, then choose the correct delegation and school.',
                            'Enter the 12-digit LRN and legal name exactly as shown in school records.',
                            'Complete the personal, academic, sport, age-division, and contact information.',
                            'Upload clear copies of every required eligibility document and athlete photo.',
                            'Review all fields, submit the registration, and monitor its eligibility status.',
                            'Correct and resubmit any item returned by the reviewer.',
                            'After approval, add the athlete to the correct event or team roster before the deadline.',
                        ]}
                    />
                </section>
            </div>
        </>
    );
}

Support.layout = {
    breadcrumbs: [{ title: 'Support', href: support() }],
};
