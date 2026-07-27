import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

export type BottomNavItem = {
    label: string;
    href: string;
    icon: LucideIcon;
};

/**
 * Mobile-only bottom tab bar (WP-08-09) — the reference's mobile shell
 * moves site navigation here instead of the header's horizontal nav
 * (hidden below `sm:` in `PublicLayout`). Padded for the iOS home-
 * indicator safe area so the bar never sits under it.
 */
export function PublicBottomNav({
    items,
    currentPath,
}: {
    items: BottomNavItem[];
    currentPath: string;
}) {
    return (
        <nav
            aria-label="Site sections"
            className="fixed inset-x-0 bottom-0 z-40 border-t bg-background sm:hidden"
        >
            <div className="mx-auto flex max-w-5xl justify-around px-1 pt-1 pb-[calc(0.25rem+env(safe-area-inset-bottom))]">
                {items.map((item) => {
                    const isActive = item.href === currentPath;

                    return (
                        <Link
                            key={item.label}
                            href={item.href}
                            aria-current={isActive ? 'page' : undefined}
                            className={cn(
                                'flex flex-1 flex-col items-center gap-0.5 rounded-md px-1 py-1.5 text-[0.65rem] font-medium',
                                isActive
                                    ? 'text-primary'
                                    : 'text-muted-foreground',
                            )}
                        >
                            <item.icon aria-hidden="true" className="size-5" />
                            {item.label}
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
