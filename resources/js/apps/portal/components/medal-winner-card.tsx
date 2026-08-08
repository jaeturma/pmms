import { Medal } from 'lucide-react';
import { cn } from '@/apps/portal/lib/utils';
import type { PortalMedalWinner } from '@/apps/portal/types';

const MEDAL_STYLES: Record<
    PortalMedalWinner['medal'],
    { label: string; bg: string; fg: string }
> = {
    gold: {
        label: 'Gold',
        bg: 'var(--portal-accent-soft)',
        fg: 'var(--portal-accent)',
    },
    silver: {
        label: 'Silver',
        bg: 'oklch(0.93 0.005 258)',
        fg: 'oklch(0.5 0.01 258)',
    },
    bronze: {
        label: 'Bronze',
        bg: 'var(--portal-maroon-soft)',
        fg: 'var(--portal-maroon)',
    },
    other: {
        label: 'Placement',
        bg: 'var(--portal-ink-soft)',
        fg: 'var(--portal-ink)',
    },
};

/**
 * One medal-winning record on a municipal team profile — an icon-plus-
 * text medal badge (WP's "medal type is not communicated by color alone"
 * accessibility rule), the athlete or team name, and the sport/event/
 * school it was won in. Team-event medals (`participant_type === 'team'`)
 * show the full roster instead of a single athlete name — this app
 * records a team medal as N tied individual placements (see
 * `MedalTallyService::municipalityMedalWinners()`), not one row, so the
 * roster is the only place those names surface.
 */
export function PortalMedalWinnerCard({
    winner,
}: {
    winner: PortalMedalWinner;
}) {
    const style = MEDAL_STYLES[winner.medal];

    return (
        <li className="flex gap-3 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4">
            <span
                className={cn('portal-icon-badge size-10 shrink-0')}
                style={{ backgroundColor: style.bg, color: style.fg }}
            >
                <Medal aria-hidden="true" className="size-5" />
            </span>

            <div className="min-w-0 flex-1">
                <p
                    className="text-xs font-bold tracking-wide uppercase"
                    style={{ color: style.fg }}
                >
                    {style.label}
                </p>
                <p className="truncate font-semibold">
                    {winner.participant_type === 'team'
                        ? winner.team_name
                        : winner.athlete_name}
                </p>
                <p className="text-sm text-[var(--portal-muted-foreground)]">
                    {winner.sport} — {winner.event} ({winner.level}{' '}
                    {winner.gender})
                </p>
                {winner.school && (
                    <p className="text-sm text-[var(--portal-muted-foreground)]">
                        {winner.school}
                    </p>
                )}
                {winner.participant_type === 'team' &&
                    winner.roster.length > 0 && (
                        <p className="mt-1 text-xs text-[var(--portal-muted-foreground)]">
                            Roster: {winner.roster.join(', ')}
                        </p>
                    )}
            </div>
        </li>
    );
}
