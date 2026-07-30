import { Link } from '@inertiajs/react';
import { cn } from '@/apps/portal/lib/utils';

export type PortalNavItem = {
    label: string;
    href: string;
    active?: boolean;
};

type PortalNavigationProps = {
    items: PortalNavItem[];
    className?: string;
    itemClassName?: string;
    onNavigate?: () => void;
};

export function PortalNavigation({ items, className, itemClassName, onNavigate }: PortalNavigationProps) {
    return (
        <nav className={cn('flex items-center gap-1', className)} aria-label="Primary">
            {items.map((item) => (
                <Link
                    key={item.href}
                    href={item.href}
                    onClick={onNavigate}
                    className={cn(
                        'rounded-[calc(var(--portal-radius)-0.25rem)] px-3 py-2 text-sm font-medium transition-colors',
                        item.active
                            ? 'bg-[var(--portal-accent-soft)] text-[var(--portal-accent)]'
                            : 'text-[var(--portal-fg)]/80 hover:bg-[var(--portal-muted)] hover:text-[var(--portal-fg)]',
                        itemClassName,
                    )}
                >
                    {item.label}
                </Link>
            ))}
        </nav>
    );
}
