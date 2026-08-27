import { Head, Link } from '@inertiajs/react';
import { PortalHero } from '@/apps/portal/components/hero';
import type { PortalMeetSummary } from '@/apps/portal/types';
type Props = {
    meet: PortalMeetSummary;
    item: {
        title: string;
        summary: string | null;
        body: string;
        published_at: string;
    };
};
export default function PortalNewsShow({ meet, item }: Props) {
    return (
        <>
            <Head title={`${item.title} — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PortalHero
                    title={item.title}
                    description={item.summary ?? undefined}
                    eyebrow={item.published_at}
                />
                <article className="mx-auto w-full max-w-3xl rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-6 text-base leading-8 whitespace-pre-wrap sm:p-10">
                    {item.body}
                </article>
                <Link
                    href="/news"
                    className="self-center font-semibold text-[var(--portal-maroon)]"
                >
                    ← Back to News
                </Link>
            </div>
        </>
    );
}
