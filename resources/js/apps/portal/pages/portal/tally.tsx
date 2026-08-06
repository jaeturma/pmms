import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalMedalTotalsRow } from '@/apps/portal/components/medal-totals';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import { PortalSelect } from '@/apps/portal/components/select';
import { PortalStandingsTable } from '@/apps/portal/components/standings-table';
import { PortalTabs } from '@/apps/portal/components/tabs';
import { PortalTopMedalistTable } from '@/apps/portal/components/top-medalist-table';
import type {
    PortalMedalTotals,
    PortalMeetSummary,
    PortalSchoolStandingRow,
    PortalSportOption,
    PortalStandingRow,
    PortalTopMedalistRow,
} from '@/apps/portal/types';
import { tally as publicTally } from '@/routes/public';

type ViewTab = 'overall' | 'elementary' | 'secondary' | 'top-medalist';

const VIEW_TABS: { value: ViewTab; label: string }[] = [
    { value: 'overall', label: 'Overall' },
    { value: 'elementary', label: 'Elementary' },
    { value: 'secondary', label: 'Secondary' },
    { value: 'top-medalist', label: 'Top Medalist' },
];

type Props = {
    meet: PortalMeetSummary;
    schools: PortalSchoolStandingRow[];
    districts: PortalStandingRow[];
    totals: PortalMedalTotals;
    topByPoints: PortalStandingRow[];
    recentMedals: PortalMedalTotals;
    topMedalists: PortalTopMedalistRow[];
    filters: { sport_id: number | null; age_division: string | null };
    sportOptions: PortalSportOption[];
    generatedAt: string;
};

export default function PortalTally({
    meet,
    schools,
    districts,
    totals,
    topMedalists,
    filters,
    sportOptions,
    generatedAt,
}: Props) {
    // The age-division dimension (Overall/Elementary/Secondary) is a tab,
    // not a dropdown — "Top Medalist" is a fourth tab alongside it that
    // swaps in a different table entirely rather than filtering the
    // existing one, so it needs its own local view state on top of the
    // server-driven `age_division` filter.
    const [activeTab, setActiveTab] = useState<ViewTab>(
        filters.age_division === 'elementary' ||
            filters.age_division === 'secondary'
            ? filters.age_division
            : 'overall',
    );

    const updateFilters = (
        next: Partial<{ sport_id: string; age_division: string }>,
    ) => {
        router.get(
            publicTally(meet.id).url,
            {
                sport_id:
                    next.sport_id ??
                    (filters.sport_id ? String(filters.sport_id) : ''),
                age_division: next.age_division ?? filters.age_division ?? '',
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const selectTab = (tab: ViewTab) => {
        setActiveTab(tab);

        if (tab !== 'top-medalist') {
            updateFilters({ age_division: tab === 'overall' ? '' : tab });
        }
    };

    return (
        <>
            <Head title={`Medal Tally — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PortalHero
                    title="Medal Tally"
                    description={`Standings derived from validated results only. Generated ${generatedAt}.`}
                />

                <PortalTabs
                    tabs={VIEW_TABS}
                    value={activeTab}
                    onChange={(value) => selectTab(value as ViewTab)}
                />

                {activeTab === 'top-medalist' ? (
                    <>
                        <PortalSectionHeader
                            title="Top Medalist"
                            description="Individual athletes ranked by gold, then silver, then bronze."
                            action={
                                <PortalSelect
                                    value={filters.sport_id ?? ''}
                                    placeholder="All sports"
                                    options={sportOptions.map((sport) => ({
                                        value: String(sport.id),
                                        label: sport.label,
                                    }))}
                                    onChange={(event) =>
                                        updateFilters({
                                            sport_id: event.target.value,
                                        })
                                    }
                                />
                            }
                        />
                        <PortalTopMedalistTable rows={topMedalists} />
                    </>
                ) : (
                    <>
                        <PortalSectionHeader
                            title="Overall standings"
                            action={
                                <PortalSelect
                                    value={filters.sport_id ?? ''}
                                    placeholder="All sports"
                                    options={sportOptions.map((sport) => ({
                                        value: String(sport.id),
                                        label: sport.label,
                                    }))}
                                    onChange={(event) =>
                                        updateFilters({
                                            sport_id: event.target.value,
                                        })
                                    }
                                />
                            }
                        />
                        <PortalStandingsTable
                            nameLabel="District"
                            emphasized
                            rows={districts.map((row) => ({
                                label: row.district,
                                logoUrl: row.logo_url,
                                gold: row.gold,
                                silver: row.silver,
                                bronze: row.bronze,
                                total: row.total,
                            }))}
                        />

                        <PortalMedalTotalsRow totals={totals} />

                        <PortalSectionHeader title="School standings" />
                        <PortalStandingsTable
                            nameLabel="School"
                            showCrest={false}
                            rows={schools.map((row) => ({
                                label: row.school,
                                gold: row.gold,
                                silver: row.silver,
                                bronze: row.bronze,
                                total: row.total,
                            }))}
                        />
                    </>
                )}
            </div>
        </>
    );
}
