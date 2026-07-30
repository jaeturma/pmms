import * as DropdownMenu from '@radix-ui/react-dropdown-menu';
import { Link } from '@inertiajs/react';
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
                    className="flex items-center gap-1 rounded-[calc(var(--portal-radius)-0.25rem)] px-3 py-2 text-sm font-medium text-[var(--portal-fg)]/80 transition-colors hover:bg-[var(--portal-muted)] hover:text-[var(--portal-fg)]"
                >
                    {trigger ?? label}
                    <ChevronDown aria-hidden="true" className="size-3.5" />
                </button>
            </DropdownMenu.Trigger>
            <DropdownMenu.Portal>
                <DropdownMenu.Content
                    align="start"
                    sideOffset={6}
                    className="z-50 min-w-[12rem] rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-1 text-[var(--portal-surface-foreground)] shadow-lg"
                >
                    {items.map((item) => (
                        <DropdownMenu.Item key={item.href} asChild>
                            <Link
                                href={item.href}
                                className="block rounded-[calc(var(--portal-radius)-0.25rem)] px-3 py-1.5 text-sm outline-none transition-colors hover:bg-[var(--portal-muted)] data-highlighted:bg-[var(--portal-muted)]"
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
