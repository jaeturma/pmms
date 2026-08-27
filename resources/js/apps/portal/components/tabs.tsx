import { cn } from '@/apps/portal/lib/utils';

type PortalTab = {
    value: string;
    label: string;
    mobileLabel?: string;
};

type PortalTabsProps = {
    tabs: PortalTab[];
    value: string;
    onChange: (value: string) => void;
    className?: string;
};

/** A pill-style tab strip for switching between whole page views (e.g.
 * Overall / Elementary / Secondary / Top Medalist on the medal tally) —
 * distinct from `PortalSelect`, which narrows one table via a filter
 * rather than swapping the section shown underneath. */
export function PortalTabs({
    tabs,
    value,
    onChange,
    className,
}: PortalTabsProps) {
    return (
        <div
            role="tablist"
            className={cn(
                'inline-flex flex-wrap gap-1 self-center rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-muted)] p-1',
                className,
            )}
        >
            {tabs.map((tab) => {
                const active = tab.value === value;

                return (
                    <button
                        key={tab.value}
                        type="button"
                        role="tab"
                        aria-selected={active}
                        onClick={() => onChange(tab.value)}
                        className={cn(
                            'rounded-[calc(var(--portal-radius)-0.25rem)] px-4 py-1.5 text-sm font-medium transition-colors',
                            active
                                ? 'bg-[var(--portal-surface)] text-[var(--portal-fg)] shadow-sm'
                                : 'text-[var(--portal-muted-foreground)] hover:text-[var(--portal-fg)]',
                        )}
                    >
                        {tab.mobileLabel ? (
                            <>
                                <span className="sm:hidden">
                                    {tab.mobileLabel}
                                </span>
                                <span className="hidden sm:inline">
                                    {tab.label}
                                </span>
                            </>
                        ) : (
                            tab.label
                        )}
                    </button>
                );
            })}
        </div>
    );
}
