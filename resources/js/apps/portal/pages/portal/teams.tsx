import { Head } from '@inertiajs/react';
import { Search, Users } from 'lucide-react';
import { useMemo, useState } from 'react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import { PortalTeamCard } from '@/apps/portal/components/team-card';
import type {
    PortalMeetSummary,
    PortalMunicipalityTeam,
} from '@/apps/portal/types';

/**
 * The public municipal Teams/Delegations directory index (WP: public
 * municipal teams, Stage 3). Search is client-side — the competing-
 * municipality list for one meet is small (tens of rows, not thousands)
 * and already fully loaded on this page, so a second request just to
 * filter it would be pure overhead.
 */
type Props = {
    meet: PortalMeetSummary | null;
    teams: PortalMunicipalityTeam[];
};

export default function PortalTeams({ meet, teams }: Props) {
    const [query, setQuery] = useState('');

    const filteredTeams = useMemo(() => {
        const term = query.trim().toLowerCase();

        return term === ''
            ? teams
            : teams.filter((team) => team.name.toLowerCase().includes(term));
    }, [teams, query]);

    return (
        <>
            <Head title="Teams" />
            <div className="flex flex-col gap-6">
                <PortalHero
                    title="Teams"
                    description={
                        meet
                            ? `Municipal delegations competing in ${meet.name}.`
                            : 'No meet is currently active.'
                    }
                />

                {teams.length === 0 ? (
                    <PortalEmptyState
                        icon={Users}
                        title="No competing municipalities yet"
                        description="Delegations will appear here once they're registered for the active meet."
                    />
                ) : (
                    <>
                        <PortalSectionHeader
                            title="Municipalities"
                            action={
                                <label className="relative">
                                    <span className="sr-only">
                                        Search municipalities
                                    </span>
                                    <Search
                                        aria-hidden="true"
                                        className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-[var(--portal-muted-foreground)]"
                                    />
                                    <input
                                        type="search"
                                        value={query}
                                        onChange={(event) =>
                                            setQuery(event.target.value)
                                        }
                                        placeholder="Search municipalities…"
                                        className="h-10 w-56 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] pr-3 pl-9 text-sm"
                                    />
                                </label>
                            }
                        />

                        {filteredTeams.length === 0 ? (
                            <PortalEmptyState
                                icon={Search}
                                title={`No municipality matches "${query}".`}
                            />
                        ) : (
                            <section className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {filteredTeams.map(
                                    (team: PortalMunicipalityTeam) => (
                                        <PortalTeamCard
                                            key={team.id}
                                            team={team}
                                        />
                                    ),
                                )}
                            </section>
                        )}
                    </>
                )}
            </div>
        </>
    );
}
