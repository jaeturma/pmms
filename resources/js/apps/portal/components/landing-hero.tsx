import type { ReactNode } from 'react';
import { PortalCountdown } from '@/apps/portal/components/countdown';
import { PortalHeroBackground } from '@/apps/portal/components/hero-background';
import { PortalTorchIcon } from '@/apps/portal/components/torch-icon';
import { cn } from '@/apps/portal/lib/utils';

type PortalLandingHeroProps = {
    eyebrow?: string;
    title: string;
    description?: string;
    meta?: ReactNode;
    actions?: ReactNode;
    /** ISO instant to count down to. Omitted (no active meet) or already
     * elapsed (meet already underway) both simply skip the countdown —
     * `PortalCountdown`'s own `onElapsed="hide"` handles the latter. */
    startsAtIso?: string;
    divisionLogoUrl?: string | null;
    className?: string;
};

/**
 * The landing page's dedicated, full-viewport premium hero — distinct
 * from the shared `PortalHero` band every inner page (Schedule, FAQs,
 * Sports directory, ...) still reuses for its simple title/description
 * header. Building a second component here rather than expanding
 * `PortalHero` itself keeps every one of those inner pages unaffected.
 */
export function PortalLandingHero({
    eyebrow,
    title,
    description,
    meta,
    actions,
    startsAtIso,
    divisionLogoUrl,
    className,
}: PortalLandingHeroProps) {
    return (
        <section
            className={cn(
                'relative flex min-h-[78vh] flex-col justify-center overflow-hidden rounded-[var(--portal-radius)] border-b-4 border-[var(--portal-accent)] px-6 py-14 text-[var(--portal-ink-foreground)] sm:px-10 sm:py-20',
                className,
            )}
        >
            <PortalHeroBackground />

            <div className="portal-hero-in relative mx-auto flex w-full max-w-4xl flex-col items-center gap-5 text-center">
                <div className="flex items-center gap-4">
                    <PortalTorchIcon className="h-16 w-auto text-[var(--portal-ink-foreground)] sm:h-20" />
                    {divisionLogoUrl && (
                        <img
                            src={divisionLogoUrl}
                            alt=""
                            className="size-14 rounded-full border-2 border-white/30 object-cover sm:size-[72px]"
                        />
                    )}
                </div>

                {eyebrow && (
                    <p className="rounded-full border border-white/25 bg-white/10 px-4 py-1.5 text-xs font-semibold tracking-[0.15em] text-[var(--portal-ink-foreground)]/90 uppercase backdrop-blur-sm">
                        {eyebrow}
                    </p>
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

                {actions && <div className="mt-3 flex flex-wrap justify-center gap-3">{actions}</div>}
            </div>
        </section>
    );
}
