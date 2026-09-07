import { useEffect, useState } from 'react';
import { initialsFor } from '@/apps/portal/components/municipality-crest';
import { ScoreboardGameLabel } from '@/apps/portal/components/scoreboard-game-label';
import { readDecision } from '@/apps/portal/lib/boxing-state';
import { cn } from '@/apps/portal/lib/utils';
import type { PortalLiveNow } from '@/apps/portal/types';

type PortalBoxingScoreboardProps = {
    liveNow: PortalLiveNow;
    className?: string;
};

function formatClock(totalSeconds: number): string {
    const clamped = Math.max(0, totalSeconds);
    const minutes = Math.floor(clamped / 60);
    const seconds = clamped % 60;

    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

type BoxingClock = {
    clock_seconds?: number;
    clock_updated_at?: string | null;
    clock_phase?: 'round' | 'rest';
};

/** The round/rest countdown — anchored to the operator's last clock
 * action and ticked down locally, the same shape as the operator
 * console's own clock (`CountdownClock` in live-score-display). */
function RoundCountdown({ clock, running }: { clock: BoxingClock; running: boolean }) {
    const base = typeof clock.clock_seconds === 'number' ? clock.clock_seconds : 0;
    const anchor = clock.clock_updated_at ?? null;

    const compute = () => {
        if (!running || anchor === null) {
            return base;
        }
        const elapsed = Math.floor((Date.now() - new Date(anchor).getTime()) / 1000);

        return Math.max(0, base - elapsed);
    };

    const [remaining, setRemaining] = useState(compute);

    useEffect(() => {
        setRemaining(compute());
        if (!running || anchor === null) {
            return;
        }
        const id = setInterval(() => setRemaining(compute()), 1000);

        return () => clearInterval(id);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [base, anchor, running]);

    return <>{formatClock(remaining)}</>;
}

/** The athlete's sports/action photo when one is on file for this bout's
 * corner (`ScoringSession::athleteParticipants()` — only resolvable for a
 * one-on-one individual entry) — falls back to an initials badge from the
 * corner's team/delegation label otherwise. Sits on the corner's own
 * red/blue panel, so the frame is white for contrast. */
function CornerPhoto({ name, photoUrl }: { name: string; photoUrl?: string | null }) {
    if (photoUrl) {
        return (
            <img
                src={photoUrl}
                alt={`${name} — sports photo`}
                className="size-[96px] shrink-0 rounded-2xl border-4 border-white/90 object-cover shadow-[0_3px_14px_rgba(0,0,0,0.25)] sm:size-[124px]"
            />
        );
    }

    return (
        <span
            aria-hidden="true"
            className="flex size-[96px] shrink-0 items-center justify-center rounded-2xl border-4 border-white/90 bg-white/15 text-xl font-black shadow-[0_3px_14px_rgba(0,0,0,0.25)] sm:size-[124px] sm:text-3xl"
        >
            {initialsFor(name)}
        </span>
    );
}

function CornerPanel({
    corner,
    label,
    athleteName,
    logoUrl,
    photoUrl,
    align,
}: {
    corner: 'red' | 'blue';
    label: string;
    athleteName?: string;
    logoUrl?: string | null;
    photoUrl?: string | null;
    align: 'left' | 'right';
}) {
    return (
        <div
            className={cn(
                'flex flex-col items-center gap-3 px-3 py-6 text-center sm:flex-row sm:gap-5 sm:px-6 sm:py-8',
                align === 'right' && 'sm:flex-row-reverse sm:text-right',
                corner === 'red'
                    ? 'bg-[#b3122a] text-white'
                    : 'bg-[#0c47b7] text-white',
            )}
        >
            <CornerPhoto name={athleteName ?? label} photoUrl={photoUrl} />
            <div className={cn('flex flex-col items-center gap-1', align === 'right' ? 'sm:items-end' : 'sm:items-start')}>
                <span className="inline-flex rounded-full bg-white/20 px-2.5 py-[3px] text-[10px] font-[950] uppercase">
                    {corner === 'red' ? 'Red Corner' : 'Blue Corner'}
                </span>
                <p className="text-base font-[950] uppercase sm:text-xl">{athleteName ?? label}</p>
                {athleteName && (
                    <div className={cn('flex items-center gap-1.5', align === 'right' && 'sm:flex-row-reverse')}>
                        {logoUrl && (
                            <img src={logoUrl} alt="" aria-hidden="true" className="size-4 shrink-0 rounded-full object-cover" />
                        )}
                        <span className="text-xs font-[750] opacity-90">{label}</span>
                    </div>
                )}
            </div>
        </div>
    );
}

export function PortalBoxingScoreboard({ liveNow, className }: PortalBoxingScoreboardProps) {
    const { session } = liveNow;
    const decision = readDecision(session.sport_state);

    const sideALabel = session.side_a_label ?? 'TBD';
    const sideBLabel = session.side_b_label ?? 'TBD';
    const athleteA = session.side_a_athlete;
    const athleteB = session.side_b_athlete;

    const clock: BoxingClock =
        session.sport_state && typeof session.sport_state === 'object'
            ? (session.sport_state as unknown as BoxingClock)
            : {};
    const running = session.status === 'in_progress';
    const roundsTotal =
        session.sport_state && typeof session.sport_state === 'object' && 'total_rounds' in session.sport_state
            ? (session.sport_state as { total_rounds?: number }).total_rounds
            : undefined;
    const roundBadge = roundsTotal
        ? `${(session.period_label ?? 'ROUND').toUpperCase()} OF ${roundsTotal}`
        : (session.period_label ?? session.status_label).toUpperCase();

    const decisionCorner =
        decision?.winner === 'a' ? sideALabel : decision?.winner === 'b' ? sideBLabel : null;
    const decisionMethod =
        decision?.method === 'points'
            ? decision.type
                ? `${decision.type[0].toUpperCase()}${decision.type.slice(1)} decision`
                : 'Decision'
            : (decision?.method ?? '').replace('_', '-').toUpperCase();
    const showDecision = Boolean(decision && decisionCorner);

    return (
        <div className={cn('flex flex-col gap-3.5', className)}>
            <ScoreboardGameLabel
                mode={session.scoreboard_mode}
                teamA={session.side_a_team}
                teamB={session.side_b_team}
            />
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

                <div className="grid items-stretch sm:grid-cols-[1fr_auto_1fr]">
                    <CornerPanel
                        corner="red"
                        label={sideALabel}
                        athleteName={athleteA?.name}
                        logoUrl={session.side_a_logo_url}
                        photoUrl={athleteA?.sports_photo_url}
                        align="left"
                    />

                    <div className="flex flex-col items-center justify-center gap-2 bg-[var(--portal-muted)] px-6 py-6">
                        <span className="rounded-[8px] bg-[var(--portal-ink)] px-3 py-1.5 text-[11px] font-[900] text-[var(--portal-ink-foreground)] sm:text-[13px]">
                            {roundBadge}
                            {clock.clock_phase === 'rest' ? ' · REST' : ''}
                        </span>
                        <span className="font-mono text-[40px] font-[950] text-[var(--portal-ink)] tabular-nums sm:text-[64px]">
                            <RoundCountdown clock={clock} running={running} />
                        </span>
                        <span className="text-[11px] font-[900] text-[var(--portal-maroon)]">
                            {session.status === 'ended' ? session.status_label.toUpperCase() : '● BOUT IN PROGRESS'}
                        </span>
                    </div>

                    <CornerPanel
                        corner="blue"
                        label={sideBLabel}
                        athleteName={athleteB?.name}
                        logoUrl={session.side_b_logo_url}
                        photoUrl={athleteB?.sports_photo_url}
                        align="right"
                    />
                </div>

                {showDecision && decision && (
                    <div className="border-t-2 border-[var(--portal-ink)] bg-[var(--portal-ink)] px-4 py-4 text-center text-[var(--portal-ink-foreground)]">
                        <span className="block text-[11px] font-[850] uppercase opacity-80">
                            {decision.status === 'final' ? 'Referee’s decision' : 'Currently leading'}
                        </span>
                        <strong className="text-xl font-[950] uppercase sm:text-2xl">
                            {decisionCorner} {decision.status === 'final' ? 'wins' : 'ahead'}
                        </strong>
                        <span className="block text-xs font-[750]">
                            {decisionMethod}
                            {decision.tally
                                ? ` · ${Math.max(decision.tally.a, decision.tally.b)}–${Math.min(decision.tally.a, decision.tally.b)}`
                                : ''}
                        </span>
                        {decision.note && <span className="mt-0.5 block text-[11px] opacity-80">{decision.note}</span>}
                    </div>
                )}

                {(session.status_note || liveNow.venue) && (
                    <div className="grid grid-cols-[1fr_auto_1fr] items-center gap-2 border-t border-[var(--portal-border)] px-4 py-2 text-xs text-[var(--portal-muted-foreground)]">
                        <span>{session.status_note}</span>
                        {liveNow.venue && <span className="text-center">{liveNow.venue}</span>}
                        <span />
                    </div>
                )}
            </div>
        </div>
    );
}
