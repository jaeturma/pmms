import { Head } from '@inertiajs/react';
import { PublicPageHero } from '@/components/public-page-hero';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';

type Props = {
    meet: {
        id: number;
        name: string;
        school_year: string;
        starts_at: string;
        ends_at: string;
        venue: string | null;
        status_label: string;
    };
};

export default function PublicFaqs({ meet }: Props) {
    /**
     * Question text is written copy; every answer traces to real data
     * (`meet.*`, the same fields every other public page already
     * shows) or documented portal behavior (`docs/public-portal.md`'s
     * publication/validation/live-provisional rules, and `tally.tsx`'s
     * own rank-order disclaimer) — nothing invented.
     */
    const faqs = [
        {
            question: 'When is this meet happening?',
            answer: `${meet.name} runs ${meet.starts_at} – ${meet.ends_at} (SY ${meet.school_year})${meet.venue ? ` at ${meet.venue}` : ''}. Current status: ${meet.status_label}.`,
        },
        {
            question: 'What does "published" mean?',
            answer: 'A meet only appears on this portal once an organizer or administrator publishes it. Nothing about a meet — its schedule, results, or medal tally — is shown here before that.',
        },
        {
            question: 'What does "validated" mean for results?',
            answer: "Results shown on the Results and Medal Tally pages are always validated (official). An encoded result that hasn't been validated yet, or one that was reopened for correction, never appears here.",
        },
        {
            question: 'Are live scores official?',
            answer: 'No. A live scoreboard shows a match in progress and is always provisional, clearly marked as such — the official result only appears on the Results page once it has been validated.',
        },
        {
            question: 'How is the medal tally ranking decided?',
            answer: 'Rank is based on Gold medals first, then Silver, then Bronze. Points (Gold = 3, Silver = 2, Bronze = 1) are shown for reference and power the "Top by points" list — they do not change the official rank order.',
        },
        {
            question: 'Where can I find the schedule?',
            answer: "The Schedule page lists this meet's events by day and venue, and updates automatically as slots are added or adjusted.",
        },
    ];

    return (
        <>
            <Head title={`FAQs — ${meet.name}`} />
            <div className="flex flex-col gap-6 sm:gap-8">
                <PublicPageHero
                    title="FAQs"
                    description="Common questions about how this portal works."
                    meta={
                        <div className="flex flex-wrap items-center gap-2">
                            <span>{meet.name}</span>
                            <Badge
                                variant="secondary"
                                className="bg-white/15 text-white"
                            >
                                {meet.status_label}
                            </Badge>
                        </div>
                    }
                />

                <div className="animate-card-in rounded-xl border px-5">
                    <Accordion type="single" collapsible>
                        {faqs.map((faq, index) => (
                            <AccordionItem
                                key={faq.question}
                                value={`item-${index}`}
                            >
                                <AccordionTrigger>
                                    {faq.question}
                                </AccordionTrigger>
                                <AccordionContent>
                                    {faq.answer}
                                </AccordionContent>
                            </AccordionItem>
                        ))}
                    </Accordion>
                </div>
            </div>
        </>
    );
}
