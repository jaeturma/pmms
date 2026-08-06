import type { ReactNode } from 'react';
import { PortalCountdown } from '@/apps/portal/components/countdown';
import { PortalHeroBackground } from '@/apps/portal/components/hero-background';
import { PortalTorchIcon } from '@/apps/portal/components/torch-icon';
import { cn } from '@/apps/portal/lib/utils';
import type { PortalMunicipality } from '@/apps/portal/types';

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
    /** Rendered as a wrapping row of plain logo images spanning 90% of
     * the hero's width, below the countdown — one row on wide screens,
     * wrapping onto more rows as the viewport narrows rather than
     * shrinking below a legible size. A municipality with no crest
     * uploaded yet falls back to its plain name (no placeholder badge —
     * this row is images, not avatars). */
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
                'relative -mx-4 -mt-8 flex min-h-[78vh] flex-col justify-center overflow-hidden border-b-4 border-[var(--portal-accent)] px-4 py-14 text-[var(--portal-ink-foreground)] sm:-mx-6 sm:-mt-10 sm:px-6 sm:py-20 lg:-mx-10 lg:px-10 xl:-mx-16 xl:px-16 2xl:-mx-24 2xl:px-24',
                className,
            )}
        >
            <PortalHeroBackground />

            <div className="portal-hero-in relative mx-auto flex w-full max-w-4xl flex-col items-center gap-5 text-center">
                {heroIconUrl ? (
                    <img src={heroIconUrl} alt="" className="h-20 w-auto object-contain sm:h-24" />
                ) : (
                    <PortalTorchIcon className="h-20 w-auto text-[var(--portal-ink-foreground)] sm:h-24" />
                )}

                <h1 className="text-4xl leading-[1.05] font-extrabold tracking-tight sm:text-6xl lg:text-7xl">{title}</h1>

                {description && (
                    <p className="max-w-2xl text-base text-[var(--portal-ink-foreground)]/85 sm:text-lg">{description}</p>
                )}

                {meta && (
                    <div className="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm font-medium text-[var(--portal-ink-foreground)]/80">
                        {meta}
                    </div>
                )}

                {startsAtIso && (
                    <div className="mt-2">
                        <PortalCountdown targetIso={startsAtIso} variant="display" onElapsed="hide" />
                    </div>
                )}
            </div>

            {municipalities && municipalities.length > 0 && (
                <div
                    className="relative mx-auto mt-8 flex w-[90%] flex-wrap items-center justify-center gap-x-8 gap-y-6 sm:mt-10"
                    aria-label="Competing municipalities"
                >
                    {municipalities.map((municipality) =>
                        municipality.logo_url ? (
                            <img
                                key={municipality.id}
                                src={municipality.logo_url}
                                alt={municipality.name}
                                className="h-14 w-auto object-contain sm:h-20"
                            />
                        ) : (
                            <span
                                key={municipality.id}
                                className="text-sm font-medium text-[var(--portal-ink-foreground)]/70 sm:text-base"
                            >
                                {municipality.name}
                            </span>
                        ),
                    )}
                </div>
            )}
        </section>
    );
}
