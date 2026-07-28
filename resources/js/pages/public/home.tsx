import { Head, Link } from '@inertiajs/react';
import {
    Award,
    CalendarClock,
    CalendarDays,
    Crown,
    Flag,
    MapPin,
    Medal,
    Users,
} from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PodiumDisplay } from '@/components/podium-display';
import { PublicAnnouncements } from '@/components/public-announcements';
import { PublicLiveMatches } from '@/components/public-live-matches';
import type { LiveMatch } from '@/components/public-live-matches';
import { PublicPageHero } from '@/components/public-page-hero';
import { TeamLogo } from '@/components/team-logo';
import { TopByPointsCard } from '@/components/top-by-points-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

type Leader = {
    district: string;
    points: number;
};

type UpcomingEvent = {
    id: number;
    date: string;
    starts_at: string;
    event: string;
    venue: string;
};

type ResultPlacement = {
    rank: number;
    athlete: string;
    school: string;
    delegation: string;
    mark: string | null;
    is_tie: boolean;
};

type LatestResult = {
    event: string;
    official_as_of: string | null;
    placements: ResultPlacement[];
};

type ClosingSummary = {
    champion: string;
    gold: number;
    silver: number;
    bronze: number;
    total: number;
};

type Props = {
    meet: PublicMeet | null;
    municipalities: Municipality[];
    announcements: Announcement[];
    liveMatches: LiveMatch[];
    currentLeaders: Leader[];
    upcomingEvents: UpcomingEvent[];
    latestResult: LatestResult | null;
    closingSummary: ClosingSummary | null;
};

export default function PublicHome({
    meet,
    municipalities,
    announcements,
    liveMatches,
    currentLeaders,
    upcomingEvents,
    latestResult,
    closingSummary,
}: Props) {
    if (meet === null) {
        return (
            <>
                <Head title="Provincial Meet" />
                <div className="flex min-h-[calc(100vh-10rem)] flex-col gap-6 sm:gap-8">
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
            <div className="flex min-h-[calc(100vh-10rem)] flex-col gap-8 sm:gap-12">
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

                {closingSummary && (
                    <Card className="animate-card-in border-medal-gold/40 bg-medal-gold/5">
                        <CardContent className="flex flex-wrap items-center gap-4 pt-6">
                            <Crown
                                aria-hidden="true"
                                className="size-10 shrink-0 text-medal-gold"
                            />
                            <div className="flex-1">
                                {/* `bg-medal-gold text-medal-gold-foreground`
                                    — the same solid-fill pairing
                                    `RankBadge` already uses, not gold
                                    text on a light background (WP-08.5-09:
                                    `--medal-gold`'s lightness is high
                                    enough that gold-on-white reads poorly;
                                    the dedicated `-foreground` token exists
                                    exactly to pair with a gold fill). */}
                                <Badge className="mb-1 bg-medal-gold text-medal-gold-foreground">
                                    Meet concluded
                                </Badge>
                                <p className="text-lg font-semibold">
                                    Champion: {closingSummary.champion}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {closingSummary.gold} gold ·{' '}
                                    {closingSummary.silver} silver ·{' '}
                                    {closingSummary.bronze} bronze ·{' '}
                                    {closingSummary.total} total medals
                                </p>
                            </div>
                            <Medal
                                aria-hidden="true"
                                className="size-8 shrink-0 text-muted-foreground"
                            />
                        </CardContent>
                    </Card>
                )}

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

                <PublicAnnouncements announcements={announcements} />

                <PublicLiveMatches meetId={meet.id} matches={liveMatches} />

                <section className="grid gap-4 sm:gap-6 lg:grid-cols-3">
                    <div className="animate-card-in">
                        <TopByPointsCard
                            rows={currentLeaders}
                            areaLabel="district"
                        />
                    </div>

                    <Card
                        className="animate-card-in"
                        style={{ animationDelay: '80ms' }}
                    >
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">
                                Upcoming events
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {upcomingEvents.length === 0 ? (
                                <EmptyState
                                    icon={CalendarClock}
                                    title="Nothing scheduled next"
                                />
                            ) : (
                                <ul className="space-y-3">
                                    {upcomingEvents.map((event) => (
                                        <li
                                            key={event.id}
                                            className="flex items-start gap-2 text-sm"
                                        >
                                            <CalendarClock
                                                aria-hidden="true"
                                                className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                            />
                                            <span>
                                                <span className="block font-medium">
                                                    {event.event}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {event.date},{' '}
                                                    {event.starts_at} ·{' '}
                                                    {event.venue}
                                                </span>
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <Card
                        className="animate-card-in"
                        style={{ animationDelay: '160ms' }}
                    >
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">
                                Latest official result
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {latestResult === null ? (
                                <EmptyState
                                    icon={Award}
                                    title="No results yet"
                                />
                            ) : (
                                <div className="space-y-1">
                                    <div>
                                        <p className="text-sm font-medium">
                                            {latestResult.event}
                                        </p>
                                        {latestResult.official_as_of && (
                                            <p className="text-xs text-muted-foreground">
                                                Official as of{' '}
                                                {latestResult.official_as_of}
                                            </p>
                                        )}
                                    </div>
                                    <PodiumDisplay
                                        placements={latestResult.placements}
                                    />
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <section className="flex flex-1 flex-col gap-4 sm:gap-6">
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
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                            {municipalities.map((municipality, i) => (
                                <div
                                    key={municipality.id}
                                    className="flex animate-card-in flex-col items-center gap-2 rounded-xl border p-4 text-center transition-[transform,box-shadow] duration-(--duration-base) ease-premium hover:-translate-y-0.5 hover:shadow-md sm:p-5"
                                    style={{
                                        animationDelay: `${Math.min(i, 12) * 30}ms`,
                                    }}
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
            </div>
        </>
    );
}
