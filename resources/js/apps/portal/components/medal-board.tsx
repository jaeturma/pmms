import { Link } from '@inertiajs/react';
import { Medal } from 'lucide-react';
import { useMemo } from 'react';
import { PortalAnimatedNumber } from '@/apps/portal/components/animated-number';
import { MunicipalityCrest } from '@/apps/portal/components/municipality-crest';
import { usePortalFlipRows } from '@/apps/portal/lib/use-flip-rows';
import { cn } from '@/apps/portal/lib/utils';
import type { PortalStandingRow } from '@/apps/portal/types';
import { show as teamShow } from '@/routes/public/teams';

type MedalKey = 'gold' | 'silver' | 'bronze';

const MEDALS: { key: MedalKey; label: string; bg: string; fg: string }[] = [
    {
        key: 'gold',
        label: 'Gold',
        bg: 'var(--portal-accent-soft)',
        fg: 'var(--portal-accent)',
    },
    {
        key: 'silver',
        label: 'Silver',
        bg: 'oklch(0.93 0.005 258)',
        fg: 'oklch(0.5 0.01 258)',
    },
    {
        key: 'bronze',
        label: 'Bronze',
        bg: 'var(--portal-maroon-soft)',
        fg: 'var(--portal-maroon)',
    },
];

type PortalMedalBoardProps = {
    rows: PortalStandingRow[];
    /** Live `/tally` only: pulse rising counts and FLIP-slide rows to
     * their new rank instead of jumping. */
    animate?: boolean;
};

/**
 * The public medal tally's centrepiece — an official-competition medal
 * board (Rank · Delegation · Gold · Silver · Bronze · Total) built for
 * desktop, projector, LED wall and mobile alike. Column widths are fixed
 * so a live count change never shifts the layout; the top three get a
 * restrained medallion + tinted row, never neon or motion for its own
 * sake. Ranking/sort order is decided server-side
 * (`MedalTallyService::ordered()`) — this only renders it.
 */
export function PortalMedalBoard({
    rows,
    animate = false,
}: PortalMedalBoardProps) {
    const totalsByKey = useMemo(() => {
        const map = new Map<string, number>();

        for (const row of rows) {
            map.set(rowKey(row), row.total);
        }

        return map;
    }, [rows]);

    const { rowRef, reduced } = usePortalFlipRows(animate, {
        flashOnIncrease: totalsByKey,
    });

    const count = (value: number) =>
        animate ? (
            <PortalAnimatedNumber value={value} reduced={reduced} />
        ) : (
            value
        );

    return (
        <div className="portal-medal-board overflow-x-auto rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] shadow-sm">
            <table className="w-full table-fixed text-[var(--portal-surface-foreground)]">
                <colgroup>
                    <col className="w-14 sm:w-20" />
                    <col />
                    <col className="w-16 sm:w-24 lg:w-28" />
                    <col className="w-16 sm:w-24 lg:w-28" />
                    <col className="w-16 sm:w-24 lg:w-28" />
                    <col className="w-16 sm:w-24 lg:w-28" />
                </colgroup>
                <thead>
                    <tr className="border-b border-[var(--portal-border)] bg-[var(--portal-muted)] text-[var(--portal-muted-foreground)]">
                        <th className="px-2 py-3 text-center text-xs font-semibold tracking-wide uppercase sm:px-4">
                            Rank
                        </th>
                        <th className="px-2 py-3 text-left text-xs font-semibold tracking-wide uppercase sm:px-4">
                            Delegation
                        </th>
                        {MEDALS.map(({ key, label, bg, fg }) => (
                            <th key={key} className="px-1 py-2 sm:px-2">
                                <span className="flex flex-col items-center gap-1">
                                    <span
                                        className="portal-icon-badge size-6 sm:size-8"
                                        style={{
                                            backgroundColor: bg,
                                            color: fg,
                                        }}
                                    >
                                        <Medal
                                            aria-hidden="true"
                                            className="size-3.5 sm:size-4"
                                        />
                                    </span>
                                    <span className="text-[0.65rem] font-semibold tracking-wide text-[var(--portal-muted-foreground)] uppercase sm:text-xs">
                                        {label}
                                    </span>
                                </span>
                            </th>
                        ))}
                        <th className="px-1 py-2 text-center text-[0.65rem] font-bold tracking-wide text-[var(--portal-fg)] uppercase sm:px-2 sm:text-xs">
                            Total
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-[var(--portal-border)] text-lg sm:text-xl lg:text-2xl xl:text-[26px]">
                    {rows.map((row, index) => {
                        const key = rowKey(row);
                        const rank = index + 1;
                        const podium = rank <= 3;

                        const identity = (
                            <span className="flex min-w-0 items-center gap-2 sm:gap-3">
                                <MunicipalityCrest
                                    name={row.district}
                                    logoUrl={row.team_logo_url ?? row.logo_url}
                                    size="sm"
                                    shape="square"
                                    className="size-8 shrink-0 sm:size-11 lg:size-12"
                                />
                                <span className="truncate font-semibold">
                                    {row.district}
                                </span>
                            </span>
                        );

                        return (
                            <tr
                                key={key}
                                ref={animate ? rowRef(key) : undefined}
                                className={cn(
                                    animate && 'portal-tally-row',
                                    podium && 'portal-medal-board__podium',
                                    `portal-medal-board__rank-${podium ? rank : 'x'}`,
                                )}
                            >
                                <td className="px-2 py-2 text-center sm:px-4 sm:py-3">
                                    <span
                                        className={cn(
                                            'inline-flex size-8 items-center justify-center rounded-full text-sm font-bold tabular-nums sm:size-10 sm:text-base',
                                            podium
                                                ? 'portal-medal-board__medallion'
                                                : 'text-[var(--portal-muted-foreground)]',
                                        )}
                                    >
                                        {rank}
                                    </span>
                                </td>
                                <td className="px-2 py-2 sm:px-4 sm:py-3">
                                    {row.slug ? (
                                        <Link
                                            href={teamShow(row.slug).url}
                                            className="hover:underline"
                                        >
                                            {identity}
                                        </Link>
                                    ) : (
                                        identity
                                    )}
                                </td>
                                <td className="px-1 py-2 text-center tabular-nums sm:px-2">
                                    {count(row.gold)}
                                </td>
                                <td className="px-1 py-2 text-center tabular-nums sm:px-2">
                                    {count(row.silver)}
                                </td>
                                <td className="px-1 py-2 text-center tabular-nums sm:px-2">
                                    {count(row.bronze)}
                                </td>
                                <td className="px-1 py-2 text-center font-bold tabular-nums sm:px-2">
                                    {count(row.total)}
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}

function rowKey(row: PortalStandingRow): string {
    return row.slug ? `d-${row.slug}` : `d-${row.district_id ?? row.district}`;
}
