import { Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { LiveBadge } from '@/components/live-badge';
import { scoreboard } from '@/routes/public';

export type LiveMatch = {
    match_id: number;
    event: string;
    round_label: string;
    side_a_label: string;
    side_b_label: string;
    score_a: number;
    score_b: number;
    status_label: string;
};

type Props = {
    meetId: number;
    matches: LiveMatch[];
};

/**
 * "Live now" entry points — shared by the portal home (WP-08.5-03) and
 * the meet page (WP-08.5-02's original consumer), extracted on second use
 * so the two don't drift out of sync. Renders nothing when no match is
 * currently live.
 */
export function PublicLiveMatches({ meetId, matches }: Props) {
    if (matches.length === 0) {
        return null;
    }

    return (
        <section className="flex flex-col gap-3">
            <Heading variant="small" title="Live now" />
            <ul className="grid gap-2 sm:grid-cols-2">
                {matches.map((match) => (
                    <li key={match.match_id}>
                        <Link
                            href={
                                scoreboard({
                                    meet: meetId,
                                    match: match.match_id,
                                }).url
                            }
                            className="flex flex-col gap-1.5 rounded-xl border p-3 text-sm hover:bg-accent"
                        >
                            <span className="flex items-center justify-between gap-2">
                                <span className="font-medium">
                                    {match.event}
                                </span>
                                <LiveBadge label={match.status_label} />
                            </span>
                            <span className="text-muted-foreground">
                                {match.round_label} · {match.side_a_label}{' '}
                                {match.score_a}–{match.score_b}{' '}
                                {match.side_b_label}
                            </span>
                        </Link>
                    </li>
                ))}
            </ul>
        </section>
    );
}
