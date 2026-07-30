import { Head, Link } from '@inertiajs/react';
import { Image as ImageIcon } from 'lucide-react';
import { results as publicResults } from '@/routes/public';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import type { PortalContestedSport, PortalMeetSummary } from '@/apps/portal/types';

type Props = {
    meet: PortalMeetSummary;
    sports: PortalContestedSport[];
};

/** No photo/media model or upload pipeline exists anywhere in PMMS —
 * these are real sport-identity tiles (name + event count), never
 * fabricated stock photography. Same real data as `sports.tsx`, a
 * different visual presentation of it. */
export default function PortalGallery({ meet, sports }: Props) {
    return (
        <>
            <Head title={`Gallery — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PortalHero eyebrow="Gallery" title={meet.name} description="Sport identity tiles for every sport contested at this meet." />

                {sports.length === 0 ? (
                    <PortalEmptyState icon={ImageIcon} title="Nothing to show yet" />
                ) : (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        {sports.map((sport) => (
                            <Link
                                key={sport.id}
                                href={publicResults(meet.id, { query: { sport_id: sport.id } }).url}
                                className="portal-animate-in flex aspect-square flex-col items-center justify-center gap-2 rounded-[var(--portal-radius)] bg-[var(--portal-ink)] p-4 text-center text-[var(--portal-ink-foreground)] transition-transform hover:-translate-y-0.5"
                            >
                                <span className="text-2xl font-bold">{sport.name.slice(0, 2).toUpperCase()}</span>
                                <span className="text-sm font-medium">{sport.name}</span>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
