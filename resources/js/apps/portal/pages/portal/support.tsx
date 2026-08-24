import { Head, Link } from '@inertiajs/react';
import {
    ClipboardCheck,
    Headphones,
    Laptop,
    UserRoundCheck,
    Users,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { PortalHero } from '@/apps/portal/components/hero';
import type { PortalMeetSummary } from '@/apps/portal/types';
import { login, register } from '@/routes';

type Props = { meet: PortalMeetSummary };

type GuideProps = {
    icon: ReactNode;
    title: string;
    subtitle: string;
    steps: string[];
};

function Guide({ icon, title, subtitle, steps }: GuideProps) {
    return (
        <section className="overflow-hidden rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)]">
            <div className="flex items-start gap-3 border-b border-[var(--portal-border)] p-5">
                <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[var(--portal-accent)]/10 text-[var(--portal-accent)]">
                    {icon}
                </span>
                <div>
                    <h2 className="text-lg font-bold text-[var(--portal-fg)]">
                        {title}
                    </h2>
                    <p className="mt-1 text-sm text-[var(--portal-muted-foreground)]">
                        {subtitle}
                    </p>
                </div>
            </div>
            <ol className="space-y-0 p-5">
                {steps.map((step, index) => (
                    <li
                        key={step}
                        className="flex gap-4 border-b border-[var(--portal-border)] py-3 first:pt-0 last:border-0 last:pb-0"
                    >
                        <span className="flex size-7 shrink-0 items-center justify-center rounded-full bg-[var(--portal-accent)] text-xs font-bold text-[var(--portal-accent-foreground)]">
                            {index + 1}
                        </span>
                        <p className="pt-0.5 text-sm leading-6 text-[var(--portal-fg)]">
                            {step}
                        </p>
                    </li>
                ))}
            </ol>
        </section>
    );
}

export default function PortalSupport({ meet }: Props) {
    return (
        <>
            <Head title={`Support — ${meet.name}`} />
            <div className="flex flex-col gap-8">
                <PortalHero
                    icon={<Headphones className="size-12" aria-hidden="true" />}
                    title="Support"
                    description="Process guides for Tournament Management personnel and Coaches."
                    meta={<span>{meet.name}</span>}
                />

                <div className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-muted)] p-4 text-sm text-[var(--portal-fg)]">
                    Sign in with your assigned account before following these
                    procedures. Menu options are based on your role and assigned
                    sport.
                    <Link
                        href={login().url}
                        className="ml-2 font-semibold text-[var(--portal-accent)] hover:underline"
                    >
                        Staff login →
                    </Link>
                </div>

                <div>
                    <div className="mb-4 flex items-center gap-3">
                        <Users
                            className="size-6 text-[var(--portal-accent)]"
                            aria-hidden="true"
                        />
                        <div>
                            <h2 className="text-xl font-bold">
                                Tournament Management
                            </h2>
                            <p className="text-sm text-[var(--portal-muted-foreground)]">
                                Coordinate entries, schedules, matches, scoring,
                                and verified results.
                            </p>
                        </div>
                    </div>
                    <div className="grid gap-5 lg:grid-cols-3">
                        <Guide
                            icon={<UserRoundCheck className="size-5" />}
                            title="Tournament Manager"
                            subtitle="Lead the assigned sport from entry review through official results."
                            steps={[
                                'Open the Dashboard and confirm that the correct sport assignment appears on your account.',
                                'Review Entries for your sport and confirm that eligible participants and teams are ready before scheduling.',
                                'Open Schedule to arrange events, dates, times, venues, categories, and competition areas.',
                                'Use Matches to prepare brackets or matchups and monitor the progress of each contest.',
                                'Review encoded results, return incorrect records for correction, and complete the required confirmation or validation step.',
                                'Check the public Results and Medal Tally pages after validation to confirm that published information is correct.',
                            ]}
                        />
                        <Guide
                            icon={<ClipboardCheck className="size-5" />}
                            title="Tournament Secretary"
                            subtitle="Maintain complete and accurate tournament records."
                            steps={[
                                'Verify your meet and sport assignment on the Dashboard before processing records.',
                                'Check Entries against the approved participant list and report missing or duplicate records before competition.',
                                'Keep Schedule and Matches updated when the Tournament Manager approves a change.',
                                'Encode results carefully from the signed result sheet, including placements, marks, scores, and remarks where applicable.',
                                'Submit the encoded result for review; do not publish information from an unsigned or incomplete source document.',
                                'Monitor returned results, apply the requested corrections, and resubmit them promptly.',
                            ]}
                        />
                        <Guide
                            icon={<Laptop className="size-5" />}
                            title="Tournament ICT"
                            subtitle="Operate event data and live-scoring tools reliably."
                            steps={[
                                'Confirm the assigned sport, venue connectivity, device power, and account access before the first event.',
                                'Review the day’s Schedule and Matches and confirm participant or team names before opening a scoring session.',
                                'Start live scoring only for the correct match, update scores and game state as confirmed by officials, and end the session after the final signal.',
                                'Coordinate corrections with the Tournament Secretary and Tournament Manager instead of changing an approved result independently.',
                                'Verify that completed matches and validated results appear correctly on the public sport page.',
                                'If connectivity fails, preserve the signed source records and encode or synchronize the result when service is restored.',
                            ]}
                        />
                    </div>
                </div>

                <div>
                    <div className="mb-4 flex items-center gap-3">
                        <UserRoundCheck
                            className="size-6 text-[var(--portal-accent)]"
                            aria-hidden="true"
                        />
                        <div>
                            <h2 className="text-xl font-bold">Coaches</h2>
                            <p className="text-sm text-[var(--portal-muted-foreground)]">
                                Register athletes completely and submit the
                                required eligibility records.
                            </p>
                        </div>
                    </div>
                    <Guide
                        icon={<UserRoundCheck className="size-5" />}
                        title="How to register an athlete"
                        subtitle="Prepare clear document images before starting so registration can be completed in one session."
                        steps={[
                            'Create a Coach account from the registration page, select the correct delegation and school, then wait for account approval if required.',
                            'Sign in, open Athletes from the dashboard or sidebar, and select Register athlete.',
                            'Choose the correct delegation and school, then enter the athlete’s 12-digit LRN and complete legal name exactly as shown in school records.',
                            'Complete the athlete’s personal, academic, sport, age-division, and contact information. Review dates and category selections carefully.',
                            'Upload clear JPG or PNG copies of the required eligibility documents: Athlete History, School Form 10, school ID, birth certificate, parental consent, and medical certificate.',
                            'Add the athlete and sports photos where available, review every field, then submit the registration.',
                            'Open the athlete record afterward to monitor eligibility or review status. Correct and resubmit any item returned by the reviewer.',
                            'After approval, use Entries to enter the athlete in the correct event or team roster before the registration deadline.',
                        ]}
                    />
                    <p className="mt-4 text-sm text-[var(--portal-muted-foreground)]">
                        Need a coach account?{' '}
                        <Link
                            href={register().url}
                            className="font-semibold text-[var(--portal-accent)] hover:underline"
                        >
                            Open registration →
                        </Link>
                    </p>
                </div>
            </div>
        </>
    );
}
