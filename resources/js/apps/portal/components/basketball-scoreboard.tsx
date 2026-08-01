import { ClipboardList, ListOrdered, Video } from 'lucide-react';
import { useEffect, useState } from 'react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { initialsFor } from '@/apps/portal/components/municipality-crest';
import { foulCount, readQuarters, shotClockSeconds, timeoutCount } from '@/apps/portal/lib/basketball-state';
import { cn } from '@/apps/portal/lib/utils';
import type { PortalLiveNow, PortalPlayByPlayEntry } from '@/apps/portal/types';

type PortalBasketballScoreboardProps = {
    liveNow: PortalLiveNow;
    className?: string;
};

type BasketballTab = 'plays' | 'box' | 'stats' | 'videos';

const TABS: Array<{ id: BasketballTab; label: string }> = [
    { id: 'plays', label: 'Live play-by-play' },
    { id: 'box', label: 'Box score' },
    { id: 'stats', label: 'Team stats' },
    { id: 'videos', label: 'Videos' },
];

/** `docs/ui-ux/basketball.html`'s per-event icon, mapped from our real
 * `ScoreEvent` types (there is no "shot type" tracked, just point deltas
 * and fouls) rather than fabricating shot detail the log doesn't have. */
function playIcon(description: string): string {
    if (description.startsWith('Foul')) {
        return '🚫';
    }

    if (description.startsWith('+') || description.startsWith('-') || description.startsWith('Correction')) {
        return '🏀';
    }

    return '⏱️';
}

function formatClock(totalSeconds: number): string {
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

/** Renders scoring-table shorthand like "Q3" as "Quarter 3"; any other
 * period/status text (e.g. "Halftime") passes through unchanged. */
function formatPeriodLabel(label: string | null | undefined): string | null | undefined {
    const match = label?.match(/^Q(\d+)$/i);

    return match ? `Quarter ${match[1]}` : label;
}

function TeamLogo({ name, side, logoUrl }: { name: string; side: 'home' | 'away'; logoUrl?: string | null }) {
    if (logoUrl) {
        return (
            <img
                src={logoUrl}
                alt={`${name} crest`}
                className="size-[88px] shrink-0 object-cover sm:size-[128px]"
            />
        );
    }

    return (
        <span
            aria-hidden="true"
            className="flex size-[88px] shrink-0 items-center justify-center rounded-full border-4 border-[var(--portal-surface)] text-lg font-black text-[var(--portal-ink-foreground)] shadow-[0_3px_12px_rgba(0,0,0,0.18)] sm:size-[128px] sm:text-2xl"
            style={{
                background:
                    side === 'home'
                        ? 'linear-gradient(135deg, var(--portal-ink), var(--portal-accent))'
                        : 'linear-gradient(135deg, var(--portal-maroon), var(--portal-accent))',
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
            <span aria-hidden="true">{playIcon(play.description)}</span>
            <span className="truncate text-sm">{play.description}</span>
            <span className={cn('text-right font-mono text-base font-[950] tabular-nums sm:text-[18px]', highlight && 'text-[var(--portal-maroon)]')}>
                {play.score_a}–{play.score_b}
            </span>
        </div>
    );
}

export function PortalBasketballScoreboard({ liveNow, className }: PortalBasketballScoreboardProps) {
    const { session } = liveNow;
    const [elapsed, setElapsed] = useState(session.elapsed_seconds);
    const [tab, setTab] = useState<BasketballTab>('plays');

    const shotClockSeed = shotClockSeconds(session.sport_state);
    const [shotClock, setShotClock] = useState(shotClockSeed ?? 24);

    useEffect(() => {
        setElapsed(session.elapsed_seconds);

        if (!session.clock_running) {
            return;
        }

        const interval = setInterval(() => setElapsed((value) => value + 1), 1000);

        return () => clearInterval(interval);
    }, [session.elapsed_seconds, session.clock_running]);

    // Cosmetic only: PMMS has no real shot-clock reset tied to individual
    // plays, so this just loops 24→0→24 client-side once seeded with a
    // starting value, the same way the reference mockup's own shot clock
    // is decorative chrome rather than backend-driven.
    useEffect(() => {
        if (shotClockSeed === undefined || !session.clock_running) {
            return;
        }

        const interval = setInterval(() => setShotClock((value) => (value <= 0 ? 24 : value - 1)), 1000);

        return () => clearInterval(interval);
    }, [shotClockSeed, session.clock_running]);

    const quarters = readQuarters(session.sport_state);
    const foulsA = foulCount(session.sport_state, 'fouls_a');
    const foulsB = foulCount(session.sport_state, 'fouls_b');
    const timeoutsA = timeoutCount(session.sport_state, 'timeouts_a');
    const timeoutsB = timeoutCount(session.sport_state, 'timeouts_b');
    const showTimeouts = timeoutsA !== undefined && timeoutsB !== undefined;

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
                        <div className="flex flex-col items-center gap-2 text-center sm:grid sm:grid-cols-[112px_1fr] sm:items-center sm:gap-4 sm:text-left">
                            <TeamLogo name={session.side_a_label ?? 'TBD'} side="home" logoUrl={session.side_a_logo_url} />
                            <div>
                                <p className="text-[52px] leading-[0.95] font-[950] tracking-[-2px] tabular-nums sm:text-[82px] sm:tracking-[-4px]">
                                    {session.score_a ?? 0}
                                </p>
                                <p className="text-[13px] font-[950] uppercase sm:text-[22px]">{session.side_a_label ?? 'TBD'}</p>
                            </div>
                        </div>

                        <div className="flex flex-col items-center gap-2">
                            <span className="text-xs font-[900] text-[var(--portal-ink)] sm:text-[17px]">
                                {formatPeriodLabel(session.period_label) ?? session.status_label}
                            </span>
                            {session.started_at && (
                                <span className="rounded-[10px] border border-[var(--portal-border)] bg-[var(--portal-muted)] px-[9px] pt-[3px] pb-[7px] font-mono text-[28px] font-[950] text-[var(--portal-ink)] tabular-nums sm:px-[18px] sm:text-[46px]">
                                    {formatClock(elapsed)}
                                </span>
                            )}
                            {shotClockSeed !== undefined && (
                                <div className="flex flex-col items-center gap-1">
                                    <span className="w-[52px] rounded-[9px] border border-[var(--portal-maroon)]/50 px-2 py-[3px] text-center font-mono text-[24px] font-[950] text-[var(--portal-maroon)] tabular-nums sm:w-[70px] sm:text-[34px]">
                                        {shotClock}
                                    </span>
                                    <span className="text-[10px] font-[900] text-[var(--portal-muted-foreground)] sm:text-xs">SHOT CLOCK</span>
                                </div>
                            )}
                        </div>

                        <div className="flex flex-col items-center gap-2 text-center sm:grid sm:grid-cols-[1fr_112px] sm:items-center sm:gap-4 sm:text-right">
                            <div className="sm:order-2">
                                <TeamLogo name={session.side_b_label ?? 'TBD'} side="away" logoUrl={session.side_b_logo_url} />
                            </div>
                            <div className="sm:order-1">
                                <p className="text-[52px] leading-[0.95] font-[950] tracking-[-2px] tabular-nums sm:text-[82px] sm:tracking-[-4px]">
                                    {session.score_b ?? 0}
                                </p>
                                <p className="text-[13px] font-[950] uppercase sm:text-[22px]">{session.side_b_label ?? 'TBD'}</p>
                            </div>
                        </div>
                    </div>

                    {quarters && (
                        <div className="mt-[18px] overflow-x-auto">
                            <div className="mx-auto max-w-[520px]">
                                <table className="w-full border-collapse border border-[var(--portal-border)] text-[13px]">
                                    <thead>
                                        <tr className="bg-[var(--portal-muted)] text-[11px] text-[var(--portal-ink)]">
                                            <th className="border border-[var(--portal-border)] px-2.5 py-2 text-left font-semibold">Team</th>
                                            {quarters.map((quarter) => (
                                                <th key={quarter.label} className="border border-[var(--portal-border)] px-2.5 py-2 font-semibold">
                                                    {quarter.label}
                                                </th>
                                            ))}
                                            <th className="border border-[var(--portal-border)] px-2.5 py-2 font-semibold">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td className="border border-[var(--portal-border)] px-2.5 py-2 text-left font-bold">
                                                {session.side_a_label ?? 'TBD'}
                                            </td>
                                            {quarters.map((quarter) => (
                                                <td key={quarter.label} className="border border-[var(--portal-border)] px-2.5 py-2 text-center">
                                                    {quarter.a}
                                                </td>
                                            ))}
                                            <td className="border border-[var(--portal-border)] px-2.5 py-2 text-center text-[17px] font-[950] text-[var(--portal-ink)]">
                                                {session.score_a ?? 0}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="border border-[var(--portal-border)] px-2.5 py-2 text-left font-bold">
                                                {session.side_b_label ?? 'TBD'}
                                            </td>
                                            {quarters.map((quarter) => (
                                                <td key={quarter.label} className="border border-[var(--portal-border)] px-2.5 py-2 text-center">
                                                    {quarter.b}
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

                    <div className={cn('mx-auto mt-3 grid max-w-[420px] gap-3', showTimeouts ? 'grid-cols-2' : 'grid-cols-1')}>
                        <div className="grid grid-cols-3 items-center overflow-hidden rounded-[9px] border border-[var(--portal-border)] px-4 py-2.5 text-[13px]">
                            <span className="text-left text-[17px] font-[950] text-[var(--portal-ink)] tabular-nums">{foulsA}</span>
                            <span className="text-center text-[11px] font-[900] text-[var(--portal-muted-foreground)] uppercase">Fouls</span>
                            <span className="text-right text-[17px] font-[950] text-[var(--portal-ink)] tabular-nums">{foulsB}</span>
                        </div>
                        {showTimeouts && (
                            <div className="grid grid-cols-3 items-center overflow-hidden rounded-[9px] border border-[var(--portal-border)] px-4 py-2.5 text-[13px]">
                                <span className="text-left text-[17px] font-[950] text-[var(--portal-ink)] tabular-nums">{timeoutsA}</span>
                                <span className="text-center text-[11px] font-[900] text-[var(--portal-muted-foreground)] uppercase">
                                    Timeouts
                                </span>
                                <span className="text-right text-[17px] font-[950] text-[var(--portal-ink)] tabular-nums">{timeoutsB}</span>
                            </div>
                        )}
                    </div>
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
                <div className="flex overflow-x-auto border-b border-[var(--portal-border)]">
                    {TABS.map((item) => (
                        <button
                            key={item.id}
                            type="button"
                            onClick={() => setTab(item.id)}
                            className={cn(
                                'flex-1 border-b-[3px] px-3 py-[15px] text-sm font-[850] whitespace-nowrap transition-colors',
                                tab === item.id
                                    ? 'border-[var(--portal-accent)] text-[var(--portal-ink)]'
                                    : 'border-transparent text-[var(--portal-muted-foreground)] hover:text-[var(--portal-fg)]',
                            )}
                        >
                            {item.label}
                        </button>
                    ))}
                </div>

                {tab === 'plays' &&
                    (session.playByPlay.length === 0 ? (
                        <PortalEmptyState
                            icon={ClipboardList}
                            tone="maroon"
                            title="No plays recorded yet"
                            description="Play-by-play appears here as soon as the scoring table logs the first event."
                            className="rounded-none border-0"
                        />
                    ) : (
                        <div>
                            {session.playByPlay.map((play, index) => (
                                <PlayRow key={play.id} play={play} highlight={index === 0} />
                            ))}
                            <div className="p-3 text-center text-sm font-[850] text-[var(--portal-ink)]">Showing the most recent plays</div>
                        </div>
                    ))}

                {tab === 'box' && (
                    <PortalEmptyState
                        icon={ClipboardList}
                        tone="ink"
                        title="Box score not available"
                        description="PMMS doesn't track full per-player stat lines yet — see Team stats in the sidebar for what is tracked."
                        className="rounded-none border-0"
                    />
                )}

                {tab === 'stats' && (
                    <PortalEmptyState
                        icon={ListOrdered}
                        tone="ink"
                        title="See Team stats in the sidebar"
                        description="This game's shooting/rebounding splits are shown in the Team Comparison card alongside this scoreboard."
                        className="rounded-none border-0"
                    />
                )}

                {tab === 'videos' && (
                    <PortalEmptyState
                        icon={Video}
                        tone="ink"
                        title="No video for this game"
                        description="Video highlights aren't published for this match."
                        className="rounded-none border-0"
                    />
                )}
            </div>
        </div>
    );
}
