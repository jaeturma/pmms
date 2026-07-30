import { AlertTriangle } from 'lucide-react';
import { cn } from '@/apps/portal/lib/utils';
import { PortalButton } from '@/apps/portal/components/button';

type PortalErrorStateProps = {
    title?: string;
    description?: string;
    onRetry?: () => void;
    className?: string;
};

export function PortalErrorState({
    title = 'Something went wrong',
    description = 'Please try again in a moment.',
    onRetry,
    className,
}: PortalErrorStateProps) {
    return (
        <div
            className={cn(
                'flex flex-col items-center gap-2 rounded-[var(--portal-radius)] border border-[var(--portal-danger)]/30 bg-[var(--portal-danger)]/5 px-6 py-10 text-center',
                className,
            )}
            role="alert"
        >
            <AlertTriangle aria-hidden="true" className="size-8 text-[var(--portal-danger)]" />
            <p className="text-sm font-medium text-[var(--portal-fg)]">{title}</p>
            <p className="text-sm text-[var(--portal-muted-foreground)]">{description}</p>
            {onRetry && (
                <PortalButton variant="outline" size="sm" onClick={onRetry} className="mt-2">
                    Try again
                </PortalButton>
            )}
        </div>
    );
}
