import type { ReactNode } from 'react';
import { cn } from '@/apps/portal/lib/utils';

type PortalSectionHeaderProps = {
    title: string;
    description?: string;
    action?: ReactNode;
    className?: string;
};

export function PortalSectionHeader({ title, description, action, className }: PortalSectionHeaderProps) {
    return (
        <div className={cn('flex items-end justify-between gap-4', className)}>
            <div>
                <h2 className="text-lg font-semibold text-[var(--portal-fg)] sm:text-xl">{title}</h2>
                {description && <p className="mt-0.5 text-sm text-[var(--portal-muted-foreground)]">{description}</p>}
            </div>
            {action}
        </div>
    );
}
