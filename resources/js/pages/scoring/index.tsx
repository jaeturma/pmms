import { Head, Link } from '@inertiajs/react';
import { Radio } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Match = {
    id: number;
    event: string;
    sport: string;
    mode: string;
    schedule: string;
    venue: string | null;
    viewer_url: string;
};

export default function Scoreboards({ matches }: { matches: Match[] }) {
    return (
        <>
            <Head title="Scoreboard" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Scoreboard"
                    description="Operate Basketball, Baseball, and Boxing scoreboards linked from Schedule of Events."
                />
                {matches.length === 0 ? (
                    <EmptyState
                        icon={Radio}
                        title="No scoreboards linked"
                        description="Enable Live Scoreboard when adding a schedule for your sport."
                    />
                ) : (
                    <div className="grid gap-4 lg:grid-cols-2">
                        {matches.map((match) => (
                            <section
                                key={match.id}
                                className="space-y-4 rounded-xl border p-5"
                            >
                                <div className="flex flex-wrap gap-2">
                                    <Badge variant="outline">
                                        {match.sport}
                                    </Badge>
                                    <Badge>{match.mode}</Badge>
                                </div>
                                <h2 className="text-lg font-semibold">
                                    {match.event}
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    {match.schedule} /{' '}
                                    {match.venue ?? 'Venue unavailable'}
                                </p>
                                <div className="flex gap-2">
                                    <Button asChild>
                                        <Link
                                            href={`/matches/${match.id}/scoreboard`}
                                        >
                                            Operate scoreboard
                                        </Link>
                                    </Button>
                                    <Button variant="outline" asChild>
                                        <a
                                            href={match.viewer_url}
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            Viewer display
                                        </a>
                                    </Button>
                                </div>
                            </section>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
