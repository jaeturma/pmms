/** The reference mockups' (`docs/ui-ux/basketball.html`, `docs/ui-ux/
 * softball-live.html`) "event-strip" — the crumb/live-badge/date-time
 * row — reused by any sport's `/basketball`-style hub page Live Now
 * section and dedicated per-match scoreboard page. Replaces the generic
 * `PortalHero` for a sport whenever there's a specific live game to
 * describe. Venue isn't repeated here — it's already shown in the
 * scoreboard card's own footer. */
type PortalSportEventStripProps = {
    sportEmoji: string;
    sportName: string;
    category: string;
    roundLabel: string | null;
    live: boolean;
    scheduledDate: string | null;
    startsAt: string | null;
};

export function PortalSportEventStrip({
    sportEmoji,
    sportName,
    category,
    roundLabel,
    live,
    scheduledDate,
    startsAt,
}: PortalSportEventStripProps) {
    return (
        <div className="portal-animate-in flex flex-wrap items-center gap-3">
            <span className="text-xl font-[900] text-[var(--portal-ink)]">
                {sportEmoji} {sportName.toUpperCase()}
            </span>
            <span
                aria-hidden="true"
                className="text-[var(--portal-muted-foreground)]"
            >
                ›
            </span>
            <span className="font-[750]">{category}</span>
            {roundLabel && (
                <>
                    <span
                        aria-hidden="true"
                        className="text-[var(--portal-muted-foreground)]"
                    >
                        ›
                    </span>
                    <span className="font-[750]">{roundLabel}</span>
                </>
            )}
            {live && (
                <span className="rounded-[7px] bg-[var(--portal-live)] px-2.5 py-[5px] text-xs font-[900] text-[var(--portal-live-foreground)]">
                    ● LIVE NOW
                </span>
            )}
            {(scheduledDate || startsAt) && (
                <span className="flex flex-wrap items-center gap-x-3 text-sm font-[650] text-[var(--portal-muted-foreground)]">
                    {scheduledDate && <span>📅 {scheduledDate}</span>}
                    {startsAt && <span>🕐 {startsAt}</span>}
                </span>
            )}
        </div>
    );
}
