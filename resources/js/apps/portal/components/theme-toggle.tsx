import { Moon, Sun } from 'lucide-react';
import { usePortalTheme } from '@/apps/portal/lib/use-portal-theme';
import { cn } from '@/apps/portal/lib/utils';

type PortalThemeToggleProps = {
    className?: string;
};

/**
 * Day/Night switch for the public portal only — a real `<button>` (native
 * keyboard activation, no click-handler-on-a-div), Moon shown while in Day
 * Mode (the action it performs), Sun shown while in Night Mode, matching
 * `PortalHeader`'s other icon-only buttons (e.g. the mobile menu toggle)
 * in size and hover treatment for visual consistency.
 */
export function PortalThemeToggle({ className }: PortalThemeToggleProps) {
    const { theme, toggleTheme } = usePortalTheme();
    const isDark = theme === 'dark';
    const label = isDark ? 'Switch to day mode' : 'Switch to night mode';

    return (
        <button
            type="button"
            onClick={toggleTheme}
            aria-label={label}
            title={label}
            className={cn(
                'inline-flex size-9 shrink-0 items-center justify-center rounded-[calc(var(--portal-radius)-0.25rem)] text-[var(--portal-accent-foreground)] hover:bg-[var(--portal-accent-foreground)]/10',
                className,
            )}
        >
            {isDark ? <Sun aria-hidden="true" /> : <Moon aria-hidden="true" />}
        </button>
    );
}
