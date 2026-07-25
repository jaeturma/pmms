import { Head, Link } from '@inertiajs/react';
import { CalendarDays, Flag, MapPin } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PublicAnnouncements } from '@/components/public-announcements';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { meet as publicMeet } from '@/routes/public';

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

type Props = {
    meets: PublicMeet[];
    announcements: Announcement[];
};

export default function PublicHome({ meets, announcements }: Props) {
    return (
        <>
            <Head title="Provincial Meet" />
            <div className="flex flex-col gap-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Provincial Meet
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Schedules, results, and medal standings of the Schools
                        Division Office athletic meets.
                    </p>
                </div>

                {meets.length === 0 ? (
                    <EmptyState
                        icon={Flag}
                        title="No meets published yet"
                        description="Check back here once a meet is underway — schedules and results will appear as they are published."
                    />
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2">
                        {meets.map((meet) => (
                            <Link
                                key={meet.id}
                                href={publicMeet(meet.id)}
                                className="block rounded-xl transition hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <Card className="h-full">
                                    <CardHeader className="flex flex-row items-start justify-between gap-2">
                                        <CardTitle className="text-base leading-snug">
                                            {meet.name}
                                        </CardTitle>
                                        <Badge variant="secondary">
                                            {meet.status_label}
                                        </Badge>
                                    </CardHeader>
                                    <CardContent className="space-y-1.5 text-sm text-muted-foreground">
                                        <p>SY {meet.school_year}</p>
                                        <p className="flex items-center gap-1.5">
                                            <CalendarDays
                                                aria-hidden="true"
                                                className="size-4 shrink-0"
                                            />
                                            {meet.starts_at} – {meet.ends_at}
                                        </p>
                                        {meet.venue && (
                                            <p className="flex items-center gap-1.5">
                                                <MapPin
                                                    aria-hidden="true"
                                                    className="size-4 shrink-0"
                                                />
                                                {meet.venue}
                                            </p>
                                        )}
                                    </CardContent>
                                </Card>
                            </Link>
                        ))}
                    </div>
                )}

                <PublicAnnouncements announcements={announcements} showMeet />
            </div>
        </>
    );
}
