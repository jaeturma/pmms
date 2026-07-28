import { Head, Link } from '@inertiajs/react';
import { CalendarDays, MapPin, Timer } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { PublicAnnouncements } from '@/components/public-announcements';
import { PublicLiveMatches } from '@/components/public-live-matches';
import type { LiveMatch } from '@/components/public-live-matches';
import { PublicMeetNav } from '@/components/public-meet-nav';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    athletics as publicAthletics,
    meet as publicMeet,
} from '@/routes/public';

type Slot = {
    id: number;
    starts_at: string;
    ends_at: string;
    event: string;
    note: string | null;
};

type VenueGroup = {
    venue: string;
    slots: Slot[];
};

type Day = {
    value: string;
    label: string;
};

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
    announcements: Announcement[];
    days: Day[];
    selectedDay: string | null;
    venuesForDay: VenueGroup[];
    venueGuide: { name: string; address: string | null }[];
    liveMatches: LiveMatch[];
    hasAthletics: boolean;
};

export default function PublicMeet({
    meet,
    announcements,
    days,
    selectedDay,
    venuesForDay,
    venueGuide,
    liveMatches,
    hasAthletics,
}: Props) {
    return (
        <>
            <Head title={meet.name} />
            <div className="flex flex-col gap-6">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {meet.name}
                        </h1>
                        <Badge variant="secondary">{meet.status_label}</Badge>
                    </div>
                    <p className="mt-1 text-sm text-muted-foreground">
                        SY {meet.school_year} · {meet.starts_at} –{' '}
                        {meet.ends_at}
                        {meet.venue && ` · ${meet.venue}`}
                    </p>
                </div>

                <PublicMeetNav meetId={meet.id} active="schedule" />

                {hasAthletics && (
                    <Button
                        variant="outline"
                        size="sm"
                        className="w-fit"
                        asChild
                    >
                        <Link href={publicAthletics(meet.id)}>
                            <Timer aria-hidden="true" />
                            Athletics schedule and results
                        </Link>
                    </Button>
                )}

                <PublicAnnouncements announcements={announcements} />

                <PublicLiveMatches meetId={meet.id} matches={liveMatches} />

                <section className="flex flex-col gap-3">
                    <Heading variant="small" title="Schedule" />

                    {days.length === 0 ? (
                        <EmptyState
                            icon={CalendarDays}
                            title="Schedule not yet available"
                            description="Event schedules will appear here once they are set."
                        />
                    ) : (
                        <>
                            <nav
                                aria-label="Select day"
                                className="flex gap-2 overflow-x-auto pb-1"
                            >
                                {days.map((day) => (
                                    <Button
                                        key={day.value}
                                        variant={
                                            day.value === selectedDay
                                                ? 'default'
                                                : 'outline'
                                        }
                                        size="sm"
                                        className="shrink-0"
                                        aria-current={
                                            day.value === selectedDay
                                                ? 'date'
                                                : undefined
                                        }
                                        asChild
                                    >
                                        <Link
                                            href={
                                                publicMeet(meet.id).url +
                                                `?date=${day.value}`
                                            }
                                            preserveScroll
                                        >
                                            {day.label}
                                        </Link>
                                    </Button>
                                ))}
                            </nav>

                            {/* `key={selectedDay}` remounts this group on
                                every day switch, replaying
                                `animate-card-in` (WP-08.5-06's "tab
                                transition" — this app's day/section
                                switchers are real Inertia visits to the
                                same page component, which React does not
                                remount on its own for a prop-only
                                change). */}
                            <div
                                key={selectedDay}
                                className="flex animate-card-in flex-col gap-4 sm:gap-6"
                            >
                                {venuesForDay.map((group) => (
                                    <section
                                        key={group.venue}
                                        className="rounded-xl border transition-[transform,box-shadow] duration-(--duration-base) ease-premium hover:-translate-y-0.5 hover:shadow-md"
                                    >
                                        {/* Same card shape `results.tsx`
                                            already uses per event (border-b
                                            header, bare `overflow-x-auto`
                                            table body — the ancestor
                                            already supplies the border,
                                            per the audited convention in
                                            `docs/ui-ux/shared-components.md`). */}
                                        <div className="border-b p-4">
                                            <h3 className="flex items-center gap-1.5 font-medium">
                                                <MapPin
                                                    aria-hidden="true"
                                                    className="size-4 shrink-0 text-muted-foreground"
                                                />
                                                {group.venue}
                                            </h3>
                                        </div>
                                        <div className="overflow-x-auto">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead className="w-28">
                                                            Time
                                                        </TableHead>
                                                        <TableHead>
                                                            Event
                                                        </TableHead>
                                                        <TableHead>
                                                            Note
                                                        </TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {group.slots.map((slot) => (
                                                        <TableRow key={slot.id}>
                                                            <TableCell className="font-medium whitespace-nowrap">
                                                                {slot.starts_at}
                                                                –{slot.ends_at}
                                                            </TableCell>
                                                            <TableCell>
                                                                {slot.event}
                                                            </TableCell>
                                                            <TableCell className="text-muted-foreground">
                                                                {slot.note ??
                                                                    '—'}
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    </section>
                                ))}
                            </div>
                        </>
                    )}
                </section>

                {venueGuide.length > 0 && (
                    <section className="flex flex-col gap-3">
                        <Heading variant="small" title="Venues" />
                        <ul className="grid gap-3 sm:grid-cols-2 sm:gap-4">
                            {venueGuide.map((venue) => (
                                <li
                                    key={venue.name}
                                    className="flex items-start gap-2 rounded-xl border p-4 text-sm"
                                >
                                    <MapPin
                                        aria-hidden="true"
                                        className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                    />
                                    <span>
                                        <span className="font-medium">
                                            {venue.name}
                                        </span>
                                        {venue.address && (
                                            <span className="block text-muted-foreground">
                                                {venue.address}
                                            </span>
                                        )}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </>
    );
}
