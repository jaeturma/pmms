import type { LucideIcon } from 'lucide-react';
import { cn } from '@/apps/portal/lib/utils';

type PortalEmptyStateTone = 'accent' | 'ink' | 'maroon';

type PortalEmptyStateProps = {
    icon: LucideIcon;
    title: string;
    description?: string;
    tone?: PortalEmptyStateTone;
    className?: string;
};

const toneClasses: Record<PortalEmptyStateTone, string> = {
    accent: 'bg-[var(--portal-accent-soft)] text-[var(--portal-accent)]',
    ink: 'bg-[var(--portal-ink-soft)] text-[var(--portal-ink)]',
    maroon: 'bg-[var(--portal-maroon-soft)] text-[var(--portal-maroon)]',
};

export function PortalEmptyState({ icon: Icon, title, description, tone = 'accent', className }: PortalEmptyStateProps) {
    return (
        <div
            className={cn(
                'flex flex-col items-center gap-3 rounded-[var(--portal-radius)] border border-dashed border-[var(--portal-border)] px-6 py-10 text-center',
                className,
            )}
        >
            <span className={cn('portal-icon-badge size-14', toneClasses[tone])}>
                <Icon aria-hidden="true" className="size-7" />
            </span>
            <p className="text-sm font-medium text-[var(--portal-fg)]">{title}</p>
            {description && <p className="text-sm text-[var(--portal-muted-foreground)]">{description}</p>}
        </div>
    );
}
