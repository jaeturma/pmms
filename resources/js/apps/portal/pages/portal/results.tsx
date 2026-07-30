import { Head, router } from '@inertiajs/react';
import { Award } from 'lucide-react';
import { results as publicResults } from '@/routes/public';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import { PortalSelect } from '@/apps/portal/components/select';
import type { PortalMeetSummary, PortalResultRow, PortalSportOption } from '@/apps/portal/types';

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
                            <div key={result.id} className="portal-animate-in rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4">
                                <div className="flex flex-wrap items-baseline justify-between gap-2">
                                    <p className="font-semibold">{result.event}</p>
                                    {result.official_as_of && (
                                        <p className="text-xs text-[var(--portal-muted-foreground)]">Official as of {result.official_as_of}</p>
                                    )}
                                </div>
                                <ol className="mt-3 space-y-1.5 text-sm">
                                    {result.placements.map((placement) => (
                                        <li key={placement.rank} className="flex flex-wrap items-baseline justify-between gap-2">
                                            <span>
                                                <span className="mr-2 font-semibold tabular-nums">{placement.rank}.</span>
                                                {placement.athlete}
                                                {placement.is_tie && <span className="ml-1 text-xs text-[var(--portal-muted-foreground)]">(tie)</span>}
                                            </span>
                                            <span className="text-[var(--portal-muted-foreground)]">
                                                {placement.school} · {placement.delegation}
                                                {placement.mark && <span className="ml-2 font-medium text-[var(--portal-fg)]">{placement.mark}</span>}
                                            </span>
                                        </li>
                                    ))}
                                </ol>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
