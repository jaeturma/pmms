import { Head, Link } from '@inertiajs/react';
import { Trophy } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalSportEvents } from '@/apps/portal/components/sport-events';
import type {
    PortalContestedSport,
    PortalMeetSummary,
} from '@/apps/portal/types';
import {
    results as publicResults,
    tally as publicTally,
} from '@/routes/public';

type Props = {
    meet: PortalMeetSummary;
    sports: PortalContestedSport[];
};

export default function PortalSports({ meet, sports }: Props) {
    return (
        <>
            <Head title={`Sports — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PortalHero
                    title="Sports"
                    description="Every sport contested at this meet."
                />

                {sports.length === 0 ? (
                    <PortalEmptyState
                        icon={Trophy}
                        title="No sports registered yet"
                    />
                ) : (
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {sports.map((sport) => (
                            <div
                                key={sport.id}
                                className="portal-animate-in relative isolate min-h-36 overflow-hidden rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4"
                            >
                                {sport.photo_url && (
                                    <img
                                        src={sport.photo_url}
                                        alt=""
                                        className="absolute inset-0 -z-20 size-full object-cover opacity-35"
                                    />
                                )}
                                <div className="absolute inset-0 -z-10 bg-gradient-to-r from-[var(--portal-surface)]/90 to-[var(--portal-surface)]/45" />
                                <p className="font-semibold">{sport.name}</p>
                                <p className="text-sm text-[var(--portal-muted-foreground)]">
                                    {sport.event_count} event
                                    {sport.event_count === 1 ? '' : 's'}
                                </p>
                                <details className="mt-3">
                                    <summary className="cursor-pointer font-semibold">
                                        Sports Events
                                    </summary>
                                    <div className="mt-3">
                                        <PortalSportEvents
                                            events={sport.events}
                                        />
                                    </div>
                                </details>
                                <div className="mt-3 flex gap-3 text-sm">
                                    <Link
                                        href={
                                            publicResults(meet.id, {
                                                query: { sport_id: sport.id },
                                            }).url
                                        }
                                        className="text-[var(--portal-accent)] hover:underline"
                                    >
                                        Results
                                    </Link>
                                    <Link
                                        href={
                                            publicTally(meet.id, {
                                                query: { sport_id: sport.id },
                                            }).url
                                        }
                                        className="text-[var(--portal-accent)] hover:underline"
                                    >
                                        Medal tally
                                    </Link>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
