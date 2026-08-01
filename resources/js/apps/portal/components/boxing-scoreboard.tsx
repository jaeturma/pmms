import { ClipboardList } from 'lucide-react';
import { useEffect, useState } from 'react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { initialsFor } from '@/apps/portal/components/municipality-crest';
import { knockdownCount, readJudges, readRounds, totalRounds } from '@/apps/portal/lib/boxing-state';
import { cn } from '@/apps/portal/lib/utils';
import type { PortalLiveNow, PortalPlayByPlayEntry } from '@/apps/portal/types';

type PortalBoxingScoreboardProps = {
    liveNow: PortalLiveNow;
    className?: string;
};

function formatClock(totalSeconds: number): string {
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

/** `docs/ui-ux/boxing.html`'s per-event icon, mapped from our real
 * `ScoreEvent` types — the only events a boxing session ever logs are
 * round scores, so every row gets the same glove glyph. */
function playIcon(): string {
    return '🥊';
}

/** The athlete's sports/action photo when one is on file for this bout's
 * corner (`ScoringSession::athleteParticipants()` — only resolvable for a
 * one-on-one individual entry) — falls back to an initials badge from the
 * corner's team/delegation label otherwise, same as basketball/softball's
 * crest-or-initials fallback. */
function CornerPhoto({ name, corner, photoUrl }: { name: string; corner: 'red' | 'blue'; photoUrl?: string | null }) {
    if (photoUrl) {
        return (
            <img
                src={photoUrl}
                alt={`${name} — sports photo`}
                className={cn(
                    'size-[84px] shrink-0 rounded-2xl border-4 border-[var(--portal-surface)] object-cover shadow-[0_3px_12px_rgba(0,0,0,0.18)] sm:size-[105px]',
                    corner === 'red' ? 'shadow-[0_0_0_3px_rgba(223,32,41,0.3)]' : 'shadow-[0_0_0_3px_rgba(12,86,216,0.3)]',
                )}
            />
        );
    }

    return (
        <span
            aria-hidden="true"
            className={cn(
                'flex size-[84px] shrink-0 items-center justify-center rounded-2xl border-4 border-[var(--portal-surface)] text-lg font-black text-[var(--portal-ink-foreground)] shadow-[0_3px_12px_rgba(0,0,0,0.18)] sm:size-[105px] sm:text-2xl',
                corner === 'red' ? 'shadow-[0_0_0_3px_rgba(223,32,41,0.3)]' : 'shadow-[0_0_0_3px_rgba(12,86,216,0.3)]',
            )}
            style={{
                background:
                    corner === 'red'
                        ? 'linear-gradient(135deg, var(--portal-maroon), var(--portal-accent))'
                        : 'linear-gradient(135deg, var(--portal-ink), var(--portal-accent))',
            }}
        >
            {initialsFor(name)}
        </span>
    );
}

function PlayRow({ play, highlight }: { play: PortalPlayByPlayEntry; highlight: boolean }) {
    return (
        <div
            className={cn(
                'grid grid-cols-[64px_28px_1fr_74px] items-center gap-3 border-b border-[var(--portal-border)] px-4 py-2.5 sm:grid-cols-[74px_34px_1fr_90px] sm:px-[18px] sm:py-2.5',
                highlight && 'bg-gradient-to-r from-[var(--portal-accent-soft)] to-transparent',
            )}
        >
            <span className={cn('text-xs font-[850] sm:text-sm', highlight && 'text-[var(--portal-maroon)]')}>{play.created_at}</span>
            <span aria-hidden="true">{playIcon()}</span>
            <span className="truncate text-sm">{play.description}</span>
            <span className={cn('text-right font-mono text-base font-[950] tabular-nums sm:text-[18px]', highlight && 'text-[var(--portal-maroon)]')}>
                {play.score_a}–{play.score_b}
            </span>
        </div>
    );
}

export function PortalBoxingScoreboard({ liveNow, className }: PortalBoxingScoreboardProps) {
    const { session } = liveNow;
    const [elapsed, setElapsed] = useState(session.elapsed_seconds);

    useEffect(() => {
        setElapsed(session.elapsed_seconds);

        if (!session.clock_running) {
            return;
        }

        const interval = setInterval(() => setElapsed((value) => value + 1), 1000);

        return () => clearInterval(interval);
    }, [session.elapsed_seconds, session.clock_running]);

    const rounds = readRounds(session.sport_state);
    const roundsTotal = totalRounds(session.sport_state);
    const judges = readJudges(session.sport_state);
    const kdA = knockdownCount(session.sport_state, 'knockdowns_a');
    const kdB = knockdownCount(session.sport_state, 'knockdowns_b');
    const showKnockdowns = kdA !== undefined && kdB !== undefined;

    const sideALabel = session.side_a_label ?? 'TBD';
    const sideBLabel = session.side_b_label ?? 'TBD';
    const athleteA = session.side_a_athlete;
    const athleteB = session.side_b_athlete;

    const roundBadge = roundsTotal
        ? `${(session.period_label ?? 'ROUND').toUpperCase()} OF ${roundsTotal}`
        : (session.period_label ?? session.status_label).toUpperCase();

    return (
        <div className={cn('flex flex-col gap-3.5', className)}>
            <div className="portal-animate-in relative overflow-hidden rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] text-[var(--portal-surface-foreground)] shadow-[0_6px_24px_rgba(15,23,42,0.08)]">
                <div className="flex items-center justify-between gap-2 bg-[var(--portal-live)] px-4 py-2 text-[var(--portal-live-foreground)]">
                    <span className="flex items-center gap-1.5 text-xs font-semibold tracking-wide uppercase">
                        <span aria-hidden="true" className="portal-live-dot size-2 rounded-full bg-current" />
                        {session.status === 'ended' ? session.status_label : 'Live now'}
                    </span>
                    <span className="text-xs">
                        {liveNow.category}
                        {liveNow.round_label ? ` · ${liveNow.round_label}` : ''}
                    </span>
                </div>

                <div className="px-4 py-6 sm:px-7">
                    <div className="grid grid-cols-[1fr_106px_1fr] items-center gap-2 sm:grid-cols-[1fr_180px_1fr] sm:gap-[26px]">
                        <div className="flex flex-col items-center gap-2 text-center sm:grid sm:grid-cols-[92px_1fr] sm:items-center sm:gap-4 sm:text-left">
                            <CornerPhoto name={athleteA?.name ?? sideALabel} corner="red" photoUrl={athleteA?.sports_photo_url} />
                            <div>
                                <span className="inline-flex rounded-full bg-[var(--portal-maroon)] px-2.5 py-[3px] text-[10px] font-[950] text-[var(--portal-maroon-foreground)] uppercase">
                                    Red Corner
                                </span>
                                <p className="mt-1.5 text-sm font-[950] uppercase sm:text-lg">{athleteA?.name ?? sideALabel}</p>
                                {athleteA && (
                                    <div className="mt-1 flex items-center justify-center gap-1.5 sm:justify-start">
                                        {session.side_a_logo_url && (
                                            <img
                                                src={session.side_a_logo_url}
                                                alt=""
                                                aria-hidden="true"
                                                className="size-4 shrink-0 rounded-full object-cover"
                                            />
                                        )}
                                        <span className="text-xs font-[750] text-[var(--portal-muted-foreground)]">{sideALabel}</span>
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="flex flex-col items-center gap-2">
                            <span className="rounded-[8px] bg-[var(--portal-ink)] px-3 py-2 text-xs font-[900] text-[var(--portal-ink-foreground)] sm:text-[15px]">
                                {roundBadge}
                            </span>
                            {session.started_at && (
                                <span className="rounded-[10px] border border-[var(--portal-border)] bg-[var(--portal-muted)] px-[9px] pt-[3px] pb-[7px] font-mono text-[28px] font-[950] text-[var(--portal-ink)] tabular-nums sm:px-[18px] sm:text-[46px]">
                                    {formatClock(elapsed)}
                                </span>
                            )}
                            <span className="text-[11px] font-[900] text-[var(--portal-maroon)]">
                                {session.status === 'ended' ? session.status_label.toUpperCase() : '● BOUT IN PROGRESS'}
                            </span>
                        </div>

                        <div className="flex flex-col items-center gap-2 text-center sm:grid sm:grid-cols-[1fr_92px] sm:items-center sm:gap-4 sm:text-right">
                            <div className="sm:order-2">
                                <CornerPhoto name={athleteB?.name ?? sideBLabel} corner="blue" photoUrl={athleteB?.sports_photo_url} />
                            </div>
                            <div className="sm:order-1">
                                <span className="inline-flex rounded-full bg-[var(--portal-accent)] px-2.5 py-[3px] text-[10px] font-[950] text-[var(--portal-accent-foreground)] uppercase">
                                    Blue Corner
                                </span>
                                <p className="mt-1.5 text-sm font-[950] uppercase sm:text-lg">{athleteB?.name ?? sideBLabel}</p>
                                {athleteB && (
                                    <div className="mt-1 flex items-center justify-center gap-1.5 sm:justify-end">
                                        {session.side_b_logo_url && (
                                            <img
                                                src={session.side_b_logo_url}
                                                alt=""
                                                aria-hidden="true"
                                                className="size-4 shrink-0 rounded-full object-cover"
                                            />
                                        )}
                                        <span className="text-xs font-[750] text-[var(--portal-muted-foreground)]">{sideBLabel}</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {rounds.length > 0 && (
                        <div className="mt-[18px] overflow-x-auto">
                            <div className="mx-auto max-w-[520px]">
                                <table className="w-full border-collapse border border-[var(--portal-border)] text-[13px]">
                                    <thead>
                                        <tr className="bg-[var(--portal-muted)] text-[11px] text-[var(--portal-ink)]">
                                            <th className="border border-[var(--portal-border)] px-2.5 py-2 text-left font-semibold">Corner</th>
                                            {rounds.map((round) => (
                                                <th key={round.round} className="border border-[var(--portal-border)] px-2.5 py-2 font-semibold">
                                                    R{round.round}
                                                </th>
                                            ))}
                                            <th className="border border-[var(--portal-border)] px-2.5 py-2 font-semibold">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td className="border border-[var(--portal-border)] px-2.5 py-2 text-left font-bold">{sideALabel}</td>
                                            {rounds.map((round) => (
                                                <td key={round.round} className="border border-[var(--portal-border)] px-2.5 py-2 text-center">
                                                    {round.score_a}
                                                </td>
                                            ))}
                                            <td className="border border-[var(--portal-border)] px-2.5 py-2 text-center text-[17px] font-[950] text-[var(--portal-ink)]">
                                                {session.score_a ?? 0}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="border border-[var(--portal-border)] px-2.5 py-2 text-left font-bold">{sideBLabel}</td>
                                            {rounds.map((round) => (
                                                <td key={round.round} className="border border-[var(--portal-border)] px-2.5 py-2 text-center">
                                                    {round.score_b}
                                                </td>
                                            ))}
                                            <td className="border border-[var(--portal-border)] px-2.5 py-2 text-center text-[17px] font-[950] text-[var(--portal-ink)]">
                                                {session.score_b ?? 0}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                    <div className={cn('mx-auto mt-3 grid max-w-[460px] items-center gap-3', showKnockdowns ? 'grid-cols-[1fr_150px_1fr]' : 'grid-cols-2')}>
                        <div className="rounded-[10px] bg-[var(--portal-maroon)] px-4 py-2.5 text-[var(--portal-maroon-foreground)]">
                            <div className="flex items-center justify-between gap-2">
                                <span className="text-[11px] font-[850] uppercase">Unofficial Total</span>
                                <strong className="text-2xl font-[950] tabular-nums">{session.score_a ?? 0}</strong>
                            </div>
                        </div>
                        {showKnockdowns && (
                            <div className="rounded-[10px] border border-[var(--portal-border)] bg-[var(--portal-muted)] px-3 py-2 text-center">
                                <span className="block text-[10px] font-[850] text-[var(--portal-muted-foreground)] uppercase">Knockdowns</span>
                                <span className="text-xl font-[950] tabular-nums">
                                    {kdA} — {kdB}
                                </span>
                            </div>
                        )}
                        <div className="rounded-[10px] bg-[var(--portal-accent)] px-4 py-2.5 text-[var(--portal-accent-foreground)]">
                            <div className="flex items-center justify-between gap-2">
                                <strong className="text-2xl font-[950] tabular-nums">{session.score_b ?? 0}</strong>
                                <span className="text-[11px] font-[850] uppercase">Unofficial Total</span>
                            </div>
                        </div>
                    </div>

                    {judges && (
                        <div className="mx-auto mt-[18px] grid max-w-[520px] grid-cols-3 gap-2.5">
                            {judges.map((judge) => (
                                <div key={judge.name} className="rounded-[10px] border border-[var(--portal-border)] px-3 py-2.5 text-center">
                                    <strong className="mb-1.5 block text-[11px] text-[var(--portal-ink)]">{judge.name}</strong>
                                    <div className="grid grid-cols-[1fr_auto_1fr] gap-1.5 text-lg font-[950]">
                                        <span className="text-[var(--portal-maroon)]">{judge.red}</span>
                                        <span className="text-[var(--portal-muted-foreground)]">—</span>
                                        <span className="text-[var(--portal-accent)]">{judge.blue}</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    <p className="mx-auto mt-3.5 max-w-[520px] rounded-[9px] bg-[var(--portal-accent-soft)] px-3 py-2.5 text-center text-xs text-[var(--portal-muted-foreground)]">
                        Live judge values are provisional and may be hidden depending on competition rules. Final results appear only after referee
                        and technical-official validation.
                    </p>
                </div>

                {(session.status_note || liveNow.venue) && (
                    <div className="grid grid-cols-[1fr_auto_1fr] items-center gap-2 border-t border-[var(--portal-border)] px-4 py-2 text-xs text-[var(--portal-muted-foreground)]">
                        <span>{session.status_note}</span>
                        {liveNow.venue && <span className="text-center">{liveNow.venue}</span>}
                        <span />
                    </div>
                )}
            </div>

            <div className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)]">
                <div className="border-b border-[var(--portal-border)] bg-[var(--portal-ink)] px-4 py-2.5 text-sm font-[900] text-[var(--portal-ink-foreground)] uppercase">
                    Live Bout Updates
                </div>

                {session.playByPlay.length === 0 ? (
                    <PortalEmptyState
                        icon={ClipboardList}
                        tone="maroon"
                        title="No round scores recorded yet"
                        description="Bout updates appear here as soon as the scoring table logs the first round."
                        className="rounded-none border-0"
                    />
                ) : (
                    <div>
                        {session.playByPlay.map((play, index) => (
                            <PlayRow key={play.id} play={play} highlight={index === 0} />
                        ))}
                        <div className="p-3 text-center text-sm font-[850] text-[var(--portal-ink)]">Showing the most recent rounds</div>
                    </div>
                )}
            </div>
        </div>
    );
}
