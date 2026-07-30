import type { ReactNode } from 'react';
import { cn } from '@/apps/portal/lib/utils';

type PortalHeroProps = {
    eyebrow?: string;
    title: string;
    description?: string;
    meta?: ReactNode;
    actions?: ReactNode;
    className?: string;
};

export function PortalHero({ eyebrow, title, description, meta, actions, className }: PortalHeroProps) {
    return (
        <section
            className={cn(
                'portal-animate-in rounded-[var(--portal-radius)] bg-[var(--portal-ink)] px-6 py-10 text-[var(--portal-ink-foreground)] sm:px-10 sm:py-14',
                className,
            )}
        >
            {eyebrow && (
                <p className="text-xs font-semibold tracking-wide text-[var(--portal-ink-foreground)]/70 uppercase">
                    {eyebrow}
                </p>
            )}
            <h1 className="mt-2 text-2xl font-bold sm:text-4xl">{title}</h1>
            {description && <p className="mt-3 max-w-2xl text-sm text-[var(--portal-ink-foreground)]/80 sm:text-base">{description}</p>}
            {meta && <div className="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-[var(--portal-ink-foreground)]/80">{meta}</div>}
            {actions && <div className="mt-6 flex flex-wrap gap-3">{actions}</div>}
        </section>
    );
}
