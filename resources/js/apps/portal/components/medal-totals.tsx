import { Medal } from 'lucide-react';
import type { PortalMedalTotals } from '@/apps/portal/types';

type PortalMedalTotalsRowProps = {
    totals: PortalMedalTotals;
};

const TILES = [
    {
        key: 'gold',
        bg: 'var(--portal-accent-soft)',
        fg: 'var(--portal-accent)',
    },
    { key: 'silver', bg: 'oklch(0.93 0.005 258)', fg: 'oklch(0.5 0.01 258)' },
    {
        key: 'bronze',
        bg: 'var(--portal-maroon-soft)',
        fg: 'var(--portal-maroon)',
    },
    { key: 'total', bg: 'var(--portal-ink-soft)', fg: 'var(--portal-ink)' },
] as const;

export function PortalMedalTotalsRow({ totals }: PortalMedalTotalsRowProps) {
    return (
        <section className="grid grid-cols-2 gap-3 sm:grid-cols-4">
            {TILES.map(({ key, bg, fg }) => (
                <div
                    key={key}
                    className="flex flex-col items-center gap-2 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4 text-center"
                >
                    <span
                        className="portal-icon-badge size-12"
                        style={{ backgroundColor: bg, color: fg }}
                    >
                        <Medal aria-hidden="true" className="size-6" />
                    </span>
                    <p className="text-2xl font-bold tabular-nums">
                        {totals[key]}
                    </p>
                    <p className="text-xs text-[var(--portal-muted-foreground)] capitalize">
                        {key}
                    </p>
                </div>
            ))}
        </section>
    );
}
