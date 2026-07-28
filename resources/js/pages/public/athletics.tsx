import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Award,
    CalendarDays,
    Info,
    Medal,
    Timer,
} from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { PublicPageHero } from '@/components/public-page-hero';
import { StatCard } from '@/components/stat-card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    athletics as publicAthletics,
    meet as publicMeet,
} from '@/routes/public';

type Day = { value: string; label: string };

type Placement = {
    rank: number;
    athlete: string;
    school: string;
    mark: string | null;
};

type Slot = {
    id: number;
    starts_at: string;
    ends_at: string;
    event: string;
    venue: string;
    status: 'upcoming' | 'ongoing' | 'completed';
    top_placements: Placement[];
    official_as_of: string | null;
};

type MedalTotals = {
    gold: number;
    silver: number;
    bronze: number;
    total: number;
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
    days: Day[];
    selectedDay: string | null;
    medalTotals: MedalTotals;
    slots: Slot[];
};

const statusBadge: Record<
    Slot['status'],
    { label: string; variant: 'default' | 'secondary' | 'outline' }
> = {
    upcoming: { label: 'Upcoming', variant: 'outline' },
    ongoing: { label: 'Ongoing', variant: 'default' },
    completed: { label: 'Completed', variant: 'secondary' },
};

export default function PublicAthletics({
    meet,
    days,
    selectedDay,
    medalTotals,
    slots,
}: Props) {
    return (
        <>
            <Head title={`Athletics — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PublicPageHero
                    title="Athletics"
                    description="Track and field schedule and results for this meet."
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

                <Button variant="outline" size="sm" className="w-fit" asChild>
                    <Link href={publicMeet(meet.id)}>
                        <ArrowLeft aria-hidden="true" />
                        Back to schedule
                    </Link>
                </Button>

                <Alert>
                    <Info aria-hidden="true" />
                    <AlertDescription>
                        Live per-athlete race tracking isn't available for
                        Athletics yet. Standings below reflect officially
                        validated results only, the same as the Results page —
                        scheduled events show a real time and venue but no live
                        position or split times.
                    </AlertDescription>
                </Alert>

                <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <StatCard
                        label="Gold"
                        value={medalTotals.gold}
                        icon={Medal}
                        tone="gold"
                    />
                    <StatCard
                        label="Silver"
                        value={medalTotals.silver}
                        icon={Medal}
                        tone="silver"
                    />
                    <StatCard
                        label="Bronze"
                        value={medalTotals.bronze}
                        icon={Medal}
                        tone="bronze"
                    />
                    <StatCard
                        label="Total medals"
                        value={medalTotals.total}
                        icon={Award}
                        tone="success"
                    />
                </div>

                <section className="flex flex-col gap-3">
                    <Heading variant="small" title="Events" />

                    {days.length === 0 ? (
                        <EmptyState
                            icon={Timer}
                            title="No Athletics events scheduled"
                            description="Track and field events will appear here once scheduled."
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
                                                publicAthletics(meet.id).url +
                                                `?date=${day.value}`
                                            }
                                            preserveScroll
                                        >
                                            {day.label}
                                        </Link>
                                    </Button>
                                ))}
                            </nav>

                            {/* `key={selectedDay}` remounts this block on
                                every day switch, replaying
                                `animate-card-in` (WP-08.5-06's "tab
                                transition" — see the identical pattern
                                in `public/meet.tsx`). */}
                            <div key={selectedDay} className="animate-card-in">
                                {slots.length === 0 ? (
                                    <EmptyState
                                        icon={CalendarDays}
                                        title="No events on this day"
                                        description="Choose another day above."
                                    />
                                ) : (
                                    <ul className="flex flex-col gap-3">
                                        {slots.map((slot) => (
                                            <li
                                                key={slot.id}
                                                className="rounded-xl border p-4"
                                            >
                                                <div className="flex flex-wrap items-center justify-between gap-2">
                                                    <div>
                                                        <p className="font-medium">
                                                            {slot.event}
                                                        </p>
                                                        <p className="text-sm text-muted-foreground">
                                                            {slot.starts_at}–
                                                            {slot.ends_at} ·{' '}
                                                            {slot.venue}
                                                        </p>
                                                    </div>
                                                    <Badge
                                                        variant={
                                                            statusBadge[
                                                                slot.status
                                                            ].variant
                                                        }
                                                    >
                                                        {
                                                            statusBadge[
                                                                slot.status
                                                            ].label
                                                        }
                                                    </Badge>
                                                </div>

                                                {slot.top_placements.length >
                                                    0 && (
                                                    <div className="mt-3 border-t pt-3">
                                                        <p className="mb-1 text-xs text-muted-foreground">
                                                            Official results
                                                            {slot.official_as_of &&
                                                                ` — as of ${slot.official_as_of}`}
                                                        </p>
                                                        <ol className="space-y-1 text-sm">
                                                            {slot.top_placements.map(
                                                                (placement) => (
                                                                    <li
                                                                        key={
                                                                            placement.rank
                                                                        }
                                                                        className="flex items-center justify-between gap-2"
                                                                    >
                                                                        <span>
                                                                            {
                                                                                placement.rank
                                                                            }
                                                                            .{' '}
                                                                            {
                                                                                placement.athlete
                                                                            }{' '}
                                                                            <span className="text-muted-foreground">
                                                                                (
                                                                                {
                                                                                    placement.school
                                                                                }

                                                                                )
                                                                            </span>
                                                                        </span>
                                                                        {placement.mark && (
                                                                            <span className="font-medium tabular-nums">
                                                                                {
                                                                                    placement.mark
                                                                                }
                                                                            </span>
                                                                        )}
                                                                    </li>
                                                                ),
                                                            )}
                                                        </ol>
                                                    </div>
                                                )}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </>
                    )}
                </section>
            </div>
        </>
    );
}
