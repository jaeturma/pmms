import { Head, Link, router } from '@inertiajs/react';
import { Medal } from 'lucide-react';
import { useState } from 'react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalMedalTotalsRow } from '@/apps/portal/components/medal-totals';
import { PortalMedalWinnerCard } from '@/apps/portal/components/medal-winner-card';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import { PortalTabs } from '@/apps/portal/components/tabs';
import { PortalTeamHero } from '@/apps/portal/components/team-hero';
import type {
    PortalMeetSummary,
    PortalMedalWinner,
    PortalMunicipalityMedalBreakdown,
    PortalMunicipalityProfile,
} from '@/apps/portal/types';
import {
    playersCoaches as teamPlayersCoaches,
    show as teamShow,
} from '@/routes/public/teams';

/**
 * The public municipal team profile (WP: public municipal teams, Stage 4)
 * — hero with large + upper-right-seal logos, Total/Elementary/Secondary/
 * Paragames medal tabs, and the finalized medal-winners list.
 */
type Category = 'overall' | 'elementary' | 'secondary' | 'paragames';

const CATEGORY_TABS: { value: Category; label: string }[] = [
    { value: 'overall', label: 'Total' },
    { value: 'elementary', label: 'Elementary' },
    { value: 'secondary', label: 'Secondary' },
    { value: 'paragames', label: 'Paragames' },
];

type Props = {
    meet: PortalMeetSummary;
    team: PortalMunicipalityProfile;
    medalBreakdown: PortalMunicipalityMedalBreakdown;
    medalWinners: PortalMedalWinner[];
    filters: { category: string | null };
};

export default function PortalTeamDetail({
    meet,
    team,
    medalBreakdown,
    medalWinners,
    filters,
}: Props) {
    const [activeTab, setActiveTab] = useState<Category>(
        filters.category === 'elementary' ||
            filters.category === 'secondary' ||
            filters.category === 'paragames'
            ? filters.category
            : 'overall',
    );

    const selectTab = (tab: Category) => {
        setActiveTab(tab);
        router.get(
            teamShow(team.slug).url,
            { category: tab === 'overall' ? '' : tab },
            { preserveState: true, preserveScroll: true },
        );
    };

    const totals =
        medalBreakdown[activeTab === 'overall' ? 'total' : activeTab];

    return (
        <>
            <Head title={`${team.name} — Teams`} />
            <div className="flex flex-col gap-6">
                <PortalTeamHero meetName={meet.name} team={team} />

                <Link
                    href={teamPlayersCoaches(team.slug).url}
                    className="self-center text-lg font-semibold text-[var(--portal-accent)]"
                >
                    Players &amp; Coaches →
                </Link>

                <PortalTabs
                    tabs={CATEGORY_TABS}
                    value={activeTab}
                    onChange={(value) => selectTab(value as Category)}
                />

                <PortalMedalTotalsRow totals={totals} />

                <PortalSectionHeader title="Medal Winners" />

                {medalWinners.length === 0 ? (
                    <PortalEmptyState
                        icon={Medal}
                        title="No finalized medals have been recorded for this category yet."
                    />
                ) : (
                    <ul className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        {medalWinners.map((winner, index) => (
                            <PortalMedalWinnerCard
                                key={index}
                                winner={winner}
                            />
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
