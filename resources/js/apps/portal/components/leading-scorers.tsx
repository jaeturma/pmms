import { Medal } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';

type PortalScorerRow = {
    athlete: string;
    school: string;
    points: number;
};

type PortalLeadingScorersProps = {
    rows: PortalScorerRow[] | null;
};

export function PortalLeadingScorers({ rows }: PortalLeadingScorersProps) {
    if (rows === null || rows.length === 0) {
        return (
            <PortalEmptyState
                icon={Medal}
                tone="maroon"
                title="Leading scorers not available"
                description="No per-athlete scoring data exists for this sport yet."
            />
        );
    }

    return (
        <ol className="space-y-2">
            {rows.map((row, index) => (
                <li
                    key={row.athlete}
                    className="flex items-center justify-between gap-3 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] px-3 py-2 text-sm"
                >
                    <span className="flex items-center gap-2">
                        <span className="text-xs font-semibold text-[var(--portal-muted-foreground)]">
                            {index + 1}
                        </span>
                        <span>
                            {row.athlete}{' '}
                            <span className="text-[var(--portal-muted-foreground)]">
                                — {row.school}
                            </span>
                        </span>
                    </span>
                    <span className="font-semibold tabular-nums">
                        {row.points}
                    </span>
                </li>
            ))}
        </ol>
    );
}
