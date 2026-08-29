import { Link } from '@inertiajs/react';
import { cn } from '@/apps/portal/lib/utils';

export type PortalDayOption = {
    value: string;
    label: string;
};

type PortalDaySwitcherProps = {
    days: PortalDayOption[];
    selected: string;
    baseUrl: string;
};

export function PortalDaySwitcher({
    days,
    selected,
    baseUrl,
}: PortalDaySwitcherProps) {
    if (days.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-2" role="tablist" aria-label="Day">
            {days.map((day) => (
                <Link
                    key={day.value}
                    href={`${baseUrl}?date=${day.value}`}
                    preserveScroll
                    role="tab"
                    aria-selected={day.value === selected}
                    className={cn(
                        'rounded-full px-3 py-1.5 text-sm font-medium transition-colors',
                        day.value === selected
                            ? 'bg-[var(--portal-accent)] text-[var(--portal-accent-foreground)]'
                            : 'border border-[var(--portal-border)] text-[var(--portal-fg)] hover:bg-[var(--portal-muted)]',
                    )}
                >
                    {day.label}
                </Link>
            ))}
        </div>
    );
}
