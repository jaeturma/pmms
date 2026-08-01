import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/apps/portal/lib/utils';

export type PortalQuickNavItem = {
    label: string;
    href: string;
    icon: LucideIcon;
    /** Marks a real-time destination (e.g. "Live now") with a pulsing
     * dot — reuses the same `portal-live-dot` primitive every other live
     * indicator in the portal already uses, not a new one-off badge. */
    live?: boolean;
};

type PortalQuickNavProps = {
    items: PortalQuickNavItem[];
    className?: string;
};

const STAGGER_CAP = 12;

/**
 * The landing page's animated quick-links strip — every destination is a
 * real route this app already serves (no invented pages). Cards fade up
 * staggered on mount, then lift slightly on hover/focus.
 */
export function PortalQuickNav({ items, className }: PortalQuickNavProps) {
    return (
        <nav aria-label="Quick navigation" className={cn('grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6', className)}>
            {items.map((item, index) => {
                const Icon = item.icon;

                return (
                    <Link
                        key={item.href}
                        href={item.href}
                        className="portal-animate-in group flex flex-col items-center gap-2.5 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] px-3 py-5 text-center transition-all duration-200 ease-[var(--portal-ease)] hover:-translate-y-1 hover:border-[var(--portal-accent)] hover:shadow-[0_10px_28px_rgba(0,0,0,0.10)] focus-visible:-translate-y-1 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--portal-accent)]"
                        style={{ animationDelay: `${Math.min(index, STAGGER_CAP) * 60}ms` }}
                    >
                        <span className="relative flex size-12 items-center justify-center rounded-full bg-[var(--portal-accent-soft)] text-[var(--portal-accent)] transition-transform duration-200 group-hover:scale-110">
                            <Icon aria-hidden="true" className="size-6" />
                            {item.live && (
                                <span
                                    aria-hidden="true"
                                    className="portal-live-dot absolute -top-0.5 -right-0.5 size-3 rounded-full bg-[var(--portal-live)] ring-2 ring-[var(--portal-surface)]"
                                />
                            )}
                        </span>
                        <span className="text-sm font-semibold text-[var(--portal-fg)]">{item.label}</span>
                    </Link>
                );
            })}
        </nav>
    );
}
