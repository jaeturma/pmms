import { Link } from '@inertiajs/react';
import * as DropdownMenu from '@radix-ui/react-dropdown-menu';
import { ChevronDown } from 'lucide-react';
import type { ReactNode } from 'react';

export type PortalDropdownItem = {
    label: string;
    href: string;
};

type PortalNavDropdownProps = {
    label: string;
    items: PortalDropdownItem[];
    trigger?: ReactNode;
};

export function PortalNavDropdown({ label, items, trigger }: PortalNavDropdownProps) {
    if (items.length === 0) {
        return null;
    }

    return (
        <DropdownMenu.Root>
            <DropdownMenu.Trigger asChild>
                <button
                    type="button"
                    className="flex items-center gap-1 rounded-[calc(var(--portal-radius)-0.25rem)] px-3 py-2 text-sm font-medium text-[var(--portal-accent-foreground)]/80 transition-colors hover:bg-[var(--portal-accent-foreground)]/10 hover:text-[var(--portal-accent-foreground)]"
                >
                    {trigger ?? label}
                    <ChevronDown aria-hidden="true" className="size-3.5" />
                </button>
            </DropdownMenu.Trigger>
            <DropdownMenu.Portal>
                <DropdownMenu.Content
                    align="start"
                    sideOffset={6}
                    // Deliberately hardcoded light colors rather than the
                    // theme-following --portal-surface variable — the menu
                    // stays light and legible regardless of the visitor's
                    // light/dark mode.
                    className="z-50 min-w-[12rem] rounded-[var(--portal-radius)] border border-[oklch(0.89_0.02_90)] bg-[oklch(1_0_0)] p-1 text-[oklch(0.24_0.06_258)] shadow-lg"
                >
                    {items.map((item) => (
                        <DropdownMenu.Item key={item.href} asChild>
                            <Link
                                href={item.href}
                                className="block rounded-[calc(var(--portal-radius)-0.25rem)] px-3 py-1.5 text-sm outline-none transition-colors hover:bg-[oklch(0.95_0.015_90)] data-highlighted:bg-[oklch(0.95_0.015_90)]"
                            >
                                {item.label}
                            </Link>
                        </DropdownMenu.Item>
                    ))}
                </DropdownMenu.Content>
            </DropdownMenu.Portal>
        </DropdownMenu.Root>
    );
}
