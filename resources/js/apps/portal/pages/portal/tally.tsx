import { Head, router } from '@inertiajs/react';
import { Trophy } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalMedalBoard } from '@/apps/portal/components/medal-board';
import { PortalMedalTotalsRow } from '@/apps/portal/components/medal-totals';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import { PortalSelect } from '@/apps/portal/components/select';
import { PortalStandingsTable } from '@/apps/portal/components/standings-table';
import { PortalTabs } from '@/apps/portal/components/tabs';
import { PortalTopMedalistTable } from '@/apps/portal/components/top-medalist-table';
import { usePortalPageVisible } from '@/apps/portal/lib/use-page-visible';
import { cn } from '@/apps/portal/lib/utils';
import type {
    PortalMeetSummary,
    PortalSportOption,
    PortalTallyCategories,
    PortalTallyCategory,
} from '@/apps/portal/types';
import { tally as publicTally } from '@/routes/public';

// The medal tally moves slowly — a 20s background refresh keeps it live
// without hammering the endpoint. Paused entirely while the tab is
// hidden (see `usePortalPageVisible`). One interval, torn down on
// unmount — it can never stack.
const TALLY_POLL_INTERVAL_MS = 20000;

const TABS: {
    value: PortalTallyCategory;
    label: string;
    mobileLabel: string;
}[] = [
    { value: 'overall', label: 'Overall', mobileLabel: 'Overall' },
    { value: 'elementary', label: 'Elementary', mobileLabel: 'Elem' },
    { value: 'secondary', label: 'Secondary', mobileLabel: 'Sec' },
    { value: 'paragames', label: 'Paragames', mobileLabel: 'Para' },
];

const CAPTIONS: Record<PortalTallyCategory, string> = {
    overall:
        'Elementary and Secondary combined. Paragames and Kickboxing are not included.',
    elementary:
        'Elementary-division events only. Excludes Paragames and Kickboxing.',
    secondary:
        'Secondary-division events only. Excludes Paragames and Kickboxing.',
    paragames:
        'Paragames events only — a separate official tally, not added into Overall.',
};

const EMPTY_COPY: Record<PortalTallyCategory, string> = {
    overall: 'No official results have been posted yet.',
    elementary: 'No official Elementary results have been posted yet.',
    secondary: 'No official Secondary results have been posted yet.',
    paragames: 'No official Paragames results have been posted yet.',
};

function isCategory(value: string | null): value is PortalTallyCategory {
    return (
        value === 'overall' ||
        value === 'elementary' ||
        value === 'secondary' ||
        value === 'paragames'
    );
}

type Props = {
    meet: PortalMeetSummary;
    categories: PortalTallyCategories;
    filters: { sport_id: number | null };
    sportOptions: PortalSportOption[];
    generatedAt: string;
    medalTallyOfficial: boolean;
};

export default function PortalTally({
    meet,
    categories,
    filters,
    sportOptions,
    generatedAt,
    medalTallyOfficial,
}: Props) {
    // Category is a pure client-side view switch — no Inertia visit, so
    // the 3s live animations are never interrupted by a navigation and
    // switching tabs is instant. It is mirrored into the URL query (via
    // history.replaceState, not a visit) so a refresh, a shared link or a
    // TV deep-link keeps the chosen board.
    const [activeTab, setActiveTab] = useState<PortalTallyCategory>(() => {
        if (typeof window === 'undefined') {
            return 'overall';
        }

        const fromUrl = new URLSearchParams(window.location.search).get(
            'category',
        );

        return isCategory(fromUrl) ? fromUrl : 'overall';
    });

    const selectTab = useCallback((tab: PortalTallyCategory) => {
        setActiveTab(tab);

        if (typeof window !== 'undefined') {
            const url = new URL(window.location.href);

            if (tab === 'overall') {
                url.searchParams.delete('category');
            } else {
                url.searchParams.set('category', tab);
            }

            window.history.replaceState(window.history.state, '', url);
        }
    }, []);

    const pageVisible = usePortalPageVisible();
    useEffect(() => {
        if (!pageVisible) {
            return;
        }

        const interval = setInterval(() => {
            router.reload({
                only: ['categories', 'generatedAt'],
                showProgress: false,
            });
        }, TALLY_POLL_INTERVAL_MS);

        return () => clearInterval(interval);
    }, [pageVisible]);

    // A brief "updated just now" state on the live pill whenever the
    // polled data actually differs — not on every poll.
    const [justUpdated, setJustUpdated] = useState(false);
    const signature = useMemo(
        () =>
            (Object.keys(categories) as PortalTallyCategory[])
                .map((key) => {
                    const t = categories[key].totals;

                    return `${key}:${t.gold}-${t.silver}-${t.bronze}`;
                })
                .join('|'),
        [categories],
    );
    const previousSignature = useRef(signature);
    useEffect(() => {
        if (previousSignature.current === signature) {
            return;
        }

        previousSignature.current = signature;
        setJustUpdated(true);
        const timer = setTimeout(() => setJustUpdated(false), 3000);

        return () => clearTimeout(timer);
    }, [signature]);

    const data = categories[activeTab];

    const updateSport = (sportId: string) => {
        router.get(
            publicTally(meet.id).url,
            {
                sport_id: sportId || undefined,
                category: activeTab === 'overall' ? undefined : activeTab,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const sportFilter = (
        <PortalSelect
            value={filters.sport_id ?? ''}
            placeholder="All sports"
            options={sportOptions.map((sport) => ({
                value: String(sport.id),
                label: sport.label,
            }))}
            onChange={(event) => updateSport(event.target.value)}
        />
    );

    return (
        <>
            <Head title={`Official Medal Tally — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PortalHero
                    eyebrow={meet.name}
                    title="Official Medal Tally"
                    description={
                        medalTallyOfficial
                            ? 'Official standings, derived from validated results only.'
                            : 'Unofficial running standings, derived from validated results only.'
                    }
                    meta={
                        <>
                            <span
                                className={cn(
                                    'inline-flex items-center gap-1.5 rounded-full bg-[var(--portal-live)] px-2.5 py-1 text-xs font-bold tracking-wide text-[var(--portal-live-foreground)] uppercase',
                                    justUpdated && 'portal-tally-live--updated',
                                )}
                            >
                                <span
                                    aria-hidden="true"
                                    className="portal-live-dot size-2 rounded-full bg-current"
                                />
                                {justUpdated ? 'Updated' : 'Live'}
                            </span>
                            <span>Last updated {generatedAt}</span>
                        </>
                    }
                />

                <div className="flex flex-col items-center gap-2">
                    <PortalTabs
                        tabs={TABS}
                        value={activeTab}
                        onChange={(value) =>
                            selectTab(value as PortalTallyCategory)
                        }
                    />
                    <p className="max-w-2xl text-center text-sm text-[var(--portal-muted-foreground)]">
                        {CAPTIONS[activeTab]}
                    </p>
                </div>

                <PortalSectionHeader
                    title={`${TABS.find((t) => t.value === activeTab)?.label} Standings`}
                    description={
                        data.hasResults
                            ? `${data.totals.total} medal${data.totals.total === 1 ? '' : 's'} awarded across ${data.districts.filter((d) => d.total > 0).length} delegation${data.districts.filter((d) => d.total > 0).length === 1 ? '' : 's'}.`
                            : undefined
                    }
                    action={sportFilter}
                />

                {data.hasResults ? (
                    // Remount per category so switching tabs is instant — a
                    // fresh mount carries no FLIP offsets, so rows never
                    // slide between two unrelated datasets. Live polling
                    // within a category still animates normally.
                    <div key={activeTab} className="flex flex-col gap-6">
                        <PortalMedalBoard rows={data.districts} animate />
                        <PortalMedalTotalsRow totals={data.totals} animate />
                    </div>
                ) : (
                    <PortalEmptyState
                        icon={Trophy}
                        tone="ink"
                        title={EMPTY_COPY[activeTab]}
                        description={
                            filters.sport_id
                                ? 'Try clearing the sport filter, or check back once results are validated.'
                                : 'Standings appear here the moment a result is validated.'
                        }
                    />
                )}

                {data.hasResults && (
                    <details
                        key={`stats-${activeTab}`}
                        className="portal-more-stats rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)]"
                    >
                        <summary className="cursor-pointer px-4 py-3 text-sm font-semibold text-[var(--portal-fg)] select-none">
                            More statistics
                        </summary>
                        <div className="flex flex-col gap-6 border-t border-[var(--portal-border)] px-4 py-5">
                            <div className="flex flex-col gap-3">
                                <PortalSectionHeader title="School standings" />
                                <PortalStandingsTable
                                    nameLabel="School"
                                    showCrest={false}
                                    animate
                                    unavailableTitle="No school medals yet"
                                    unavailableDescription="School-level medals appear here once individual-event results are validated in this category."
                                    rows={data.schools.map((row) => ({
                                        key: `s-${row.district}-${row.municipality}-${row.school}`,
                                        label: row.school,
                                        gold: row.gold,
                                        silver: row.silver,
                                        bronze: row.bronze,
                                        total: row.total,
                                    }))}
                                />
                            </div>

                            <div className="flex flex-col gap-3">
                                <PortalSectionHeader
                                    title="Top medalist"
                                    description="Individual athletes ranked by gold, then silver, then bronze."
                                />
                                <PortalTopMedalistTable
                                    rows={data.topMedalists}
                                    animate
                                />
                            </div>

                            {data.bySport.length > 0 && (
                                <div className="flex flex-col gap-3">
                                    <PortalSectionHeader title="Medals by sport" />
                                    <PortalStandingsTable
                                        nameLabel="Sport"
                                        showCrest={false}
                                        animate
                                        rows={data.bySport.map((row) => ({
                                            key: `sport-${row.sport}`,
                                            label: row.sport,
                                            gold: row.gold,
                                            silver: row.silver,
                                            bronze: row.bronze,
                                            total: row.total,
                                        }))}
                                    />
                                </div>
                            )}
                        </div>
                    </details>
                )}
            </div>
        </>
    );
}
