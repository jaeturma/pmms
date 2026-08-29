import { cn } from '@/apps/portal/lib/utils';

type PortalLoadingStateProps = {
    rows?: number;
    className?: string;
};

export function PortalLoadingState({
    rows = 3,
    className,
}: PortalLoadingStateProps) {
    return (
        <div
            className={cn('space-y-3', className)}
            role="status"
            aria-label="Loading"
        >
            {Array.from({ length: rows }, (_, i) => (
                <div
                    key={i}
                    className="h-16 animate-pulse rounded-[var(--portal-radius)] bg-[var(--portal-muted)]"
                />
            ))}
        </div>
    );
}
