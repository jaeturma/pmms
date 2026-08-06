import { Head } from '@inertiajs/react';
import { PortalHero } from '@/apps/portal/components/hero';
import type { PortalMeetSummary } from '@/apps/portal/types';

type Props = {
    meet: PortalMeetSummary;
};

function faqItems(meet: PortalMeetSummary): Array<{ question: string; answer: string }> {
    return [
        {
            question: 'When is this meet held?',
            answer: `${meet.name} runs from ${meet.starts_at} to ${meet.ends_at} (SY ${meet.school_year}), currently ${meet.status_label.toLowerCase()}.`,
        },
        {
            question: 'Are results shown here final?',
            answer: 'Only validated results appear on the Results page. Encoded-but-not-yet-validated results never show publicly, and a corrected result replaces the old one automatically.',
        },
        {
            question: 'Are live scores official?',
            answer: 'No — live scoreboards are always provisional. The official result for a match only appears once it has been validated, on the Results page.',
        },
        {
            question: 'How is the medal tally ranked?',
            answer: 'Districts are ranked by a weighted points formula from gold/silver/bronze medal counts, derived only from validated results.',
        },
    ];
}

export default function PortalFaqs({ meet }: Props) {
    return (
        <>
            <Head title={`FAQs — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PortalHero title="FAQs" description="Common questions about how this portal works." />

                <div className="space-y-3">
                    {faqItems(meet).map((item) => (
                        <div key={item.question} className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4">
                            <p className="font-semibold">{item.question}</p>
                            <p className="mt-1 text-sm text-[var(--portal-muted-foreground)]">{item.answer}</p>
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}
