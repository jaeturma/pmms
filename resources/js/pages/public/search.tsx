import { Head, Link, router } from '@inertiajs/react';
import {
    Award,
    Dumbbell,
    Megaphone,
    School as SchoolIcon,
    Search as SearchIcon,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { PublicPageHero } from '@/components/public-page-hero';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    news as publicNews,
    results as publicResults,
    search as publicSearch,
} from '@/routes/public';

type SchoolRow = { id: number; name: string };
type SportRow = { id: number; name: string; event_count: number };
type AnnouncementRow = {
    id: number;
    title: string;
    published_at: string | null;
};
type PlacementRow = {
    event: string;
    sport_id: number;
    rank: number;
    athlete: string;
    school: string;
    delegation: string;
    mark: string | null;
    is_tie: boolean;
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
    query: string;
    schools: SchoolRow[];
    sports: SportRow[];
    announcements: AnnouncementRow[];
    placements: PlacementRow[];
};

export default function PublicSearch({
    meet,
    query,
    schools,
    sports,
    announcements,
    placements,
}: Props) {
    const [term, setTerm] = useState(query);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        router.get(
            publicSearch(meet.id).url,
            term.trim() === '' ? {} : { q: term.trim() },
            { preserveState: true, preserveScroll: true },
        );
    };

    const hasResults =
        schools.length > 0 ||
        sports.length > 0 ||
        announcements.length > 0 ||
        placements.length > 0;

    return (
        <>
            <Head title={`Search — ${meet.name}`} />
            <div className="flex flex-col gap-6 sm:gap-8">
                <PublicPageHero
                    title="Search"
                    description="Find schools, sports, announcements, and results in this meet."
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

                <form
                    onSubmit={submit}
                    className="flex flex-wrap gap-2"
                    role="search"
                >
                    <Input
                        type="search"
                        value={term}
                        onChange={(event) => setTerm(event.target.value)}
                        placeholder="Search schools, sports, announcements, results…"
                        aria-label="Search this meet"
                        className="max-w-sm"
                    />
                    <Button type="submit">
                        <SearchIcon aria-hidden="true" />
                        Search
                    </Button>
                </form>

                {query === '' ? (
                    <EmptyState
                        icon={SearchIcon}
                        title="Search this meet"
                        description="Type a name above to search schools, sports, announcements, and results."
                    />
                ) : !hasResults ? (
                    <EmptyState
                        icon={SearchIcon}
                        title={`No matches for "${query}"`}
                        description="Try a different name or a shorter search term."
                    />
                ) : (
                    <div className="flex animate-card-in flex-col gap-6 sm:gap-8">
                        {schools.length > 0 && (
                            <section className="space-y-3">
                                <Heading
                                    variant="small"
                                    title="Schools"
                                    description="Schools taking part in this meet."
                                />
                                <div className="flex flex-wrap gap-2">
                                    {schools.map((school) => (
                                        <Badge
                                            key={school.id}
                                            variant="outline"
                                            className="gap-1.5 py-1.5 text-sm font-normal"
                                        >
                                            <SchoolIcon
                                                aria-hidden="true"
                                                className="size-3.5"
                                            />
                                            {school.name}
                                        </Badge>
                                    ))}
                                </div>
                            </section>
                        )}

                        {sports.length > 0 && (
                            <section className="space-y-3">
                                <Heading variant="small" title="Sports" />
                                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    {sports.map((sport) => (
                                        <Link
                                            key={sport.id}
                                            href={`${publicResults(meet.id).url}?sport_id=${sport.id}`}
                                            className="flex items-center gap-3 rounded-xl border p-4 transition hover:bg-accent"
                                        >
                                            <Dumbbell
                                                aria-hidden="true"
                                                className="size-5 shrink-0 text-primary"
                                            />
                                            <div>
                                                <p className="font-medium">
                                                    {sport.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {sport.event_count}{' '}
                                                    {sport.event_count === 1
                                                        ? 'event'
                                                        : 'events'}
                                                </p>
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            </section>
                        )}

                        {announcements.length > 0 && (
                            <section className="space-y-3">
                                <Heading
                                    variant="small"
                                    title="Announcements"
                                />
                                <div className="flex flex-col gap-2">
                                    {announcements.map((announcement) => (
                                        <Link
                                            key={announcement.id}
                                            href={publicNews(meet.id)}
                                            className="flex items-start gap-3 rounded-xl border p-4 transition hover:bg-accent"
                                        >
                                            <Megaphone
                                                aria-hidden="true"
                                                className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                            />
                                            <div>
                                                <p className="font-medium">
                                                    {announcement.title}
                                                </p>
                                                {announcement.published_at && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {
                                                            announcement.published_at
                                                        }
                                                    </p>
                                                )}
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            </section>
                        )}

                        {placements.length > 0 && (
                            <section className="space-y-3">
                                <Heading
                                    variant="small"
                                    title="Results"
                                    description="Validated placements only."
                                />
                                <div className="overflow-x-auto rounded-xl border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-12">
                                                    Rank
                                                </TableHead>
                                                <TableHead>Athlete</TableHead>
                                                <TableHead>School</TableHead>
                                                <TableHead>Event</TableHead>
                                                <TableHead className="text-right">
                                                    Mark
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {placements.map(
                                                (placement, index) => (
                                                    <TableRow
                                                        key={`${placement.athlete}-${placement.event}-${index}`}
                                                    >
                                                        <TableCell>
                                                            {placement.rank}
                                                            {placement.is_tie
                                                                ? ' T'
                                                                : ''}
                                                        </TableCell>
                                                        <TableCell className="font-medium">
                                                            {placement.athlete}
                                                        </TableCell>
                                                        <TableCell>
                                                            {placement.school}
                                                        </TableCell>
                                                        <TableCell>
                                                            <Link
                                                                href={`${publicResults(meet.id).url}?sport_id=${placement.sport_id}`}
                                                                className="underline-offset-2 hover:underline"
                                                            >
                                                                <Award
                                                                    aria-hidden="true"
                                                                    className="mr-1 inline size-3.5 text-muted-foreground"
                                                                />
                                                                {
                                                                    placement.event
                                                                }
                                                            </Link>
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            {placement.mark ??
                                                                '—'}
                                                        </TableCell>
                                                    </TableRow>
                                                ),
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </section>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}
