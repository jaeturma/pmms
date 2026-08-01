import { Head, router } from '@inertiajs/react';
import { Award } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalResultPlacements } from '@/apps/portal/components/result-placements';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import { PortalSelect } from '@/apps/portal/components/select';
import type { PortalMeetSummary, PortalResultRow, PortalSportOption } from '@/apps/portal/types';
import { results as publicResults } from '@/routes/public';

type Props = {
    meet: PortalMeetSummary;
    results: PortalResultRow[];
    filters: { sport_id: number | null };
    sportOptions: PortalSportOption[];
};

export default function PortalResults({ meet, results, filters, sportOptions }: Props) {
    return (
        <>
            <Head title={`Results — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PortalHero eyebrow="Results" title={meet.name} description="Official, validated event results only." />

                <PortalSectionHeader
                    title="Validated results"
                    action={
                        <PortalSelect
                            value={filters.sport_id ?? ''}
                            placeholder="All sports"
                            options={sportOptions.map((sport) => ({ value: String(sport.id), label: sport.label }))}
                            onChange={(event) =>
                                router.get(
                                    publicResults(meet.id).url,
                                    event.target.value ? { sport_id: event.target.value } : {},
                                    { preserveState: true, preserveScroll: true },
                                )
                            }
                        />
                    }
                />

                {results.length === 0 ? (
                    <PortalEmptyState icon={Award} title="No results yet" description="Validated results will appear here." />
                ) : (
                    <div className="space-y-4">
                        {results.map((result) => (
                            <PortalResultPlacements
                                key={result.id}
                                result={result}
                                className="portal-animate-in rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4"
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
