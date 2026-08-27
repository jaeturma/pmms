import { Head, Link } from '@inertiajs/react';
import { CircleHelp, Images, Megaphone, Newspaper } from 'lucide-react';
import { PageHeader } from '@/components/page-header';

type Props = { canManageEditorial: boolean; counts: Record<string, number> };

export default function ContentDashboard({
    canManageEditorial,
    counts,
}: Props) {
    const cards = [
        ...(canManageEditorial
            ? [
                  {
                      title: 'News',
                      href: '/content/news',
                      icon: Newspaper,
                      detail: `${counts.news_published} published · ${counts.news_draft} draft`,
                  },
                  {
                      title: 'Announcements',
                      href: '/announcements',
                      icon: Megaphone,
                      detail: `${counts.announcements_active} active`,
                  },
                  {
                      title: 'FAQ',
                      href: '/content/faq',
                      icon: CircleHelp,
                      detail: `${counts.faq_published} published · ${counts.faq_draft} draft`,
                  },
              ]
            : []),
        {
            title: 'Gallery',
            href: '/content/gallery',
            icon: Images,
            detail: `${counts.gallery_pending} pending · ${counts.gallery_published_today} published today`,
        },
    ];
    return (
        <>
            <Head title="Content Management" />
            <div className="space-y-6">
                <PageHeader
                    title="Content Management"
                    description="Editorial publishing and moderated gallery workflow."
                />
                <div className="grid gap-4 sm:grid-cols-2">
                    {cards.map(({ title, href, icon: Icon, detail }) => (
                        <Link
                            key={title}
                            href={href}
                            className="rounded-xl border bg-card p-6 transition hover:border-primary"
                        >
                            <Icon className="size-7 text-primary" />
                            <h2 className="mt-4 text-lg font-semibold">
                                {title}
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {detail}
                            </p>
                        </Link>
                    ))}
                </div>
            </div>
        </>
    );
}
