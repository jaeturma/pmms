import { Head } from '@inertiajs/react';
import { Image as ImageIcon } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import type { PortalMeetSummary } from '@/apps/portal/types';

type GalleryItem = {
    id: number;
    title: string | null;
    caption: string | null;
    sport: string | null;
    event: string | null;
    capture_date: string;
    image_url: string;
    is_featured: boolean;
};
type Props = { meet: PortalMeetSummary; items: GalleryItem[] };
export default function PortalGallery({ meet, items }: Props) {
    return (
        <>
            <Head title={`Gallery — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PortalHero
                    title="Gallery"
                    description="Information Team-selected moments from across the meet."
                />
                {items.length === 0 ? (
                    <PortalEmptyState
                        icon={ImageIcon}
                        title="No published photos yet"
                        description="Reviewed photos will appear here once published."
                    />
                ) : (
                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        {items.map((item) => (
                            <figure
                                key={item.id}
                                className={`portal-animate-in overflow-hidden rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] ${item.is_featured ? 'sm:col-span-2' : ''}`}
                            >
                                <img
                                    src={item.image_url}
                                    alt={
                                        item.caption ??
                                        `${item.sport ?? 'Meet'} gallery photo`
                                    }
                                    className="aspect-[4/3] w-full object-cover"
                                    loading="lazy"
                                />
                                <figcaption className="p-4">
                                    <p className="text-xs font-bold tracking-wide text-[var(--portal-maroon)] uppercase">
                                        {item.sport}
                                        {item.event ? ` · ${item.event}` : ''}
                                    </p>
                                    {item.title && (
                                        <h2 className="mt-1 font-semibold">
                                            {item.title}
                                        </h2>
                                    )}
                                    <p className="mt-1 text-sm text-[var(--portal-muted-foreground)]">
                                        {item.caption}
                                    </p>
                                    <p className="mt-3 text-xs text-[var(--portal-muted-foreground)]">
                                        {item.capture_date}
                                    </p>
                                </figcaption>
                            </figure>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
