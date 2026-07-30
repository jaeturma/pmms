import { Head } from '@inertiajs/react';
import { Newspaper } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalPagination } from '@/apps/portal/components/pagination';
import type { PortalMeetSummary, PortalPaginatedAnnouncement } from '@/apps/portal/types';

type Props = {
    meet: PortalMeetSummary;
    announcements: PortalPaginatedAnnouncement;
};

export default function PortalNews({ meet, announcements }: Props) {
    return (
        <>
            <Head title={`News — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PortalHero eyebrow="News" title={meet.name} description="All published announcements for this meet." />

                {announcements.data.length === 0 ? (
                    <PortalEmptyState icon={Newspaper} title="No announcements yet" />
                ) : (
                    <div className="space-y-3">
                        {announcements.data.map((announcement) => (
                            <div key={announcement.id} className="portal-animate-in rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4">
                                <p className="font-semibold">{announcement.title}</p>
                                <p className="mt-1 text-sm text-[var(--portal-muted-foreground)]">{announcement.body}</p>
                                {announcement.published_at && <p className="mt-2 text-xs text-[var(--portal-muted-foreground)]">{announcement.published_at}</p>}
                            </div>
                        ))}
                    </div>
                )}

                <PortalPagination links={announcements.links} />
            </div>
        </>
    );
}
