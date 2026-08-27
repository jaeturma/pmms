import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { PortalCountdown } from '@/apps/portal/components/countdown';
import { PortalHeroBackground } from '@/apps/portal/components/hero-background';
import { PortalTorchIcon } from '@/apps/portal/components/torch-icon';
import { cn } from '@/apps/portal/lib/utils';
import type { PortalMunicipality } from '@/apps/portal/types';
import { show as teamShow } from '@/routes/public/teams';

type PortalLandingHeroProps = {
    title: string;
    description?: string;
    meta?: ReactNode;
    /** ISO instant to count down to. Omitted (no active meet) or already
     * elapsed (meet already underway) both simply skip the countdown —
     * `PortalCountdown`'s own `onElapsed="hide"` handles the latter. */
    startsAtIso?: string;
    /** Replaces the default `PortalTorchIcon` SVG mark when the division
     * has uploaded one (`Division::heroIcon`, admin-configurable via the
     * Division settings page) — never both at once. */
    heroIconUrl?: string | null;
    /** Rendered below the countdown as a card grid spanning 90% of the
     * hero's width — 3 columns on mobile, 4 from `sm:` (~640px, tablet),
     * 6 from `md:` (~768px), then from `lg:` (~1024px, laptop/desktop) a
     * single non-wrapping row fitting every card at once (each card
     * becomes `flex-1`, sharing the row width evenly rather than a fixed
     * column count). Each card is a plain logo image (no placeholder
     * badge if none is uploaded yet — this shows real logos, not
     * avatars) with the municipality's name (always one line, truncated
     * if it doesn't fit) and nickname (broken onto a second line
     * whenever it's more than one word) underneath. */
    municipalities?: PortalMunicipality[];
    className?: string;
};

/**
 * The landing page's dedicated, full-viewport premium hero — distinct
 * from the shared `PortalHero` band every inner page (Schedule, FAQs,
 * Sports directory, ...) still reuses for its simple title/description
 * header. Building a second component here rather than expanding
 * `PortalHero` itself keeps every one of those inner pages unaffected.
 *
 * Deliberately full-bleed (a row spanning the viewport, not a rounded
 * card floating inside `PortalLayout`'s padded `<main>`) — the negative
 * horizontal margins below exactly cancel that `<main>`'s padding at each
 * of its breakpoints (`px-4 sm:px-6 lg:px-10 xl:px-16 2xl:px-24`), then
 * re-apply the same values as the section's own padding so the hero's
 * content still lines up with every other page section instead of
 * touching the true edge. The negative top margin (`-mt-8 sm:-mt-10`,
 * matching `<main>`'s own `py-8 sm:py-10`) does the same vertically, so
 * the hero sits flush against the sticky header with no gap row.
 */
export function PortalLandingHero({
    title,
    description,
    meta,
    startsAtIso,
    heroIconUrl,
    municipalities,
    className,
}: PortalLandingHeroProps) {
    return (
        <section
            className={cn(
                'relative -mx-4 -mt-8 flex min-h-[78vh] flex-col justify-center overflow-hidden border-b-4 border-[var(--portal-accent)] px-4 pt-8 pb-14 text-[var(--portal-ink-foreground)] sm:-mx-6 sm:-mt-10 sm:px-6 sm:pt-12 sm:pb-20 lg:-mx-10 lg:px-10 xl:-mx-16 xl:px-16 2xl:-mx-24 2xl:px-24',
                className,
            )}
        >
            <PortalHeroBackground />

            <div className="portal-hero-in relative mx-auto flex w-full max-w-4xl flex-col items-center gap-5 text-center">
                {heroIconUrl ? (
                    <img
                        src={heroIconUrl}
                        alt=""
                        className="h-24 w-48 max-w-[90vw] object-contain sm:h-[7.2rem] sm:w-[14.4rem]"
                    />
                ) : (
                    <PortalTorchIcon className="h-24 w-auto text-[var(--portal-ink-foreground)] sm:h-[7.2rem]" />
                )}

                {description && (
                    <p className="max-w-2xl text-base whitespace-pre-line text-[var(--portal-ink-foreground)]/85 sm:text-lg">
                        {description}
                    </p>
                )}

                <h1 className="text-[1.8rem] leading-[1.05] font-extrabold tracking-tight sm:text-5xl lg:text-[3.6rem]">
                    {title}
                </h1>

                {meta && (
                    <div className="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm font-medium text-[var(--portal-ink-foreground)]/80">
                        {meta}
                    </div>
                )}

                {startsAtIso && (
                    <div className="mt-2">
                        <PortalCountdown
                            targetIso={startsAtIso}
                            variant="display"
                            onElapsed="hide"
                        />
                    </div>
                )}
            </div>

            {municipalities && municipalities.length > 0 && (
                <div
                    id="municipalities"
                    className="relative mx-auto mt-8 grid w-[90%] scroll-mt-20 grid-cols-3 items-start gap-x-4 gap-y-6 sm:mt-10 sm:grid-cols-4 md:grid-cols-6 lg:flex lg:flex-nowrap lg:gap-x-3"
                    aria-label="Competing municipalities"
                >
                    {municipalities.map((municipality) => {
                        const nicknameWords =
                            municipality.nickname?.split(' ') ?? [];
                        const logoUrl =
                            municipality.team_logo_url ?? municipality.logo_url;

                        return (
                            <Link
                                key={municipality.id}
                                href={teamShow(municipality.slug).url}
                                className="flex min-w-0 flex-col items-center gap-1 lg:flex-1"
                            >
                                {logoUrl && (
                                    <img
                                        src={logoUrl}
                                        alt={municipality.name}
                                        className="h-auto max-h-[53px] w-full object-contain sm:max-h-[69px]"
                                    />
                                )}

                                <div className="flex w-full flex-col items-center text-center">
                                    <span className="w-full truncate text-[10px] font-semibold sm:text-xs">
                                        {municipality.name}
                                    </span>
                                    {nicknameWords.length > 0 && (
                                        <span className="text-[9px] text-[var(--portal-ink-foreground)]/70 sm:text-[11px]">
                                            {nicknameWords.length > 1 ? (
                                                <>
                                                    "{nicknameWords[0]}
                                                    <br />
                                                    {nicknameWords
                                                        .slice(1)
                                                        .join(' ')}
                                                    "
                                                </>
                                            ) : (
                                                `"${nicknameWords[0]}"`
                                            )}
                                        </span>
                                    )}
                                </div>
                            </Link>
                        );
                    })}
                </div>
            )}
        </section>
    );
}
