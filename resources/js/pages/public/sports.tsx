import { Head, Link } from '@inertiajs/react';
import { Award, Crown, Dumbbell } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PublicPageHero } from '@/components/public-page-hero';
import { sportIcon } from '@/components/sports-medal-strip';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    results as publicResults,
    tally as publicTally,
} from '@/routes/public';

type SportRow = {
    id: number;
    name: string;
    event_count: number;
    photo_url: string | null;
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

export default function PublicSports({ meet, sports }: Props) {
    return (
        <>
            <Head title={`Sports — ${meet.name}`} />
            <div className="flex flex-col gap-6 sm:gap-8">
                <PublicPageHero
                    title="Sports"
                    description="Every sport contested at this meet, with results and standings for each."
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
                        icon={Dumbbell}
                        title="No sports assigned yet"
                        description="Sports will appear here once events are added to this meet."
                    />
                ) : (
                    <div className="grid animate-card-in grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3">
                        {sports.map((sport) => {
                            const Icon = sportIcon(sport.name);

                            return (
                                <div
                                    key={sport.id}
                                    className="group relative isolate flex min-h-44 flex-col gap-4 overflow-hidden rounded-xl border bg-card p-5 transition-[transform,box-shadow] duration-(--duration-base) ease-premium hover:-translate-y-0.5 hover:shadow-md"
                                >
                                    {sport.photo_url && (
                                        <img
                                            src={sport.photo_url}
                                            alt=""
                                            className="absolute inset-0 -z-20 size-full object-cover opacity-35 transition-transform duration-500 group-hover:scale-105"
                                        />
                                    )}
                                    <div className="absolute inset-0 -z-10 bg-gradient-to-b from-card/45 to-card/95" />
                                    <div className="flex items-center gap-3">
                                        <span className="flex size-11 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                            <Icon
                                                aria-hidden="true"
                                                className="size-5"
                                            />
                                        </span>
                                        <div>
                                            <p className="font-medium">
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

                                    <div className="flex flex-wrap gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={`${publicResults(meet.id).url}?sport_id=${sport.id}`}
                                            >
                                                <Award aria-hidden="true" />
                                                Results
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={`${publicTally(meet.id).url}?sport_id=${sport.id}`}
                                            >
                                                <Crown aria-hidden="true" />
                                                Medal tally
                                            </Link>
                                        </Button>
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
