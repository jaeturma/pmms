import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { PortalHero } from '@/apps/portal/components/hero';
import type { PortalMeetSummary } from '@/apps/portal/types';

type Faq = {
    id: number;
    question: string;
    answer: string;
    category: string;
    is_featured: boolean;
};
type Props = { meet: PortalMeetSummary; items: Faq[] };
export default function PortalFaqs({ meet, items }: Props) {
    const [search, setSearch] = useState('');
    const [category, setCategory] = useState('All');
    const categories = [
        'All',
        ...Array.from(new Set(items.map((item) => item.category))),
    ];
    const filtered = useMemo(
        () =>
            items.filter(
                (item) =>
                    (category === 'All' || item.category === category) &&
                    `${item.question} ${item.answer}`
                        .toLowerCase()
                        .includes(search.toLowerCase()),
            ),
        [items, search, category],
    );
    return (
        <>
            <Head title={`FAQ — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PortalHero
                    title="FAQ"
                    description="Answers to common questions about the meet and public portal."
                />
                <div className="grid gap-3 sm:grid-cols-[1fr_auto]">
                    <input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search questions"
                        className="h-11 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] px-4"
                    />
                    <select
                        value={category}
                        onChange={(e) => setCategory(e.target.value)}
                        className="h-11 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] px-4"
                    >
                        {categories.map((value) => (
                            <option key={value}>{value}</option>
                        ))}
                    </select>
                </div>
                <div className="space-y-3">
                    {filtered.map((item) => (
                        <details
                            key={item.id}
                            className="group rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4"
                            open={item.is_featured}
                        >
                            <summary className="cursor-pointer list-none font-semibold text-[var(--portal-ink)]">
                                {item.question}
                                <span className="float-right group-open:rotate-45">
                                    +
                                </span>
                            </summary>
                            <p className="mt-3 text-sm leading-7 whitespace-pre-wrap text-[var(--portal-muted-foreground)]">
                                {item.answer}
                            </p>
                        </details>
                    ))}
                    {filtered.length === 0 && (
                        <p className="py-10 text-center text-[var(--portal-muted-foreground)]">
                            No published answers match your search.
                        </p>
                    )}
                </div>
            </div>
        </>
    );
}
