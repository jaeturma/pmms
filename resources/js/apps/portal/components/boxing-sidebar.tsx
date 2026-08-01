import { ShieldAlert, User, Users } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { accuracy, knockdownCount, readBoutMeta, readBoutStats, readOfficials, readRounds, readWarnings } from '@/apps/portal/lib/boxing-state';
import { cn } from '@/apps/portal/lib/utils';
import type { PortalLiveNow } from '@/apps/portal/types';

type PortalBoxingSidebarProps = {
    liveNow: PortalLiveNow;
};

export function PortalBoxingSidebar({ liveNow }: PortalBoxingSidebarProps) {
    const { session } = liveNow;
    const rounds = readRounds(session.sport_state);
    const boutMeta = readBoutMeta(session.sport_state);
    const boutStats = readBoutStats(session.sport_state);
    const warnings = readWarnings(session.sport_state);
    const officials = readOfficials(session.sport_state);
    const kdA = knockdownCount(session.sport_state, 'knockdowns_a');
    const kdB = knockdownCount(session.sport_state, 'knockdowns_b');
    const showKnockdowns = kdA !== undefined && kdB !== undefined;

    const sideALabel = session.side_a_label ?? 'TBD';
    const sideBLabel = session.side_b_label ?? 'TBD';

    return (
        <aside className="flex flex-col gap-3.5">
            <article className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)]">
                <div className="bg-[var(--portal-ink)] px-[15px] py-2.5 text-sm font-[900] text-[var(--portal-ink-foreground)] uppercase">
                    Bout Information
                </div>
                <dl className="divide-y divide-[var(--portal-border)] text-[12px]">
                    <div className="grid grid-cols-[110px_1fr] gap-3 px-[15px] py-2.5">
                        <dt className="text-[10px] font-[850] text-[var(--portal-muted-foreground)] uppercase">Division</dt>
                        <dd className="font-[750]">{liveNow.category}</dd>
                    </div>
                    {boutMeta?.weight_class && (
                        <div className="grid grid-cols-[110px_1fr] gap-3 px-[15px] py-2.5">
                            <dt className="text-[10px] font-[850] text-[var(--portal-muted-foreground)] uppercase">Weight Class</dt>
                            <dd className="font-[750]">{boutMeta.weight_class}</dd>
                        </div>
                    )}
                    {liveNow.round_label && (
                        <div className="grid grid-cols-[110px_1fr] gap-3 px-[15px] py-2.5">
                            <dt className="text-[10px] font-[850] text-[var(--portal-muted-foreground)] uppercase">Stage</dt>
                            <dd className="font-[750]">{liveNow.round_label}</dd>
                        </div>
                    )}
                    <div className="grid grid-cols-[110px_1fr] gap-3 px-[15px] py-2.5">
                        <dt className="text-[10px] font-[850] text-[var(--portal-muted-foreground)] uppercase">Rounds</dt>
                        <dd className="font-[750]">{boutMeta?.rounds_format ?? `${rounds.length || '—'} scored`}</dd>
                    </div>
                    {(boutMeta?.ring ?? liveNow.venue) && (
                        <div className="grid grid-cols-[110px_1fr] gap-3 px-[15px] py-2.5">
                            <dt className="text-[10px] font-[850] text-[var(--portal-muted-foreground)] uppercase">Ring</dt>
                            <dd className="font-[750]">{boutMeta?.ring ?? liveNow.venue}</dd>
                        </div>
                    )}
                </dl>
            </article>

            <article className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)]">
                <div className="bg-[var(--portal-ink)] px-[15px] py-2.5 text-sm font-[900] text-[var(--portal-ink-foreground)] uppercase">
                    Bout Statistics
                </div>

                {boutStats || showKnockdowns ? (
                    <div className="px-[15px] pt-[9px] pb-[13px]">
                        <div className="mb-1.5 grid grid-cols-2 text-xs font-[900]">
                            <span className="text-[var(--portal-maroon)] uppercase">{sideALabel}</span>
                            <span className="text-right text-[var(--portal-accent)] uppercase">{sideBLabel}</span>
                        </div>
                        <table className="w-full border-collapse text-xs">
                            <tbody>
                                {boutStats && (
                                    <>
                                        <tr className="border-t border-[var(--portal-border)]">
                                            <td className="py-2 text-center text-sm font-[900]">{boutStats.a.punches_landed}</td>
                                            <td className="py-2 text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">
                                                Punches Landed
                                            </td>
                                            <td className="py-2 text-center text-sm font-[900]">{boutStats.b.punches_landed}</td>
                                        </tr>
                                        <tr className="border-t border-[var(--portal-border)]">
                                            <td className="py-2 text-center text-sm font-[900]">{boutStats.a.punches_thrown}</td>
                                            <td className="py-2 text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">
                                                Punches Thrown
                                            </td>
                                            <td className="py-2 text-center text-sm font-[900]">{boutStats.b.punches_thrown}</td>
                                        </tr>
                                        <tr className="border-t border-[var(--portal-border)]">
                                            <td className="py-2 text-center text-sm font-[900]">
                                                {accuracy(boutStats.a.punches_landed, boutStats.a.punches_thrown)}
                                            </td>
                                            <td className="py-2 text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">Accuracy</td>
                                            <td className="py-2 text-center text-sm font-[900]">
                                                {accuracy(boutStats.b.punches_landed, boutStats.b.punches_thrown)}
                                            </td>
                                        </tr>
                                    </>
                                )}
                                {showKnockdowns && (
                                    <tr className="border-t border-[var(--portal-border)]">
                                        <td className="py-2 text-center text-sm font-[900]">{kdA}</td>
                                        <td className="py-2 text-center text-[11px] text-[var(--portal-muted-foreground)] uppercase">Knockdowns</td>
                                        <td className="py-2 text-center text-sm font-[900]">{kdB}</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <PortalEmptyState
                        icon={ShieldAlert}
                        tone="ink"
                        title="Punch stats not tracked"
                        description="Punches landed/thrown aren't tracked for this bout."
                        className="mx-[15px] mb-[13px] rounded-[var(--portal-radius)]"
                    />
                )}
            </article>

            <article className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)]">
                <div className="bg-[var(--portal-ink)] px-[15px] py-2.5 text-sm font-[900] text-[var(--portal-ink-foreground)] uppercase">
                    Warnings and Deductions
                </div>

                {warnings ? (
                    <div className="px-[15px] pt-2 pb-[13px] text-xs">
                        <div className="flex items-center justify-between border-b border-[var(--portal-muted)] py-2.5">
                            <span className="font-[750]">{sideALabel}</span>
                            <span
                                className={cn(
                                    'rounded-full px-2.5 py-1 text-[10px] font-[900] uppercase',
                                    warnings.warnings_a > 0
                                        ? 'bg-[var(--portal-maroon)] text-[var(--portal-maroon-foreground)]'
                                        : 'bg-[var(--portal-accent)] text-[var(--portal-accent-foreground)]',
                                )}
                            >
                                {warnings.warnings_a > 0 ? `${warnings.warnings_a} Warning${warnings.warnings_a === 1 ? '' : 's'}` : 'No Warning'}
                            </span>
                        </div>
                        <div className="flex items-center justify-between border-b border-[var(--portal-muted)] py-2.5">
                            <span className="font-[750]">{sideBLabel}</span>
                            <span
                                className={cn(
                                    'rounded-full px-2.5 py-1 text-[10px] font-[900] uppercase',
                                    warnings.warnings_b > 0
                                        ? 'bg-[var(--portal-maroon)] text-[var(--portal-maroon-foreground)]'
                                        : 'bg-[var(--portal-accent)] text-[var(--portal-accent-foreground)]',
                                )}
                            >
                                {warnings.warnings_b > 0 ? `${warnings.warnings_b} Warning${warnings.warnings_b === 1 ? '' : 's'}` : 'No Warning'}
                            </span>
                        </div>
                        <div className="flex items-center justify-between py-2.5">
                            <span className="font-[750]">Point Deductions</span>
                            <span
                                className={cn(
                                    'rounded-full px-2.5 py-1 text-[10px] font-[900] uppercase',
                                    warnings.deductions > 0
                                        ? 'bg-[var(--portal-maroon)] text-[var(--portal-maroon-foreground)]'
                                        : 'bg-[var(--portal-accent)] text-[var(--portal-accent-foreground)]',
                                )}
                            >
                                {warnings.deductions > 0 ? warnings.deductions : 'None'}
                            </span>
                        </div>
                    </div>
                ) : (
                    <PortalEmptyState
                        icon={ShieldAlert}
                        tone="ink"
                        title="No warnings on record"
                        description="Warnings and point deductions aren't tracked for this bout."
                        className="mx-[15px] mb-[13px] rounded-[var(--portal-radius)]"
                    />
                )}
            </article>

            <article className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)]">
                <div className="bg-[var(--portal-ink)] px-[15px] py-2.5 text-sm font-[900] text-[var(--portal-ink-foreground)] uppercase">
                    Ring Officials
                </div>

                {officials ? (
                    <div className="px-[15px] pt-1 pb-[13px]">
                        {officials.map((official) => (
                            <div
                                key={official.name}
                                className="grid grid-cols-[42px_1fr] items-center gap-2.5 border-b border-[var(--portal-muted)] py-2 last:border-b-0"
                            >
                                <span
                                    aria-hidden="true"
                                    className="flex size-[42px] items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-[var(--portal-accent-soft)] to-[var(--portal-muted)] shadow-[0_0_0_1px_var(--portal-border)]"
                                >
                                    <User className="size-5 text-[var(--portal-muted-foreground)]" aria-hidden="true" />
                                </span>
                                <div>
                                    <strong className="block text-[11px]">{official.name}</strong>
                                    <small className="text-[10px] font-[650] text-[var(--portal-muted-foreground)]">{official.role}</small>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <PortalEmptyState
                        icon={Users}
                        tone="ink"
                        title="No officials on record"
                        description="Referee and judge assignments aren't published for this bout."
                        className="mx-[15px] mb-[13px] rounded-[var(--portal-radius)]"
                    />
                )}
            </article>
        </aside>
    );
}
