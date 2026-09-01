import { Head, Link } from '@inertiajs/react';
import {
    Award,
    CalendarDays,
    Crown,
    Dumbbell,
    MapPin,
    Newspaper,
} from 'lucide-react';
import { PublicPageHero } from '@/components/public-page-hero';
import { Badge } from '@/components/ui/badge';
import {
    meet as publicMeet,
    news as publicNews,
    results as publicResults,
    sports as publicSports,
    tally as publicTally,
} from '@/routes/public';

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

export default function PublicContact({ meet }: Props) {
    const quickLinks = [
        {
            label: 'Schedule of Events',
            href: publicMeet(meet.id).url,
            icon: CalendarDays,
        },
        { label: 'Results', href: publicResults(meet.id).url, icon: Award },
        { label: 'Medal tally', href: publicTally(meet.id).url, icon: Crown },
        { label: 'Sports', href: publicSports(meet.id).url, icon: Dumbbell },
        { label: 'News', href: publicNews(meet.id).url, icon: Newspaper },
    ];

    return (
        <>
            <Head title={`Contact — ${meet.name}`} />
            <div className="flex flex-col gap-6 sm:gap-8">
                <PublicPageHero
                    title="Contact"
                    description="Meet information and quick links to the rest of the portal."
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

                <div className="rounded-xl border p-5">
                    <h2 className="font-semibold">{meet.name}</h2>
                    <dl className="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                        <div className="flex items-center gap-2">
                            <CalendarDays
                                aria-hidden="true"
                                className="size-4 shrink-0 text-muted-foreground"
                            />
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Dates
                                </dt>
                                <dd>
                                    {meet.starts_at} – {meet.ends_at} (SY{' '}
                                    {meet.school_year})
                                </dd>
                            </div>
                        </div>
                        {meet.venue && (
                            <div className="flex items-center gap-2">
                                <MapPin
                                    aria-hidden="true"
                                    className="size-4 shrink-0 text-muted-foreground"
                                />
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Venue
                                    </dt>
                                    <dd>{meet.venue}</dd>
                                </div>
                            </div>
                        )}
                    </dl>
                </div>

                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4">
                    {quickLinks.map((link) => (
                        <Link
                            key={link.label}
                            href={link.href}
                            className="flex flex-col items-center gap-2 rounded-xl border p-4 text-center text-sm font-medium transition hover:bg-accent"
                        >
                            <link.icon
                                aria-hidden="true"
                                className="size-5 text-muted-foreground"
                            />
                            {link.label}
                        </Link>
                    ))}
                </div>
            </div>
        </>
    );
}
