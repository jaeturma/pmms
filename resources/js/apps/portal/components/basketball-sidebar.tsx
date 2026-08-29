import { Users } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import {
    foulCount,
    readBoxScore,
    readTeamStats,
    shootingLine,
    timeoutCount,
} from '@/apps/portal/lib/basketball-state';
import type { PortalLiveSession } from '@/apps/portal/types';

type PortalBasketballSidebarProps = {
    session: PortalLiveSession;
};

export function PortalBasketballSidebar({
    session,
}: PortalBasketballSidebarProps) {
    const teamStats = readTeamStats(session.sport_state);
    const boxScore = readBoxScore(session.sport_state);
    const sideALabel = session.side_a_label ?? 'TBD';
    const sideBLabel = session.side_b_label ?? 'TBD';

    const foulsA = foulCount(session.sport_state, 'fouls_a');
    const foulsB = foulCount(session.sport_state, 'fouls_b');
    const timeoutsA = timeoutCount(session.sport_state, 'timeouts_a');
    const timeoutsB = timeoutCount(session.sport_state, 'timeouts_b');
    const showTimeouts = timeoutsA !== undefined && timeoutsB !== undefined;

    return (
        <aside className="flex flex-col gap-3.5">
            <article className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)]">
                <div className="px-[18px] pt-4 pb-2.5 text-sm font-[950] text-[var(--portal-fg)] uppercase">
                    Team Comparison
                </div>

                <div className="grid grid-cols-2 px-[18px] pt-[7px] pb-[11px] text-sm font-[900]">
                    <span className="text-[var(--portal-maroon)] uppercase">
                        {sideALabel}
                    </span>
                    <span className="text-right text-[var(--portal-ink)] uppercase">
                        {sideBLabel}
                    </span>
                </div>
                <table className="mx-4 mb-[18px] w-[calc(100%-2rem)] border-collapse text-[13px]">
                    <tbody>
                        <tr className="border-t border-[var(--portal-border)]">
                            <td className="py-2 text-center text-[15px] font-[900]">
                                {session.score_a ?? 0}
                            </td>
                            <td className="py-2 text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">
                                Points
                            </td>
                            <td className="py-2 text-center text-[15px] font-[900]">
                                {session.score_b ?? 0}
                            </td>
                        </tr>
                        <tr className="border-t border-[var(--portal-border)]">
                            <td className="py-2 text-center text-[15px] font-[900]">
                                {foulsA}
                            </td>
                            <td className="py-2 text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">
                                Fouls
                            </td>
                            <td className="py-2 text-center text-[15px] font-[900]">
                                {foulsB}
                            </td>
                        </tr>
                        {showTimeouts && (
                            <tr className="border-t border-[var(--portal-border)]">
                                <td className="py-2 text-center text-[15px] font-[900]">
                                    {timeoutsA}
                                </td>
                                <td className="py-2 text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">
                                    Timeouts
                                </td>
                                <td className="py-2 text-center text-[15px] font-[900]">
                                    {timeoutsB}
                                </td>
                            </tr>
                        )}
                        {teamStats && (
                            <>
                                <tr className="border-t border-[var(--portal-border)]">
                                    <td className="py-2 text-center text-[15px] font-[900]">
                                        {shootingLine(
                                            teamStats.a.fg_made,
                                            teamStats.a.fg_att,
                                        )}
                                    </td>
                                    <td className="py-2 text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">
                                        Field Goals
                                    </td>
                                    <td className="py-2 text-center text-[15px] font-[900]">
                                        {shootingLine(
                                            teamStats.b.fg_made,
                                            teamStats.b.fg_att,
                                        )}
                                    </td>
                                </tr>
                                <tr className="border-t border-[var(--portal-border)]">
                                    <td className="py-2 text-center text-[15px] font-[900]">
                                        {shootingLine(
                                            teamStats.a.three_made,
                                            teamStats.a.three_att,
                                        )}
                                    </td>
                                    <td className="py-2 text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">
                                        3-PT Field Goals
                                    </td>
                                    <td className="py-2 text-center text-[15px] font-[900]">
                                        {shootingLine(
                                            teamStats.b.three_made,
                                            teamStats.b.three_att,
                                        )}
                                    </td>
                                </tr>
                                <tr className="border-t border-[var(--portal-border)]">
                                    <td className="py-2 text-center text-[15px] font-[900]">
                                        {shootingLine(
                                            teamStats.a.ft_made,
                                            teamStats.a.ft_att,
                                        )}
                                    </td>
                                    <td className="py-2 text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">
                                        Free Throws
                                    </td>
                                    <td className="py-2 text-center text-[15px] font-[900]">
                                        {shootingLine(
                                            teamStats.b.ft_made,
                                            teamStats.b.ft_att,
                                        )}
                                    </td>
                                </tr>
                                <tr className="border-t border-[var(--portal-border)]">
                                    <td className="py-2 text-center text-[15px] font-[900]">
                                        {teamStats.a.rebounds}
                                    </td>
                                    <td className="py-2 text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">
                                        Rebounds
                                    </td>
                                    <td className="py-2 text-center text-[15px] font-[900]">
                                        {teamStats.b.rebounds}
                                    </td>
                                </tr>
                                <tr className="border-t border-[var(--portal-border)]">
                                    <td className="py-2 text-center text-[15px] font-[900]">
                                        {teamStats.a.assists}
                                    </td>
                                    <td className="py-2 text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">
                                        Assists
                                    </td>
                                    <td className="py-2 text-center text-[15px] font-[900]">
                                        {teamStats.b.assists}
                                    </td>
                                </tr>
                                <tr className="border-t border-[var(--portal-border)]">
                                    <td className="py-2 text-center text-[15px] font-[900]">
                                        {teamStats.a.turnovers}
                                    </td>
                                    <td className="py-2 text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">
                                        Turnovers
                                    </td>
                                    <td className="py-2 text-center text-[15px] font-[900]">
                                        {teamStats.b.turnovers}
                                    </td>
                                </tr>
                                <tr className="border-t border-[var(--portal-border)]">
                                    <td className="py-2 text-center text-[15px] font-[900]">
                                        {teamStats.a.steals}
                                    </td>
                                    <td className="py-2 text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">
                                        Steals
                                    </td>
                                    <td className="py-2 text-center text-[15px] font-[900]">
                                        {teamStats.b.steals}
                                    </td>
                                </tr>
                                <tr className="border-t border-[var(--portal-border)]">
                                    <td className="py-2 text-center text-[15px] font-[900]">
                                        {teamStats.a.blocks}
                                    </td>
                                    <td className="py-2 text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">
                                        Blocks
                                    </td>
                                    <td className="py-2 text-center text-[15px] font-[900]">
                                        {teamStats.b.blocks}
                                    </td>
                                </tr>
                            </>
                        )}
                    </tbody>
                </table>
            </article>

            <article className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)]">
                <div className="px-[18px] pt-4 pb-2.5 text-sm font-[950] text-[var(--portal-fg)] uppercase">
                    Top Performers
                </div>

                {boxScore ? (
                    <>
                        <div className="grid grid-cols-2">
                            <span className="bg-[var(--portal-maroon)] py-[9px] text-center text-xs font-[900] text-[var(--portal-maroon-foreground)] uppercase">
                                {sideALabel}
                            </span>
                            <span className="bg-[var(--portal-ink)] py-[9px] text-center text-xs font-[900] text-[var(--portal-ink-foreground)] uppercase">
                                {sideBLabel}
                            </span>
                        </div>
                        <div className="grid grid-cols-2">
                            <div className="border-r border-[var(--portal-border)] p-3.5">
                                {boxScore.a.map((performer) => (
                                    <div
                                        key={performer.number}
                                        className="my-3 grid grid-cols-[46px_1fr] items-center gap-2.5"
                                    >
                                        <span
                                            aria-hidden="true"
                                            className="relative flex size-[46px] items-center justify-center rounded-full bg-gradient-to-br from-[var(--portal-accent-soft)] to-[var(--portal-muted)] text-xl"
                                        >
                                            👤
                                            <span className="absolute -right-1.5 -bottom-1.5 flex size-5 items-center justify-center rounded-full bg-[var(--portal-maroon)] text-[10px] font-[900] text-[var(--portal-maroon-foreground)]">
                                                {performer.number}
                                            </span>
                                        </span>
                                        <div>
                                            <strong className="block text-xs font-bold">
                                                {performer.name}
                                            </strong>
                                            <small className="text-[10px] font-bold text-[var(--portal-muted-foreground)]">
                                                {performer.pts} PTS |{' '}
                                                {performer.reb} REB |{' '}
                                                {performer.ast} AST
                                            </small>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <div className="p-3.5">
                                {boxScore.b.map((performer) => (
                                    <div
                                        key={performer.number}
                                        className="my-3 grid grid-cols-[46px_1fr] items-center gap-2.5"
                                    >
                                        <span
                                            aria-hidden="true"
                                            className="relative flex size-[46px] items-center justify-center rounded-full bg-gradient-to-br from-[var(--portal-accent-soft)] to-[var(--portal-muted)] text-xl"
                                        >
                                            👤
                                            <span className="absolute -right-1.5 -bottom-1.5 flex size-5 items-center justify-center rounded-full bg-[var(--portal-ink)] text-[10px] font-[900] text-[var(--portal-ink-foreground)]">
                                                {performer.number}
                                            </span>
                                        </span>
                                        <div>
                                            <strong className="block text-xs font-bold">
                                                {performer.name}
                                            </strong>
                                            <small className="text-[10px] font-bold text-[var(--portal-muted-foreground)]">
                                                {performer.pts} PTS |{' '}
                                                {performer.reb} REB |{' '}
                                                {performer.ast} AST
                                            </small>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </>
                ) : (
                    <PortalEmptyState
                        icon={Users}
                        tone="ink"
                        title="No performer stats yet"
                        description="Per-player stat lines aren't tracked for this game."
                        className="mx-4 mb-4 rounded-[var(--portal-radius)]"
                    />
                )}
            </article>
        </aside>
    );
}
