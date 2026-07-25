import { Megaphone } from 'lucide-react';
import Heading from '@/components/heading';

type Announcement = {
    id: number;
    title: string;
    body: string;
    meet: string | null;
    published_at: string | null;
};

type Props = {
    announcements: Announcement[];
    showMeet?: boolean;
};

/**
 * Published announcements on the public portal. Renders nothing when
 * there are none — announcements are supplementary content.
 */
export function PublicAnnouncements({
    announcements,
    showMeet = false,
}: Props) {
    if (announcements.length === 0) {
        return null;
    }

    return (
        <section className="flex flex-col gap-3">
            <Heading variant="small" title="Announcements" />
            <ul className="flex flex-col gap-3">
                {announcements.map((announcement) => (
                    <li key={announcement.id} className="rounded-xl border p-4">
                        <div className="flex items-start gap-2">
                            <Megaphone
                                aria-hidden="true"
                                className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                            />
                            <div className="min-w-0">
                                <p className="font-medium">
                                    {announcement.title}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {announcement.published_at}
                                    {showMeet &&
                                        announcement.meet &&
                                        ` · ${announcement.meet}`}
                                </p>
                                <p className="mt-2 text-sm whitespace-pre-line">
                                    {announcement.body}
                                </p>
                            </div>
                        </div>
                    </li>
                ))}
            </ul>
        </section>
    );
}
