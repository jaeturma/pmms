import { Head } from '@inertiajs/react';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import { PortalStandingsTable } from '@/apps/portal/components/standings-table';
import type { PortalMeetSummary, PortalStandingRow } from '@/apps/portal/types';

type Props = {
    meet: PortalMeetSummary;
    districts: PortalStandingRow[];
    generatedAt: string;
};

export default function PortalStandings({ meet, districts, generatedAt }: Props) {
    return (
        <>
            <Head title={`Standings — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PortalHero title="Standings" description={`Full district ranking, derived from validated results only. Generated ${generatedAt}.`} />

                <PortalSectionHeader title="District ranking" />
                <PortalStandingsTable
                    nameLabel="District"
                    emphasized
                    rows={districts.map((row) => ({
                        label: row.district,
                        logoUrl: row.logo_url,
                        teamLogoUrl: row.team_logo_url,
                        slug: row.slug,
                        gold: row.gold,
                        silver: row.silver,
                        bronze: row.bronze,
                        total: row.total,
                        points: row.points,
                    }))}
                />
            </div>
        </>
    );
}
