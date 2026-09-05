import { Head } from '@inertiajs/react';
import { Search, Trophy } from 'lucide-react';
import { useMemo, useState } from 'react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import { PortalSportCard } from '@/apps/portal/components/sport-card';
import { PortalSportEvents } from '@/apps/portal/components/sport-events';
import { PortalTabs } from '@/apps/portal/components/tabs';
import type {
    PortalMeetSummary,
    PortalSportCard as PortalSportCardData,
} from '@/apps/portal/types';

type Props = {
    meet: PortalMeetSummary | null;
    sports: PortalSportCardData[];
};

type Classification = 'regular' | 'paragames';

/**
 * The public sports directory (`/sports-directory`) — a browsable catalog
 * of every routed sport, split into Regular Sports/Paragames tabs with a
 * client-side search (the full catalog is 28 rows, already loaded on this
 * page — the same reasoning `teams.tsx` uses for its own search). Kept
 * intentionally simple: two tabs, a search box, a grid of cards — no
 * heavier grouping, no dashboard-style stat tables.
 */
export default function PortalSportsDirectory({ meet, sports }: Props) {
    const [classification, setClassification] =
        useState<Classification>('regular');
    const [query, setQuery] = useState('');

    const regularCount = useMemo(
        () => sports.filter((sport) => !sport.is_paragames).length,
        [sports],
    );
    const paragamesCount = useMemo(
        () => sports.filter((sport) => sport.is_paragames).length,
        [sports],
    );

    const filteredSports = useMemo(() => {
        const term = query.trim().toLowerCase();
        const byClassification = sports.filter((sport) =>
            classification === 'paragames'
                ? sport.is_paragames
                : !sport.is_paragames,
        );

        return term === ''
            ? byClassification
            : byClassification.filter((sport) =>
                  sport.name.toLowerCase().includes(term),
              );
    }, [sports, classification, query]);

    return (
        <>
            <Head title="Sports" />
            <div className="flex flex-col gap-6">
                <PortalHero
                    title="Sports"
                    description={
                        meet
                            ? `Browse every sport contested at ${meet.name} and beyond.`
                            : 'Browse the full provincial meet sports catalog.'
                    }
                />

                {sports.length === 0 ? (
                    <PortalEmptyState
                        icon={Trophy}
                        title="No sports available yet"
                    />
                ) : (
                    <>
                        <PortalSectionHeader
                            title="All Sports"
                            action={
                                <label className="relative">
                                    <span className="sr-only">
                                        Search sports
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
                                        placeholder="Search sports…"
                                        className="h-10 w-56 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] pr-3 pl-9 text-sm"
                                    />
                                </label>
                            }
                        />

                        <PortalTabs
                            tabs={[
                                {
                                    value: 'regular',
                                    label: `Regular Sports (${regularCount})`,
                                },
                                {
                                    value: 'paragames',
                                    label: `Paragames (${paragamesCount})`,
                                },
                            ]}
                            value={classification}
                            onChange={(value) =>
                                setClassification(value as Classification)
                            }
                        />

                        {filteredSports.length === 0 ? (
                            <PortalEmptyState
                                icon={Search}
                                title={`No sport matches "${query}".`}
                            />
                        ) : (
                            <section className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {filteredSports.map((sport) => (
                                    <div key={sport.id} className="space-y-3">
                                        <PortalSportCard sport={sport} />
                                        <details className="rounded-lg border border-[var(--portal-border)] p-3">
                                            <summary className="cursor-pointer font-semibold">
                                                Sports Events (
                                                {sport.events.length})
                                            </summary>
                                            <div className="mt-3">
                                                <PortalSportEvents
                                                    events={sport.events}
                                                />
                                            </div>
                                        </details>
                                    </div>
                                ))}
                            </section>
                        )}
                    </>
                )}
            </div>
        </>
    );
}
