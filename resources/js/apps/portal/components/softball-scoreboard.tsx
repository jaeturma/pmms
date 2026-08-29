import { ClipboardList } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { initialsFor } from '@/apps/portal/components/municipality-crest';
import {
    readCount,
    readHitsAndErrors,
    readRunners,
} from '@/apps/portal/lib/softball-state';
import { cn } from '@/apps/portal/lib/utils';
import type { PortalLiveNow, PortalPlayByPlayEntry } from '@/apps/portal/types';

type PortalSoftballScoreboardProps = {
    liveNow: PortalLiveNow;
    className?: string;
};

const INNING_COLUMNS = [1, 2, 3, 4, 5, 6, 7];

function ordinal(n: number): string {
    const mod100 = n % 100;

    if (mod100 >= 11 && mod100 <= 13) {
        return `${n}TH`;
    }

    switch (n % 10) {
        case 1:
            return `${n}ST`;
        case 2:
            return `${n}ND`;
        case 3:
            return `${n}RD`;
        default:
            return `${n}TH`;
    }
}

/** `docs/ui-ux/softball-live.html`'s per-event icon, mapped from our real
 * `ScoreEvent` types (`Count` for balls/strikes/outs, `InningRun` for
 * runs) rather than fabricating pitch-type detail the log doesn't have. */
function playIcon(description: string): string {
    return description.startsWith('+') ? '🏃' : '⚾';
}

function TeamLogo({
    name,
    side,
    logoUrl,
}: {
    name: string;
    side: 'home' | 'away';
    logoUrl?: string | null;
}) {
    if (logoUrl) {
        return (
            <img
                src={logoUrl}
                alt={`${name} crest`}
                className="size-[82px] shrink-0 object-cover sm:size-[120px]"
            />
        );
    }

    return (
        <span
            aria-hidden="true"
            className="flex size-[82px] shrink-0 items-center justify-center rounded-full border-4 border-[var(--portal-surface)] text-lg font-black text-[var(--portal-ink-foreground)] shadow-[0_4px_14px_rgba(0,0,0,0.18)] sm:size-[120px] sm:text-2xl"
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

function CountDots({
    count,
    max,
    tone,
}: {
    count: number;
    max: number;
    tone: 'accent' | 'maroon' | 'live';
}) {
    const dots = Array.from({ length: max }, (_, index) => index < count);
    const onClass = {
        accent: 'bg-[var(--portal-accent)]',
        maroon: 'bg-[var(--portal-maroon)]',
        live: 'bg-[var(--portal-live)]',
    }[tone];

    return (
        <div className="flex justify-center gap-2">
            {dots.map((on, index) => (
                <span
                    key={index}
                    aria-hidden="true"
                    className={cn(
                        'size-[19px] rounded-full border-2',
                        on
                            ? cn(onClass, 'border-transparent')
                            : 'border-[var(--portal-border)]',
                    )}
                />
            ))}
        </div>
    );
}

function PlayRow({
    play,
    highlight,
}: {
    play: PortalPlayByPlayEntry;
    highlight: boolean;
}) {
    return (
        <div
            className={cn(
                'grid grid-cols-[64px_28px_1fr_74px] items-center gap-3 border-b border-[var(--portal-border)] px-4 py-2.5 sm:grid-cols-[82px_36px_1fr_85px] sm:px-4',
                highlight &&
                    'bg-gradient-to-r from-[var(--portal-accent-soft)] to-transparent',
            )}
        >
            <span className="text-xs font-[850] sm:text-sm">
                {play.created_at}
            </span>
            <span aria-hidden="true" className="text-lg sm:text-xl">
                {playIcon(play.description)}
            </span>
            <span className="truncate text-sm">{play.description}</span>
            <span className="text-right font-mono text-base font-[950] text-[var(--portal-maroon)] tabular-nums sm:text-[18px]">
                {play.score_a}–{play.score_b}
            </span>
        </div>
    );
}

export function PortalSoftballScoreboard({
    liveNow,
    className,
}: PortalSoftballScoreboardProps) {
    const { session } = liveNow;
    const count = readCount(session.sport_state);
    const runners = readRunners(session.sport_state);
    const hitsAndErrors = readHitsAndErrors(session.sport_state);

    const inningTitle = `${count.half.toUpperCase()} OF ${ordinal(count.inning)}`;

    return (
        <div className={cn('flex flex-col gap-3.5', className)}>
            <div className="portal-animate-in overflow-hidden rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] text-[var(--portal-surface-foreground)] shadow-[0_6px_24px_rgba(15,23,42,0.08)]">
                <div className="flex items-center justify-between gap-2 bg-[var(--portal-live)] px-4 py-2 text-[var(--portal-live-foreground)]">
                    <span className="flex items-center gap-1.5 text-xs font-semibold tracking-wide uppercase">
                        <span
                            aria-hidden="true"
                            className="portal-live-dot size-2 rounded-full bg-current"
                        />
                        {session.status === 'ended'
                            ? session.status_label
                            : 'Live now'}
                    </span>
                    <span className="text-xs">
                        {liveNow.category}
                        {liveNow.round_label ? ` · ${liveNow.round_label}` : ''}
                    </span>
                </div>

                <div className="px-3 py-4 sm:px-8 sm:py-4">
                    <div className="grid grid-cols-[1fr_105px_1fr] items-center gap-2 px-1 py-1 sm:grid-cols-[1fr_200px_1fr] sm:gap-7 sm:px-8">
                        <div className="flex flex-col items-center gap-2 text-center sm:grid sm:grid-cols-[120px_1fr] sm:items-center sm:gap-[18px] sm:text-left">
                            <TeamLogo
                                name={session.side_a_label ?? 'TBD'}
                                side="home"
                                logoUrl={session.side_a_logo_url}
                            />
                            <div>
                                <p className="text-[53px] leading-[0.95] font-[950] tracking-[-2px] tabular-nums sm:text-[86px] sm:tracking-[-5px]">
                                    {session.score_a ?? 0}
                                </p>
                                <p className="mt-2 text-[13px] font-[950] uppercase sm:text-[23px]">
                                    {session.side_a_label ?? 'TBD'}
                                </p>
                            </div>
                        </div>

                        <div className="overflow-hidden rounded-[9px] border border-[var(--portal-border)] bg-[var(--portal-surface)] text-center">
                            <div className="bg-[var(--portal-ink)] px-1 py-1.5 text-[11px] font-[900] text-[var(--portal-ink-foreground)] sm:px-2 sm:py-2 sm:text-[18px]">
                                {inningTitle}
                            </div>
                            <div
                                className="relative mx-auto mt-1 mb-0 h-[50px] w-[66px] scale-[0.72] sm:mt-3.5 sm:mb-1 sm:h-[70px] sm:w-[92px] sm:scale-100"
                                aria-label={`Runners on base: ${
                                    [
                                        runners?.first && 'first',
                                        runners?.second && 'second',
                                        runners?.third && 'third',
                                    ]
                                        .filter(Boolean)
                                        .join(', ') || 'none'
                                }`}
                            >
                                <span
                                    aria-hidden="true"
                                    className={cn(
                                        'absolute top-0 left-[31px] size-[30px] rotate-45 rounded-[3px] shadow-[0_2px_5px_rgba(0,0,0,0.07)]',
                                        runners?.second
                                            ? 'bg-[var(--portal-accent)]'
                                            : 'bg-[var(--portal-border)]',
                                    )}
                                />
                                <span
                                    aria-hidden="true"
                                    className={cn(
                                        'absolute right-[5px] bottom-[5px] size-[30px] rotate-45 rounded-[3px] shadow-[0_2px_5px_rgba(0,0,0,0.07)]',
                                        runners?.first
                                            ? 'bg-[var(--portal-accent)]'
                                            : 'bg-[var(--portal-border)]',
                                    )}
                                />
                                <span
                                    aria-hidden="true"
                                    className={cn(
                                        'absolute bottom-[5px] left-[5px] size-[30px] rotate-45 rounded-[3px] shadow-[0_2px_5px_rgba(0,0,0,0.07)]',
                                        runners?.third
                                            ? 'bg-[var(--portal-accent)]'
                                            : 'bg-[var(--portal-border)]',
                                    )}
                                />
                            </div>
                            <div className="px-1 pb-2.5 text-xs font-[850] sm:pb-3 sm:text-[17px]">
                                <span className="px-2">
                                    {count.balls}-{count.strikes}
                                </span>
                                |
                                <span className="px-2">
                                    {count.outs === 1
                                        ? '1 OUT'
                                        : `${count.outs} OUTS`}
                                </span>
                            </div>
                        </div>

                        <div className="flex flex-col items-center gap-2 text-center sm:grid sm:grid-cols-[1fr_120px] sm:items-center sm:gap-[18px] sm:text-right">
                            <div className="sm:order-2">
                                <TeamLogo
                                    name={session.side_b_label ?? 'TBD'}
                                    side="away"
                                    logoUrl={session.side_b_logo_url}
                                />
                            </div>
                            <div className="sm:order-1">
                                <p className="text-[53px] leading-[0.95] font-[950] tracking-[-2px] tabular-nums sm:text-[86px] sm:tracking-[-5px]">
                                    {session.score_b ?? 0}
                                </p>
                                <p className="mt-2 text-[13px] font-[950] uppercase sm:text-[23px]">
                                    {session.side_b_label ?? 'TBD'}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="mx-auto mt-2 grid w-full max-w-[460px] grid-cols-3 overflow-hidden rounded-[9px] border border-[var(--portal-border)] sm:mt-2.5">
                        <div className="border-r border-[var(--portal-border)] px-3 py-2 text-center">
                            <div className="mb-1.5 text-xs font-[900] text-[var(--portal-fg)]">
                                BALLS
                            </div>
                            <CountDots
                                count={count.balls}
                                max={3}
                                tone="accent"
                            />
                        </div>
                        <div className="border-r border-[var(--portal-border)] px-3 py-2 text-center">
                            <div className="mb-1.5 text-xs font-[900] text-[var(--portal-fg)]">
                                STRIKES
                            </div>
                            <CountDots
                                count={count.strikes}
                                max={3}
                                tone="maroon"
                            />
                        </div>
                        <div className="px-3 py-2 text-center">
                            <div className="mb-1.5 text-xs font-[900] text-[var(--portal-fg)]">
                                OUTS
                            </div>
                            <CountDots count={count.outs} max={3} tone="live" />
                        </div>
                    </div>
                </div>

                <div className="overflow-x-auto px-3 pb-1 sm:px-4">
                    <table className="w-full border-collapse overflow-hidden rounded-[9px] border border-[var(--portal-border)] text-[13px]">
                        <thead>
                            <tr className="bg-[var(--portal-ink)] text-[12px] text-[var(--portal-ink-foreground)]">
                                <th className="border-r border-b border-[var(--portal-border)] py-2 pl-8 text-left font-semibold">
                                    Team
                                </th>
                                {INNING_COLUMNS.map((n) => (
                                    <th
                                        key={n}
                                        className="border-r border-b border-[var(--portal-border)] py-2 font-semibold"
                                    >
                                        {n}
                                    </th>
                                ))}
                                <th className="border-r border-b border-[var(--portal-border)] py-2 font-semibold">
                                    R
                                </th>
                                <th className="border-r border-b border-[var(--portal-border)] py-2 font-semibold">
                                    H
                                </th>
                                <th className="border-b border-[var(--portal-border)] py-2 font-semibold">
                                    E
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {[
                                {
                                    label: session.side_a_label ?? 'TBD',
                                    runs: session.score_a ?? 0,
                                    hits: hitsAndErrors?.hits_a,
                                    errors: hitsAndErrors?.errors_a,
                                    side: 'a' as const,
                                },
                                {
                                    label: session.side_b_label ?? 'TBD',
                                    runs: session.score_b ?? 0,
                                    hits: hitsAndErrors?.hits_b,
                                    errors: hitsAndErrors?.errors_b,
                                    side: 'b' as const,
                                },
                            ].map((row) => (
                                <tr key={row.side}>
                                    <td className="border-r border-b border-[var(--portal-border)] py-2 pl-8 text-left font-[850]">
                                        {row.label}
                                    </td>
                                    {INNING_COLUMNS.map((n) => {
                                        const inningRow = count.innings.find(
                                            (i) => i.inning === n,
                                        );
                                        const value = inningRow
                                            ? row.side === 'a'
                                                ? inningRow.runs_a
                                                : inningRow.runs_b
                                            : null;

                                        return (
                                            <td
                                                key={n}
                                                className="border-r border-b border-[var(--portal-border)] py-2 text-center"
                                            >
                                                {value ?? '–'}
                                            </td>
                                        );
                                    })}
                                    <td className="border-r border-b border-[var(--portal-border)] py-2 text-center font-[900]">
                                        {row.runs}
                                    </td>
                                    <td className="border-r border-b border-[var(--portal-border)] py-2 text-center font-[900]">
                                        {row.hits ?? '–'}
                                    </td>
                                    <td className="border-b border-[var(--portal-border)] py-2 text-center font-[900]">
                                        {row.errors ?? '–'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="pt-2 pb-3 text-center text-xs text-[var(--portal-muted-foreground)]">
                    R - Runs &nbsp;&nbsp;|&nbsp;&nbsp; H - Hits
                    &nbsp;&nbsp;|&nbsp;&nbsp; E - Errors
                </div>

                {(session.status_note || liveNow.venue) && (
                    <div className="flex items-center justify-between gap-2 border-t border-[var(--portal-border)] px-4 py-2 text-xs text-[var(--portal-muted-foreground)]">
                        <span>{session.status_note}</span>
                        {liveNow.venue && <span>{liveNow.venue}</span>}
                    </div>
                )}
            </div>

            <div className="overflow-hidden rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)]">
                <div className="bg-[var(--portal-ink)] px-4 py-2.5 text-sm font-[900] text-[var(--portal-ink-foreground)] uppercase">
                    Live Play by Play
                </div>

                {session.playByPlay.length === 0 ? (
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
                            <PlayRow
                                key={play.id}
                                play={play}
                                highlight={index === 0}
                            />
                        ))}
                        <div className="p-3 text-center text-sm font-[850] text-[var(--portal-ink)]">
                            Showing the most recent plays
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
