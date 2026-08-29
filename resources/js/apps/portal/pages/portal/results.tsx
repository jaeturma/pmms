import { Head, router } from '@inertiajs/react';
import { Award, Search } from 'lucide-react';
import { useState } from 'react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalResultPlacements } from '@/apps/portal/components/result-placements';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import { PortalSelect } from '@/apps/portal/components/select';
import type {
    PortalMeetSummary,
    PortalResultRow,
    PortalSportOption,
} from '@/apps/portal/types';
import { results as publicResults } from '@/routes/public';

type Props = {
    meet: PortalMeetSummary;
    results: PortalResultRow[];
    filters: { sport_id: number | null };
    sportOptions: PortalSportOption[];
};

const RESULT_CARD_CLASS =
    'portal-animate-in rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4';

export default function PortalResults({
    meet,
    results,
    filters,
    sportOptions,
}: Props) {
    const [search, setSearch] = useState('');

    const term = search.trim().toLowerCase();
    const visibleResults = term
        ? results.filter((result) => result.event.toLowerCase().includes(term))
        : results;
    const elementaryResults = visibleResults.filter(
        (result) => result.age_division === 'elementary',
    );
    const secondaryResults = visibleResults.filter(
        (result) => result.age_division === 'secondary',
    );

    return (
        <>
            <Head title={`Results — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PortalHero
                    title="Results"
                    description="Official, validated event results only."
                />

                <PortalSectionHeader
                    title="Validated results"
                    action={
                        <div className="flex flex-wrap items-center gap-2">
                            <div className="relative">
                                <Search
                                    aria-hidden="true"
                                    className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-[var(--portal-muted-foreground)]"
                                />
                                <input
                                    type="search"
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    placeholder="Search a result"
                                    aria-label="Search a result"
                                    className="h-9 w-full rounded-[calc(var(--portal-radius)-0.25rem)] border border-[var(--portal-border)] bg-[var(--portal-surface)] pr-3 pl-9 text-sm sm:w-56"
                                />
                            </div>
                            <PortalSelect
                                value={filters.sport_id ?? ''}
                                placeholder="All sports"
                                options={sportOptions.map((sport) => ({
                                    value: String(sport.id),
                                    label: sport.label,
                                }))}
                                onChange={(event) =>
                                    router.get(
                                        publicResults(meet.id).url,
                                        event.target.value
                                            ? { sport_id: event.target.value }
                                            : {},
                                        {
                                            preserveState: true,
                                            preserveScroll: true,
                                        },
                                    )
                                }
                            />
                        </div>
                    }
                />

                {visibleResults.length === 0 ? (
                    <PortalEmptyState
                        icon={Award}
                        title="No results yet"
                        description="Validated results will appear here."
                    />
                ) : (
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div className="space-y-3">
                            <h3 className="text-sm font-semibold tracking-wide text-[var(--portal-muted-foreground)] uppercase">
                                Elementary
                            </h3>
                            {elementaryResults.length === 0 ? (
                                <p className="text-sm text-[var(--portal-muted-foreground)]">
                                    No elementary results yet.
                                </p>
                            ) : (
                                <div className="space-y-4">
                                    {elementaryResults.map((result) => (
                                        <PortalResultPlacements
                                            key={result.id}
                                            result={result}
                                            className={RESULT_CARD_CLASS}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="space-y-3">
                            <h3 className="text-sm font-semibold tracking-wide text-[var(--portal-muted-foreground)] uppercase">
                                Secondary
                            </h3>
                            {secondaryResults.length === 0 ? (
                                <p className="text-sm text-[var(--portal-muted-foreground)]">
                                    No secondary results yet.
                                </p>
                            ) : (
                                <div className="space-y-4">
                                    {secondaryResults.map((result) => (
                                        <PortalResultPlacements
                                            key={result.id}
                                            result={result}
                                            className={RESULT_CARD_CLASS}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}
