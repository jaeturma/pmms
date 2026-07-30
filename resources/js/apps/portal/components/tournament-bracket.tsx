import { Network } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';

type PortalBracketRound = {
    label: string;
    matchups: Array<{ side_a: string; side_b: string }>;
};

type PortalTournamentBracketProps = {
    rounds: PortalBracketRound[] | null;
};

export function PortalTournamentBracket({ rounds }: PortalTournamentBracketProps) {
    if (rounds === null || rounds.length === 0) {
        return (
            <PortalEmptyState
                icon={Network}
                title="Tournament bracket not available"
                description="No bracket-tree data exists for this sport yet."
            />
        );
    }

    return (
        <div className="flex gap-4 overflow-x-auto pb-2">
            {rounds.map((round) => (
                <div key={round.label} className="min-w-[10rem] space-y-2">
                    <p className="text-xs font-semibold text-[var(--portal-muted-foreground)] uppercase">{round.label}</p>
                    {round.matchups.map((matchup, index) => (
                        <div
                            key={index}
                            className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-2 text-xs"
                        >
                            <p>{matchup.side_a}</p>
                            <p className="text-[var(--portal-muted-foreground)]">vs</p>
                            <p>{matchup.side_b}</p>
                        </div>
                    ))}
                </div>
            ))}
        </div>
    );
}
