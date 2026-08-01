import { BarChart3, User, Users } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { readBoxScore, readPitchers, readTeamStats } from '@/apps/portal/lib/softball-state';
import type { PortalLiveSession } from '@/apps/portal/types';

type PortalSoftballSidebarProps = {
    session: PortalLiveSession;
};

export function PortalSoftballSidebar({ session }: PortalSoftballSidebarProps) {
    const teamStats = readTeamStats(session.sport_state);
    const boxScore = readBoxScore(session.sport_state);
    const pitchers = readPitchers(session.sport_state);
    const sideALabel = session.side_a_label ?? 'TBD';
    const sideBLabel = session.side_b_label ?? 'TBD';

    return (
        <aside className="flex flex-col gap-3.5">
            <article className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)]">
                <div className="bg-[var(--portal-ink)] px-[15px] py-2.5 text-sm font-[900] text-[var(--portal-ink-foreground)] uppercase">
                    Team Comparison
                </div>

                <div className="grid grid-cols-2 px-[18px] pt-[9px] pb-[7px] text-sm font-[900]">
                    <span className="text-[var(--portal-maroon)] uppercase">{sideALabel}</span>
                    <span className="text-right text-[var(--portal-ink)] uppercase">{sideBLabel}</span>
                </div>
                <table className="mx-[15px] mb-[13px] w-[calc(100%-1.875rem)] border-collapse text-xs">
                    <tbody>
                        <tr className="border-t border-[var(--portal-border)]">
                            <td className="w-[30%] py-[7px] text-center text-sm font-[900]">{session.score_a ?? 0}</td>
                            <td className="py-[7px] text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">Runs</td>
                            <td className="w-[30%] py-[7px] text-center text-sm font-[900]">{session.score_b ?? 0}</td>
                        </tr>
                        {teamStats && (
                            <>
                                <tr className="border-t border-[var(--portal-border)]">
                                    <td className="py-[7px] text-center text-sm font-[900]">{teamStats.a.walks}</td>
                                    <td className="py-[7px] text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">Walks</td>
                                    <td className="py-[7px] text-center text-sm font-[900]">{teamStats.b.walks}</td>
                                </tr>
                                <tr className="border-t border-[var(--portal-border)]">
                                    <td className="py-[7px] text-center text-sm font-[900]">{teamStats.a.strikeouts}</td>
                                    <td className="py-[7px] text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">Strikeouts</td>
                                    <td className="py-[7px] text-center text-sm font-[900]">{teamStats.b.strikeouts}</td>
                                </tr>
                                <tr className="border-t border-[var(--portal-border)]">
                                    <td className="py-[7px] text-center text-sm font-[900]">{teamStats.a.stolen_bases}</td>
                                    <td className="py-[7px] text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">Stolen Bases</td>
                                    <td className="py-[7px] text-center text-sm font-[900]">{teamStats.b.stolen_bases}</td>
                                </tr>
                                <tr className="border-t border-[var(--portal-border)]">
                                    <td className="py-[7px] text-center text-sm font-[900]">{teamStats.a.batting_avg}</td>
                                    <td className="py-[7px] text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">Batting Avg</td>
                                    <td className="py-[7px] text-center text-sm font-[900]">{teamStats.b.batting_avg}</td>
                                </tr>
                                <tr className="border-t border-[var(--portal-border)]">
                                    <td className="py-[7px] text-center text-sm font-[900]">{teamStats.a.slugging_pct}</td>
                                    <td className="py-[7px] text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">Slugging %</td>
                                    <td className="py-[7px] text-center text-sm font-[900]">{teamStats.b.slugging_pct}</td>
                                </tr>
                            </>
                        )}
                    </tbody>
                </table>
                {!teamStats && (
                    <PortalEmptyState
                        icon={BarChart3}
                        tone="ink"
                        title="Batting stats not tracked"
                        description="Walks, strikeouts, and averages aren't tracked for this game."
                        className="mx-[15px] mb-[13px] rounded-[var(--portal-radius)]"
                    />
                )}
            </article>

            <article className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)]">
                <div className="bg-[var(--portal-ink)] px-[15px] py-2.5 text-sm font-[900] text-[var(--portal-ink-foreground)] uppercase">
                    Top Performers
                </div>

                {boxScore ? (
                    <div className="grid grid-cols-2">
                        <span className="bg-[var(--portal-maroon)] py-2 text-center text-xs font-[900] text-[var(--portal-maroon-foreground)] uppercase">
                            {sideALabel}
                        </span>
                        <span className="bg-[var(--portal-ink)] py-2 text-center text-xs font-[900] text-[var(--portal-ink-foreground)] uppercase">
                            {sideBLabel}
                        </span>
                        <div className="border-r border-[var(--portal-border)] px-3 py-1.5">
                            {boxScore.a.map((performer) => (
                                <div
                                    key={performer.number}
                                    className="grid grid-cols-[46px_1fr] items-center gap-2.5 border-b border-[var(--portal-muted)] py-2 last:border-b-0"
                                >
                                    <span
                                        aria-hidden="true"
                                        className="flex size-[46px] items-end justify-center overflow-hidden rounded-full border-2 border-[var(--portal-surface)] bg-gradient-to-br from-[var(--portal-accent-soft)] to-[var(--portal-muted)] text-2xl shadow-[0_0_0_1px_var(--portal-border)]"
                                    >
                                        <User className="size-6 text-[var(--portal-muted-foreground)]" aria-hidden="true" />
                                    </span>
                                    <div>
                                        <strong className="block text-[11px]">
                                            #{performer.number} {performer.name}
                                        </strong>
                                        <small className="text-[10px] font-[650] text-[var(--portal-muted-foreground)]">{performer.line}</small>
                                    </div>
                                </div>
                            ))}
                        </div>
                        <div className="px-3 py-1.5">
                            {boxScore.b.map((performer) => (
                                <div
                                    key={performer.number}
                                    className="grid grid-cols-[46px_1fr] items-center gap-2.5 border-b border-[var(--portal-muted)] py-2 last:border-b-0"
                                >
                                    <span
                                        aria-hidden="true"
                                        className="flex size-[46px] items-end justify-center overflow-hidden rounded-full border-2 border-[var(--portal-surface)] bg-gradient-to-br from-[var(--portal-accent-soft)] to-[var(--portal-muted)] text-2xl shadow-[0_0_0_1px_var(--portal-border)]"
                                    >
                                        <User className="size-6 text-[var(--portal-muted-foreground)]" aria-hidden="true" />
                                    </span>
                                    <div>
                                        <strong className="block text-[11px]">
                                            #{performer.number} {performer.name}
                                        </strong>
                                        <small className="text-[10px] font-[650] text-[var(--portal-muted-foreground)]">{performer.line}</small>
                                    </div>
                                </div>
                            ))}
                        </div>
                        <div className="col-span-2 flex items-center justify-between border-t border-[var(--portal-border)] px-3 py-2 text-[11px] font-[750] text-[var(--portal-muted-foreground)]">
                            <span>Full box score isn't published for this game</span>
                        </div>
                    </div>
                ) : (
                    <PortalEmptyState
                        icon={Users}
                        tone="ink"
                        title="No performer stats yet"
                        description="Per-player batting lines aren't tracked for this game."
                        className="mx-[15px] mb-[13px] rounded-[var(--portal-radius)]"
                    />
                )}
            </article>

            <article className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)]">
                <div className="bg-[var(--portal-ink)] px-[15px] py-2.5 text-sm font-[900] text-[var(--portal-ink-foreground)] uppercase">
                    Current Pitcher
                </div>

                {pitchers ? (
                    <div className="grid grid-cols-2">
                        <div className="border-r border-[var(--portal-border)] p-3.5">
                            <div className="mb-1.5 text-xs font-[900] text-[var(--portal-maroon)]">{sideALabel}</div>
                            <div className="grid grid-cols-[46px_1fr] items-center gap-2.5">
                                <span
                                    aria-hidden="true"
                                    className="flex size-[46px] items-end justify-center overflow-hidden rounded-full border-2 border-[var(--portal-surface)] bg-gradient-to-br from-[var(--portal-accent-soft)] to-[var(--portal-muted)] shadow-[0_0_0_1px_var(--portal-border)]"
                                >
                                    <User className="size-6 text-[var(--portal-muted-foreground)]" aria-hidden="true" />
                                </span>
                                <div>
                                    <strong className="block text-[11px]">
                                        #{pitchers.a.number} {pitchers.a.name}
                                    </strong>
                                    <small className="block text-[10px] leading-[1.55] text-[var(--portal-muted-foreground)]">
                                        {pitchers.a.throws} | {pitchers.a.line}
                                        <br />
                                        Pitches: {pitchers.a.pitches}
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div className="p-3.5">
                            <div className="mb-1.5 text-xs font-[900] text-[var(--portal-ink)]">{sideBLabel}</div>
                            <div className="grid grid-cols-[46px_1fr] items-center gap-2.5">
                                <span
                                    aria-hidden="true"
                                    className="flex size-[46px] items-end justify-center overflow-hidden rounded-full border-2 border-[var(--portal-surface)] bg-gradient-to-br from-[var(--portal-accent-soft)] to-[var(--portal-muted)] shadow-[0_0_0_1px_var(--portal-border)]"
                                >
                                    <User className="size-6 text-[var(--portal-muted-foreground)]" aria-hidden="true" />
                                </span>
                                <div>
                                    <strong className="block text-[11px]">
                                        #{pitchers.b.number} {pitchers.b.name}
                                    </strong>
                                    <small className="block text-[10px] leading-[1.55] text-[var(--portal-muted-foreground)]">
                                        {pitchers.b.throws} | {pitchers.b.line}
                                        <br />
                                        Pitches: {pitchers.b.pitches}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                ) : (
                    <PortalEmptyState
                        icon={Users}
                        tone="ink"
                        title="No pitcher on record"
                        description="Current pitcher details aren't tracked for this game."
                        className="mx-[15px] mb-[13px] rounded-[var(--portal-radius)]"
                    />
                )}
            </article>
        </aside>
    );
}
