import { Head, Link } from '@inertiajs/react';
import { Award, CalendarDays, Crown, Flag, MapPin, Users } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PublicAnnouncements } from '@/components/public-announcements';
import { PublicPageHero } from '@/components/public-page-hero';
import { TeamLogo } from '@/components/team-logo';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    meet as publicMeet,
    results as publicResults,
    tally as publicTally,
} from '@/routes/public';

type Announcement = {
    id: number;
    title: string;
    body: string;
    meet: string | null;
    published_at: string | null;
};

type PublicMeet = {
    id: number;
    name: string;
    school_year: string;
    starts_at: string;
    ends_at: string;
    venue: string | null;
    status_label: string;
};

type Municipality = {
    id: number;
    name: string;
    nickname: string | null;
};

type Props = {
    meet: PublicMeet | null;
    municipalities: Municipality[];
    announcements: Announcement[];
};

export default function PublicHome({
    meet,
    municipalities,
    announcements,
}: Props) {
    if (meet === null) {
        return (
            <>
                <Head title="Provincial Meet" />
                <div className="flex min-h-[calc(100vh-10rem)] flex-col gap-6">
                    <PublicPageHero
                        title="Provincial Meet"
                        description="Schedules, results, and medal standings of the Schools Division Office athletic meets."
                    />
                    <div className="flex flex-1 items-center justify-center">
                        <EmptyState
                            icon={Flag}
                            title="No meet is active right now"
                            description="Check back here once the next meet is underway — schedules and results will appear as soon as it's published."
                        />
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title={meet.name} />
            <div className="flex min-h-[calc(100vh-10rem)] flex-col gap-8">
                <PublicPageHero
                    title={meet.name}
                    description="Schedules, results, and medal standings of the Schools Division Office athletic meet."
                    meta={
                        <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
                            <Badge
                                variant="secondary"
                                className="bg-white/15 text-white"
                            >
                                {meet.status_label}
                            </Badge>
                            <span>SY {meet.school_year}</span>
                            <span className="flex items-center gap-1.5">
                                <CalendarDays
                                    aria-hidden="true"
                                    className="size-4 shrink-0"
                                />
                                {meet.starts_at} – {meet.ends_at}
                            </span>
                            {meet.venue && (
                                <span className="flex items-center gap-1.5">
                                    <MapPin
                                        aria-hidden="true"
                                        className="size-4 shrink-0"
                                    />
                                    {meet.venue}
                                </span>
                            )}
                        </div>
                    }
                />

                <div className="flex flex-wrap gap-3">
                    <Button size="lg" asChild>
                        <Link href={publicMeet(meet.id)}>
                            <CalendarDays aria-hidden="true" />
                            View schedule
                        </Link>
                    </Button>
                    <Button size="lg" variant="outline" asChild>
                        <Link href={publicResults(meet.id)}>
                            <Award aria-hidden="true" />
                            Results
                        </Link>
                    </Button>
                    <Button size="lg" variant="outline" asChild>
                        <Link href={publicTally(meet.id)}>
                            <Crown aria-hidden="true" />
                            Medal tally
                        </Link>
                    </Button>
                </div>

                <section className="flex flex-1 flex-col gap-4">
                    <div className="flex items-center gap-2">
                        <Users
                            aria-hidden="true"
                            className="size-5 text-muted-foreground"
                        />
                        <h2 className="text-lg font-semibold">
                            Competing municipalities
                        </h2>
                        {municipalities.length > 0 && (
                            <Badge variant="secondary">
                                {municipalities.length}
                            </Badge>
                        )}
                    </div>

                    {municipalities.length === 0 ? (
                        <EmptyState
                            icon={Users}
                            title="No delegations registered yet"
                            description="Competing municipalities will appear here once their delegations are registered."
                        />
                    ) : (
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                            {municipalities.map((municipality) => (
                                <div
                                    key={municipality.id}
                                    className="flex flex-col items-center gap-2 rounded-xl border p-4 text-center transition hover:shadow-md"
                                >
                                    <TeamLogo
                                        name={municipality.name}
                                        className="size-14 text-lg sm:size-16 sm:text-xl"
                                    />
                                    <div>
                                        <p className="text-sm leading-snug font-medium">
                                            {municipality.name}
                                        </p>
                                        {municipality.nickname && (
                                            <p className="text-xs text-muted-foreground">
                                                "{municipality.nickname}"
                                            </p>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </section>

                <PublicAnnouncements announcements={announcements} />
            </div>
        </>
    );
}
