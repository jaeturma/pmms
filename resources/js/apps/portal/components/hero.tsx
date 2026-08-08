import type { ReactNode } from 'react';
import { cn } from '@/apps/portal/lib/utils';

type PortalHeroProps = {
    icon?: ReactNode;
    eyebrow?: string;
    title: string;
    description?: string;
    meta?: ReactNode;
    actions?: ReactNode;
    className?: string;
};

/**
 * Full-bleed (a row spanning the viewport, not a rounded card floating
 * inside `PortalLayout`'s padded `<main>`) — same technique as
 * `PortalLandingHero`: negative margins exactly cancel `<main>`'s own
 * padding at each breakpoint (`px-4 sm:px-6 lg:px-10 xl:px-16 2xl:px-24`,
 * `py-8 sm:py-10`), then re-apply the same values as this section's own
 * padding so content still lines up with the rest of the page instead of
 * touching the true edge.
 */
export function PortalHero({ icon, eyebrow, title, description, meta, actions, className }: PortalHeroProps) {
    return (
        <section
            className={cn(
                'portal-hero-gradient portal-animate-in relative -mx-4 -mt-8 overflow-hidden border-b-4 border-[var(--portal-accent)] px-4 py-10 text-[var(--portal-ink-foreground)] sm:-mx-6 sm:-mt-10 sm:px-6 sm:py-14 lg:-mx-10 lg:px-10 xl:-mx-16 xl:px-16 2xl:-mx-24 2xl:px-24',
                className,
            )}
        >
            <div className={cn(icon && 'flex items-start gap-4 sm:items-center sm:gap-5')}>
                {icon}
                <div className="min-w-0">
                    {eyebrow && (
                        <p className="text-xs font-semibold tracking-wide text-[var(--portal-ink-foreground)]/70 uppercase">
                            {eyebrow}
                        </p>
                    )}
                    <h1 className="mt-2 text-2xl font-bold uppercase sm:text-4xl">{title}</h1>
                    {description && (
                        <p className="mt-3 max-w-2xl text-sm text-[var(--portal-ink-foreground)]/80 sm:text-base">{description}</p>
                    )}
                </div>
            </div>
            {meta && <div className="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-[var(--portal-ink-foreground)]/80">{meta}</div>}
            {actions && <div className="mt-6 flex flex-wrap gap-3">{actions}</div>}
        </section>
    );
}
