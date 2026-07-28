import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

type Props = {
    icon?: LucideIcon;
    title: string;
    description?: string;
    action?: ReactNode;
};

/**
 * Shared no-data state for every admin resource page and both public
 * portal pages (WP-10-09 widens padding/icon size for a more spacious,
 * premium feel — no color or structural change).
 */
export function EmptyState({ icon: Icon, title, description, action }: Props) {
    return (
        <div className="flex flex-col items-center justify-center rounded-xl border border-dashed p-12 text-center">
            {Icon && (
                <div
                    aria-hidden="true"
                    className="mb-5 flex size-14 items-center justify-center rounded-full bg-muted"
                >
                    <Icon className="size-7 text-muted-foreground" />
                </div>
            )}
            <h2 className="text-base font-semibold">{title}</h2>
            {description && (
                <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                    {description}
                </p>
            )}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
