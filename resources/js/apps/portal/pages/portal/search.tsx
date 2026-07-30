import { Head, router } from '@inertiajs/react';
import { Search as SearchIcon } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { search as publicSearch } from '@/routes/public';
import { PortalButton } from '@/apps/portal/components/button';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import type { PortalAnnouncement, PortalContestedSport, PortalMeetSummary, PortalSchool, PortalSearchPlacement } from '@/apps/portal/types';

type Props = {
    meet: PortalMeetSummary;
    query: string;
    schools: PortalSchool[];
    sports: PortalContestedSport[];
    announcements: PortalAnnouncement[];
    placements: PortalSearchPlacement[];
};

export default function PortalSearch({ meet, query, schools, sports, announcements, placements }: Props) {
    const [term, setTerm] = useState(query);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.get(publicSearch(meet.id).url, term ? { q: term } : {}, { preserveState: true });
    };

    return (
        <>
            <Head title={`Search — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PortalHero eyebrow="Search" title={meet.name} description="Search schools, sports, announcements, and results." />

                <form onSubmit={submit} className="flex gap-2">
                    <input
                        type="search"
                        value={term}
                        onChange={(event) => setTerm(event.target.value)}
                        placeholder="Search…"
                        className="h-10 flex-1 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] px-3 text-sm"
                    />
                    <PortalButton type="submit">
                        <SearchIcon aria-hidden="true" className="size-4" />
                        Search
                    </PortalButton>
                </form>

                {query && (
                    <div className="space-y-6">
                        <section className="space-y-2">
                            <PortalSectionHeader title="Schools" />
                            {schools.length === 0 ? (
                                <p className="text-sm text-[var(--portal-muted-foreground)]">No matching schools.</p>
                            ) : (
                                <ul className="text-sm">
                                    {schools.map((school) => (
                                        <li key={school.id}>{school.name}</li>
                                    ))}
                                </ul>
                            )}
                        </section>

                        <section className="space-y-2">
                            <PortalSectionHeader title="Sports" />
                            {sports.length === 0 ? (
                                <p className="text-sm text-[var(--portal-muted-foreground)]">No matching sports.</p>
                            ) : (
                                <ul className="text-sm">
                                    {sports.map((sport) => (
                                        <li key={sport.id}>{sport.name}</li>
                                    ))}
                                </ul>
                            )}
                        </section>

                        <section className="space-y-2">
                            <PortalSectionHeader title="Announcements" />
                            {announcements.length === 0 ? (
                                <p className="text-sm text-[var(--portal-muted-foreground)]">No matching announcements.</p>
                            ) : (
                                <ul className="space-y-1 text-sm">
                                    {announcements.map((announcement) => (
                                        <li key={announcement.id}>{announcement.title}</li>
                                    ))}
                                </ul>
                            )}
                        </section>

                        <section className="space-y-2">
                            <PortalSectionHeader title="Results" />
                            {placements.length === 0 ? (
                                <p className="text-sm text-[var(--portal-muted-foreground)]">No matching results.</p>
                            ) : (
                                <ul className="space-y-1 text-sm">
                                    {placements.map((placement, index) => (
                                        <li key={index} className="flex justify-between gap-2">
                                            <span>
                                                {placement.rank}. {placement.athlete} — {placement.event}
                                            </span>
                                            <span className="text-[var(--portal-muted-foreground)]">{placement.school}</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    </div>
                )}
            </div>
        </>
    );
}
