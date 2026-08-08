import { Head } from '@inertiajs/react';
import { Search, Users } from 'lucide-react';
import { useMemo, useState } from 'react';
import { PortalCoachCard } from '@/apps/portal/components/coach-card';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalPlayerCard } from '@/apps/portal/components/player-card';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import { PortalSelect } from '@/apps/portal/components/select';
import { sportIcon } from '@/apps/portal/components/sport-icon';
import type {
    PortalMeetSummary,
    PortalSportPersonnel,
} from '@/apps/portal/types';

/**
 * The public municipal players-and-coaches roster, grouped by sport (WP:
 * public municipal teams, Stage 5) — a lightweight search plus Role and
 * Category filters, all client-side (this municipality's roster is
 * already fully loaded on this page, and only ever this one municipality's
 * — never the whole meet — per the WP's explicit "don't load athletes from
 * other municipalities" performance rule).
 */
type Role = 'all' | 'athletes' | 'coaches';
type Category = 'all' | 'elementary' | 'secondary' | 'paragames';

const ROLE_OPTIONS = [
    { value: 'all', label: 'All roles' },
    { value: 'athletes', label: 'Athletes' },
    { value: 'coaches', label: 'Coaches' },
];

const CATEGORY_OPTIONS = [
    { value: 'all', label: 'All categories' },
    { value: 'elementary', label: 'Elementary' },
    { value: 'secondary', label: 'Secondary' },
    { value: 'paragames', label: 'Paragames' },
];

type Props = {
    meet: PortalMeetSummary;
    team: { id: number; slug: string; name: string; logo_url: string | null };
    sports: PortalSportPersonnel[];
};

export default function PortalTeamPlayersCoaches({
    meet,
    team,
    sports,
}: Props) {
    const [query, setQuery] = useState('');
    const [role, setRole] = useState<Role>('all');
    const [category, setCategory] = useState<Category>('all');

    const filteredSports = useMemo(() => {
        const term = query.trim().toLowerCase();
        const matches = (...values: string[]) =>
            term === '' ||
            values.some((value) => value.toLowerCase().includes(term));

        return sports
            .filter((sport) => category !== 'paragames' || sport.is_paragames)
            .map((sport) => ({
                ...sport,
                athletes:
                    role === 'coaches'
                        ? []
                        : sport.athletes.filter(
                              (athlete) =>
                                  (category === 'all' ||
                                      category === 'paragames' ||
                                      athlete.level === category) &&
                                  matches(
                                      athlete.name,
                                      sport.sport,
                                      athlete.event,
                                      athlete.school,
                                  ),
                          ),
                // Coaches have no per-category record in this app (a coach
                // is assigned to a sport, not a specific event/age
                // division) — an Elementary/Secondary filter hides them
                // rather than guessing which category they belong to; the
                // Paragames filter still applies since that's a sport-level
                // fact, not a per-coach one.
                coaches:
                    role === 'athletes' ||
                    category === 'elementary' ||
                    category === 'secondary'
                        ? []
                        : sport.coaches.filter((coach) =>
                              matches(coach.name, sport.sport, coach.school),
                          ),
            }))
            .filter(
                (sport) =>
                    sport.athletes.length > 0 || sport.coaches.length > 0,
            );
    }, [sports, query, role, category]);

    return (
        <>
            <Head title={`${team.name} — Players & Coaches`} />
            <div className="flex flex-col gap-6">
                <PortalHero
                    eyebrow={meet.name}
                    title={`${team.name} — Players & Coaches`}
                />

                {sports.length === 0 ? (
                    <PortalEmptyState
                        icon={Users}
                        title="No public athlete records are currently available."
                    />
                ) : (
                    <>
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <label className="relative w-full sm:max-w-xs sm:flex-1">
                                <span className="sr-only">
                                    Search players and coaches
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
                                    placeholder="Search name, sport, event, school…"
                                    className="h-10 w-full rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] pr-3 pl-9 text-sm"
                                />
                            </label>

                            <div className="flex gap-2">
                                <label className="flex-1 sm:flex-none">
                                    <span className="sr-only">
                                        Filter by role
                                    </span>
                                    <PortalSelect
                                        options={ROLE_OPTIONS}
                                        value={role}
                                        onChange={(event) =>
                                            setRole(event.target.value as Role)
                                        }
                                        className="w-full sm:w-auto"
                                    />
                                </label>

                                <label className="flex-1 sm:flex-none">
                                    <span className="sr-only">
                                        Filter by category
                                    </span>
                                    <PortalSelect
                                        options={CATEGORY_OPTIONS}
                                        value={category}
                                        onChange={(event) =>
                                            setCategory(
                                                event.target.value as Category,
                                            )
                                        }
                                        className="w-full sm:w-auto"
                                    />
                                </label>
                            </div>
                        </div>

                        {filteredSports.length === 0 ? (
                            <PortalEmptyState
                                icon={Search}
                                title="No players or coaches match these filters."
                            />
                        ) : (
                            filteredSports.map((sport) => {
                                const Icon = sportIcon(sport.sport);

                                return (
                                    <section
                                        key={sport.sport}
                                        className="flex flex-col gap-3"
                                    >
                                        <PortalSectionHeader
                                            title={
                                                <>
                                                    <span className="portal-icon-badge size-10 bg-[var(--portal-accent-soft)] text-[var(--portal-accent)]">
                                                        <Icon
                                                            aria-hidden="true"
                                                            className="size-5"
                                                        />
                                                    </span>
                                                    {sport.sport}
                                                </>
                                            }
                                        />

                                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div>
                                                <p className="mb-2 text-sm font-semibold text-[var(--portal-muted-foreground)]">
                                                    Players
                                                </p>
                                                {sport.athletes.length === 0 ? (
                                                    <p className="text-sm text-[var(--portal-muted-foreground)]">
                                                        No public athlete
                                                        records are currently
                                                        available.
                                                    </p>
                                                ) : (
                                                    <ul className="flex flex-col gap-2">
                                                        {sport.athletes.map(
                                                            (
                                                                athlete,
                                                                index,
                                                            ) => (
                                                                <PortalPlayerCard
                                                                    key={index}
                                                                    athlete={
                                                                        athlete
                                                                    }
                                                                />
                                                            ),
                                                        )}
                                                    </ul>
                                                )}
                                            </div>

                                            <div>
                                                <p className="mb-2 text-sm font-semibold text-[var(--portal-muted-foreground)]">
                                                    Coaches
                                                </p>
                                                {sport.coaches.length === 0 ? (
                                                    <p className="text-sm text-[var(--portal-muted-foreground)]">
                                                        No coaches recorded for
                                                        this sport yet.
                                                    </p>
                                                ) : (
                                                    <ul className="flex flex-col gap-2">
                                                        {sport.coaches.map(
                                                            (coach, index) => (
                                                                <PortalCoachCard
                                                                    key={index}
                                                                    coach={
                                                                        coach
                                                                    }
                                                                />
                                                            ),
                                                        )}
                                                    </ul>
                                                )}
                                            </div>
                                        </div>
                                    </section>
                                );
                            })
                        )}
                    </>
                )}
            </div>
        </>
    );
}
