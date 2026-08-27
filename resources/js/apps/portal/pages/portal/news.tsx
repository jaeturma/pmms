import { Head, Link } from '@inertiajs/react';
import { Newspaper } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import type { PortalMeetSummary } from '@/apps/portal/types';

type NewsItem = {
    id: number;
    title: string;
    slug: string;
    summary: string | null;
    body: string;
    is_featured: boolean;
    published_at: string;
    featured_image_url: string | null;
};
type Props = { meet: PortalMeetSummary; news: { data: NewsItem[] } };
export default function PortalNews({ meet, news }: Props) {
    return (
        <>
            <Head title={`News — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PortalHero
                    title="News & Announcements"
                    description="Official stories and updates from the meet."
                />
                {news.data.length === 0 ? (
                    <PortalEmptyState
                        icon={Newspaper}
                        title="No published news yet"
                    />
                ) : (
                    <div className="grid gap-5 md:grid-cols-2">
                        {news.data.map((item) => (
                            <article
                                key={item.id}
                                className={`rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-6 ${item.is_featured ? 'md:col-span-2' : ''}`}
                            >
                                {item.featured_image_url && (
                                    <img
                                        src={item.featured_image_url}
                                        alt=""
                                        className="mb-5 aspect-[16/7] w-full rounded-[calc(var(--portal-radius)-0.25rem)] object-cover"
                                        loading="lazy"
                                    />
                                )}
                                <p className="text-xs text-[var(--portal-muted-foreground)]">
                                    {item.published_at}
                                </p>
                                <h2 className="mt-2 text-xl font-bold text-[var(--portal-ink)]">
                                    {item.title}
                                </h2>
                                <p className="mt-3 text-sm leading-7 text-[var(--portal-muted-foreground)]">
                                    {item.summary ?? item.body.slice(0, 220)}
                                </p>
                                <Link
                                    href={`/news/${item.slug}`}
                                    className="mt-4 inline-flex font-semibold text-[var(--portal-maroon)]"
                                >
                                    Read story →
                                </Link>
                            </article>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
