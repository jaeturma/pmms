import type { LucideIcon } from 'lucide-react';
import { cn } from '@/apps/portal/lib/utils';

type PortalEmptyStateProps = {
    icon: LucideIcon;
    title: string;
    description?: string;
    className?: string;
};

export function PortalEmptyState({ icon: Icon, title, description, className }: PortalEmptyStateProps) {
    return (
        <div
            className={cn(
                'flex flex-col items-center gap-2 rounded-[var(--portal-radius)] border border-dashed border-[var(--portal-border)] px-6 py-10 text-center',
                className,
            )}
        >
            <Icon aria-hidden="true" className="size-8 text-[var(--portal-muted-foreground)]" />
            <p className="text-sm font-medium text-[var(--portal-fg)]">{title}</p>
            {description && <p className="text-sm text-[var(--portal-muted-foreground)]">{description}</p>}
        </div>
    );
}
