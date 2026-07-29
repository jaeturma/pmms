import { Head, Link, router, usePage, usePoll } from '@inertiajs/react';
import {
    Award,
    Crown,
    Info,
    ListOrdered,
    Medal,
    Monitor,
    WifiOff,
} from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { MedalDistributionCard } from '@/components/medal-distribution-card';
import { MedalCells, MedalHeader } from '@/components/medal-table-parts';
import { MedalsBySportCard } from '@/components/medals-by-sport-card';
import type { SportRow } from '@/components/medals-by-sport-card';
import { PublicLoadingSkeleton } from '@/components/public-loading-skeleton';
import { PublicMeetNav } from '@/components/public-meet-nav';
import { PublicPageHero } from '@/components/public-page-hero';
import { RankBadge } from '@/components/rank-badge';
import { SportsMedalStrip } from '@/components/sports-medal-strip';
import { StatCard } from '@/components/stat-card';
import { TopByPointsCard } from '@/components/top-by-points-card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useKioskMode } from '@/hooks/use-kiosk-mode';
import { recentDescription } from '@/lib/utils';
import {
    rankings as publicRankings,
    tally as publicTally,
} from '@/routes/public';

/** Collapsed ranking table row count (WP-08-09) — a phone shouldn't get
 * the full standings dumped on it by default; "View full ranking"
 * expands to all rows, same table either way. */
const RANKING_PREVIEW_COUNT = 8;

type SchoolRow = {
    position: number;
    school: string;
    district: string;
    gold: number;
    silver: number;
    bronze: number;
    total: number;
};

type DistrictRow = {
    position: number;
    district: string;
    gold: number;
    silver: number;
    bronze: number;
    total: number;
    points: number;
};

type Totals = { gold: number; silver: number; bronze: number; total: number };

type Option = { id: number; label: string };
type StringOption = { id: string; label: string };

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
    schools: SchoolRow[];
    districts: DistrictRow[];
    totals: Totals;
    topByPoints: DistrictRow[];
    bySport: SportRow[];
    recentMedals: Totals;
    filters: { sport_id: number | null; age_division: string | null };
    sportOptions: Option[];
    ageDivisionOptions: StringOption[];
    generatedAt: string;
};

export default function PublicTally({
    meet,
    schools,
    districts,
    totals,
    topByPoints,
    bySport,
    recentMedals,
    filters,
    sportOptions,
    ageDivisionOptions,
    generatedAt,
}: Props) {
    const { division } = usePage().props;
    const areaLabel = division.areaLabel;
    const kiosk = useKioskMode();
    const [showAllRankings, setShowAllRankings] = useState(false);
    const [loading, setLoading] = useState(false);
    const [kioskRefreshFailures, setKioskRefreshFailures] = useState(0);
    const visibleDistricts = showAllRankings
        ? districts
        : districts.slice(0, RANKING_PREVIEW_COUNT);

    // Kiosk-only auto-refresh (WP-08.5-07) — this page has no live
    // session to poll like the scoreboard; a genuine TV/LED-wall display
    // still needs fresh standings without anyone touching it. A
    // successful poll re-fetches every prop, including `generatedAt`
    // (server-computed), so that alone is the "last refreshed" readout —
    // no separate client-side timestamp needed, only failure tracking
    // for the connection-status banner. `kiosk` is effectively fixed for
    // this component's whole lifetime (it only changes across a fresh
    // page load with a different URL), so capturing it once in
    // `autoStart` is enough — no separate start/stop effect needed.
    usePoll(
        30000,
        {
            onSuccess: () => setKioskRefreshFailures(0),
            onError: () => setKioskRefreshFailures((n) => n + 1),
        },
        { autoStart: kiosk },
    );

    const applyFilters = (overrides: {
        sport_id?: string;
        age_division?: string;
    }) => {
        const params: Record<string, string> = {};

        const sportId = overrides.sport_id ?? String(filters.sport_id ?? '');
        const ageDivision =
            overrides.age_division ?? filters.age_division ?? '';

        if (sportId && sportId !== 'all') {
            params.sport_id = sportId;
        }

        if (ageDivision && ageDivision !== 'all') {
            params.age_division = ageDivision;
        }

        router.get(publicTally(meet.id).url, params, {
            preserveState: true,
            preserveScroll: true,
            onStart: () => setLoading(true),
            onFinish: () => setLoading(false),
        });
    };

    const kioskUrl = () => {
        const params = new URLSearchParams(window.location.search);
        params.set('kiosk', '1');

        return `${window.location.pathname}?${params.toString()}`;
    };

    // TV/projector/LED-wall/kiosk rendering (WP-08.5-07, `?kiosk=1`): no
    // nav, filters, or secondary sections (medals-by-sport, school
    // standings) — a big screen should show one thing at a glance, the
    // official ranking, at large text and full width. The 30s poll
    // above supplies "auto-refresh"; `kioskRefreshFailures` supplies
    // "connection status".
    if (kiosk) {
        return (
            <>
                <Head title={`Medal tally — ${meet.name}`} />
                <div className="flex min-h-screen flex-col gap-6 p-6 sm:p-10 lg:p-16">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p className="text-base font-medium text-muted-foreground">
                                {meet.name}
                            </p>
                            <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                Medal Tally & Rankings
                            </h1>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            As of {generatedAt}
                        </p>
                    </div>

                    {kioskRefreshFailures >= 2 && (
                        // Message text uses the inherited default
                        // `foreground` color, not `text-warning` (WP-
                        // 08.5-09) — see `live-score-display.tsx`'s
                        // identical fix for the measured contrast
                        // numbers; only the icon stays warning-colored.
                        <div
                            role="status"
                            className="flex items-center justify-center gap-2 rounded-md bg-warning/10 px-3 py-2 text-sm"
                        >
                            <WifiOff
                                aria-hidden="true"
                                className="size-4 text-warning"
                            />
                            Connection lost — retrying automatically. Standings
                            shown may be out of date.
                        </div>
                    )}

                    {districts.length === 0 ? (
                        <div className="flex flex-1 items-center justify-center">
                            <EmptyState
                                icon={Crown}
                                title="No medals yet"
                                description="Standings appear as soon as results are validated."
                            />
                        </div>
                    ) : (
                        <div className="overflow-x-auto rounded-2xl border-2">
                            {/* `text-lg` on `Table` itself (its own base
                                is `text-sm`) — every cell below,
                                including the shared `MedalCells`/
                                `MedalHeader`, inherits the larger size
                                rather than needing its own override. */}
                            <Table className="text-lg">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-20">
                                            Rank
                                        </TableHead>
                                        <TableHead>{areaLabel}</TableHead>
                                        <MedalHeader />
                                        <TableHead className="w-28 text-center">
                                            Points
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {districts.map((row) => (
                                        <TableRow key={row.district}>
                                            <TableCell>
                                                <RankBadge
                                                    position={row.position}
                                                />
                                            </TableCell>
                                            <TableCell className="font-semibold">
                                                {row.district}
                                            </TableCell>
                                            <MedalCells row={row} />
                                            <TableCell className="text-center font-semibold">
                                                {row.points}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </div>
            </>
        );
    }

    return (
        <>
            <Head title={`Medal tally — ${meet.name}`} />
            <div className="flex flex-col gap-6 sm:gap-8">
                <PublicPageHero
                    title="Medal Tally & Rankings"
                    description="Real-time overall medal count and ranking of all municipalities."
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

                <PublicMeetNav meetId={meet.id} active="tally" />

                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="flex flex-wrap gap-2">
                        {sportOptions.length > 0 && (
                            <Select
                                value={String(filters.sport_id ?? 'all')}
                                onValueChange={(value) =>
                                    applyFilters({ sport_id: value })
                                }
                            >
                                <SelectTrigger
                                    className="w-56"
                                    aria-label="Filter by sport"
                                >
                                    <SelectValue placeholder="All sports" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All sports
                                    </SelectItem>
                                    {sportOptions.map((option) => (
                                        <SelectItem
                                            key={option.id}
                                            value={String(option.id)}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}
                        <Select
                            value={filters.age_division ?? 'all'}
                            onValueChange={(value) =>
                                applyFilters({ age_division: value })
                            }
                        >
                            <SelectTrigger
                                className="w-48"
                                aria-label="Filter by division"
                            >
                                <SelectValue placeholder="All divisions" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All divisions
                                </SelectItem>
                                {ageDivisionOptions.map((option) => (
                                    <SelectItem
                                        key={option.id}
                                        value={option.id}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={publicRankings(meet.id)}>
                                <ListOrdered aria-hidden="true" />
                                Full rankings
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={kioskUrl()}>
                                <Monitor aria-hidden="true" />
                                Kiosk / TV mode
                            </Link>
                        </Button>
                    </div>
                </div>

                {loading ? (
                    <PublicLoadingSkeleton rows={6} />
                ) : districts.length === 0 ? (
                    <EmptyState
                        icon={Crown}
                        title="No medals yet"
                        description="Standings appear as soon as results are validated."
                    />
                ) : (
                    <div className="flex animate-card-in flex-col gap-6 sm:gap-8">
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4 sm:gap-5">
                            <StatCard
                                label="Total gold"
                                value={totals.gold}
                                icon={Medal}
                                tone="gold"
                                description={recentDescription(
                                    recentMedals.gold,
                                )}
                            />
                            <StatCard
                                label="Total silver"
                                value={totals.silver}
                                icon={Medal}
                                tone="silver"
                                description={recentDescription(
                                    recentMedals.silver,
                                )}
                            />
                            <StatCard
                                label="Total bronze"
                                value={totals.bronze}
                                icon={Medal}
                                tone="bronze"
                                description={recentDescription(
                                    recentMedals.bronze,
                                )}
                            />
                            <StatCard
                                label="Total medals"
                                value={totals.total}
                                icon={Award}
                                tone="success"
                                description={recentDescription(
                                    recentMedals.total,
                                )}
                            />
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-6">
                            <section className="space-y-3 md:col-span-2">
                                <Heading
                                    title={`Overall ranking by ${areaLabel.toLowerCase()}`}
                                    description="Official standings for this meet."
                                />
                                <div className="overflow-x-auto rounded-xl border">
                                    {/* `text-base` (one step up from the
                                        default `text-sm`) — same "size the
                                        table, every cell inherits it"
                                        technique the kiosk view above
                                        already uses at `text-lg`, just a
                                        more modest step for a normal
                                        browser width (WP-10-06's "large,
                                        spacious ranking presentation"). */}
                                    <Table className="text-base">
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-12">
                                                    Rank
                                                </TableHead>
                                                <TableHead>
                                                    {areaLabel}
                                                </TableHead>
                                                <MedalHeader />
                                                <TableHead className="hidden w-20 text-center sm:table-cell">
                                                    Points
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {visibleDistricts.map((row) => (
                                                <TableRow key={row.district}>
                                                    <TableCell>
                                                        <RankBadge
                                                            position={
                                                                row.position
                                                            }
                                                        />
                                                    </TableCell>
                                                    <TableCell className="font-medium">
                                                        {row.district}
                                                    </TableCell>
                                                    <MedalCells row={row} />
                                                    <TableCell className="hidden text-center font-medium sm:table-cell">
                                                        {row.points}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                                {!showAllRankings &&
                                    districts.length >
                                        RANKING_PREVIEW_COUNT && (
                                        <Button
                                            variant="link"
                                            className="h-auto p-0"
                                            onClick={() =>
                                                setShowAllRankings(true)
                                            }
                                        >
                                            View full ranking (
                                            {districts.length} total) →
                                        </Button>
                                    )}
                                <p className="text-xs text-muted-foreground">
                                    Rank is based on: Gold, then Silver, then
                                    Bronze. Points (Gold=3, Silver=2, Bronze=1)
                                    are shown for reference and power the "Top
                                    by points" list — they do not change the
                                    official rank order above.
                                </p>
                            </section>

                            <div className="space-y-4">
                                <MedalDistributionCard totals={totals} />
                            </div>
                        </div>

                        <section className="space-y-3">
                            <Heading variant="small" title="Medals by sport" />
                            <SportsMedalStrip
                                rows={bySport}
                                moreHref="#medals-by-sport"
                            />
                        </section>

                        <div
                            id="medals-by-sport"
                            className="grid scroll-mt-4 grid-cols-1 gap-4 md:grid-cols-2 md:gap-6"
                        >
                            <TopByPointsCard
                                rows={topByPoints}
                                areaLabel={areaLabel}
                            />
                            <MedalsBySportCard rows={bySport} />
                        </div>

                        <Alert>
                            <Info aria-hidden="true" />
                            <AlertDescription>
                                As of {generatedAt}. Medal tally is updated in
                                real time as results are validated. Ties share
                                medals; corrections to validated results update
                                the tally automatically.
                            </AlertDescription>
                        </Alert>

                        <section className="space-y-3">
                            <Heading
                                variant="small"
                                title="School standings"
                                description="Reference only — shows which school each medal came from."
                            />
                            <div className="overflow-x-auto rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-12">
                                                #
                                            </TableHead>
                                            <TableHead>School</TableHead>
                                            <TableHead>District</TableHead>
                                            <MedalHeader />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {schools.map((row) => (
                                            <TableRow key={row.school}>
                                                <TableCell>
                                                    {row.position}
                                                </TableCell>
                                                <TableCell className="font-medium">
                                                    {row.school}
                                                </TableCell>
                                                <TableCell>
                                                    {row.district}
                                                </TableCell>
                                                <MedalCells row={row} />
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </section>
                    </div>
                )}
            </div>
        </>
    );
}
