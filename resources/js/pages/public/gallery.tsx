import { Head, Link } from '@inertiajs/react';
import { Images } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PublicPageHero } from '@/components/public-page-hero';
import { sportIcon } from '@/components/sports-medal-strip';
import { Badge } from '@/components/ui/badge';
import {
    results as publicResults,
    tally as publicTally,
} from '@/routes/public';

type SportRow = {
    id: number;
    name: string;
    event_count: number;
};

type Props = {
    meet: {
        id: number;
        name: string;
        school_year: string;
        starts_at: string;
        ends_at: string;
        venue: string | null;
        status_label: string;
    };
    sports: SportRow[];
};

/**
 * PMMS has no photo/media pipeline anywhere, and fabricating stock
 * "event photos" would misrepresent real DepEd content (Phase 11's own
 * DESIGN-NOTES) — so this gallery is a grid of real sport-identity
 * tiles (icon + name + real event count), not photography. Each tile
 * is its own aspect-square "card" (Arena's consistent-aspect-ratio
 * grid language) rather than `sports.tsx`'s horizontal list-card, so
 * the two pages read as genuinely different presentations of the same
 * real data, not duplicates of one another.
 */
export default function PublicGallery({ meet, sports }: Props) {
    return (
        <>
            <Head title={`Gallery — ${meet.name}`} />
            <div className="flex flex-col gap-6 sm:gap-8">
                <PublicPageHero
                    title="Gallery"
                    description="A visual look at every sport contested at this meet."
                    meta={
                        <div className="flex flex-wrap items-center gap-2">
                            <span>{meet.name}</span>
                            <Badge
                                variant="secondary"
                                className="bg-white/15 text-white"
                            >
                                {meet.status_label}
                            </Badge>
                        </div>
                    }
                />

                {sports.length === 0 ? (
                    <EmptyState
                        icon={Images}
                        title="Nothing to show yet"
                        description="The gallery fills in once sports are added to this meet."
                    />
                ) : (
                    <div className="grid animate-card-in grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
                        {sports.map((sport) => {
                            const Icon = sportIcon(sport.name);

                            return (
                                <div
                                    key={sport.id}
                                    className="flex flex-col gap-2"
                                >
                                    <div className="group flex aspect-square flex-col items-center justify-center gap-2 rounded-xl border bg-primary/5 p-4 text-center transition-[transform,box-shadow] duration-(--duration-base) ease-premium hover:-translate-y-0.5 hover:shadow-md">
                                        <Icon
                                            aria-hidden="true"
                                            className="size-12 text-primary transition-transform duration-(--duration-base) ease-premium group-hover:scale-110"
                                        />
                                        <div>
                                            <p className="font-semibold">
                                                {sport.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {sport.event_count}{' '}
                                                {sport.event_count === 1
                                                    ? 'event'
                                                    : 'events'}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center justify-center gap-2 text-xs">
                                        <Link
                                            href={`${publicResults(meet.id).url}?sport_id=${sport.id}`}
                                            className="text-muted-foreground underline-offset-2 hover:text-foreground hover:underline"
                                        >
                                            Results
                                        </Link>
                                        <span
                                            aria-hidden="true"
                                            className="text-muted-foreground"
                                        >
                                            ·
                                        </span>
                                        <Link
                                            href={`${publicTally(meet.id).url}?sport_id=${sport.id}`}
                                            className="text-muted-foreground underline-offset-2 hover:text-foreground hover:underline"
                                        >
                                            Medal tally
                                        </Link>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </>
    );
}
