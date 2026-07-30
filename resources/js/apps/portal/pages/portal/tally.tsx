import { Head, router } from '@inertiajs/react';
import { tally as publicTally } from '@/routes/public';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalMedalTotalsRow } from '@/apps/portal/components/medal-totals';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import { PortalSelect } from '@/apps/portal/components/select';
import { PortalStandingsTable } from '@/apps/portal/components/standings-table';
import type {
    PortalAgeDivisionOption,
    PortalMedalTotals,
    PortalMeetSummary,
    PortalSchoolStandingRow,
    PortalSportMedals,
    PortalSportOption,
    PortalStandingRow,
} from '@/apps/portal/types';

type Props = {
    meet: PortalMeetSummary;
    schools: PortalSchoolStandingRow[];
    districts: PortalStandingRow[];
    totals: PortalMedalTotals;
    topByPoints: PortalStandingRow[];
    bySport: PortalSportMedals[];
    recentMedals: PortalMedalTotals;
    filters: { sport_id: number | null; age_division: string | null };
    sportOptions: PortalSportOption[];
    ageDivisionOptions: PortalAgeDivisionOption[];
    generatedAt: string;
};

export default function PortalTally({ meet, schools, districts, totals, bySport, filters, sportOptions, ageDivisionOptions, generatedAt }: Props) {
    const updateFilters = (next: Partial<{ sport_id: string; age_division: string }>) => {
        router.get(
            publicTally(meet.id).url,
            {
                sport_id: next.sport_id ?? (filters.sport_id ? String(filters.sport_id) : ''),
                age_division: next.age_division ?? filters.age_division ?? '',
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={`Medal Tally — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PortalHero eyebrow="Medal tally" title={meet.name} description={`Standings derived from validated results only. Generated ${generatedAt}.`} />

                <PortalMedalTotalsRow totals={totals} />

                <PortalSectionHeader
                    title="Overall standings"
                    action={
                        <div className="flex gap-2">
                            <PortalSelect
                                value={filters.sport_id ?? ''}
                                placeholder="All sports"
                                options={sportOptions.map((sport) => ({ value: String(sport.id), label: sport.label }))}
                                onChange={(event) => updateFilters({ sport_id: event.target.value })}
                            />
                            <PortalSelect
                                value={filters.age_division ?? ''}
                                placeholder="All divisions"
                                options={ageDivisionOptions.map((division) => ({ value: division.id, label: division.label }))}
                                onChange={(event) => updateFilters({ age_division: event.target.value })}
                            />
                        </div>
                    }
                />
                <PortalStandingsTable
                    nameLabel="District"
                    rows={districts.map((row) => ({ label: row.district, gold: row.gold, silver: row.silver, bronze: row.bronze, total: row.total }))}
                />

                <PortalSectionHeader title="School standings" />
                <PortalStandingsTable
                    nameLabel="School"
                    rows={schools.map((row) => ({ label: row.school, gold: row.gold, silver: row.silver, bronze: row.bronze, total: row.total }))}
                />

                <PortalSectionHeader title="Medals by sport" />
                <PortalStandingsTable
                    nameLabel="Sport"
                    rows={bySport.map((row) => ({ label: row.sport, gold: row.gold, silver: row.silver, bronze: row.bronze, total: row.total }))}
                />
            </div>
        </>
    );
}
