import { Head } from '@inertiajs/react';
import { Newspaper } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PaginationControls } from '@/components/pagination-controls';
import type { Paginated } from '@/components/pagination-controls';
import { PublicAnnouncements } from '@/components/public-announcements';
import { PublicPageHero } from '@/components/public-page-hero';
import { Badge } from '@/components/ui/badge';
import { news as publicNews } from '@/routes/public';

type Announcement = {
    id: number;
    title: string;
    body: string;
    meet: string | null;
    published_at: string | null;
};

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
    announcements: Paginated<Announcement>;
};

export default function PublicNews({ meet, announcements }: Props) {
    return (
        <>
            <Head title={`News — ${meet.name}`} />
            <div className="flex flex-col gap-6 sm:gap-8">
                <PublicPageHero
                    title="News"
                    description="Announcements and updates for this meet."
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

                {announcements.data.length === 0 ? (
                    <EmptyState
                        icon={Newspaper}
                        title="No announcements yet"
                        description="Updates about this meet will appear here as they're published."
                    />
                ) : (
                    <div className="flex animate-card-in flex-col gap-4">
                        <PublicAnnouncements
                            announcements={announcements.data}
                        />
                        <PaginationControls
                            page={announcements}
                            url={publicNews(meet.id).url}
                            label="announcements"
                        />
                    </div>
                )}
            </div>
        </>
    );
}
