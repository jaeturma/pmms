import * as React from 'react';
import { SidebarInset } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import type { AppVariant } from '@/types';

type Props = React.ComponentProps<'main'> & {
    variant?: AppVariant;
};

export function AppContent({
    variant = 'sidebar',
    children,
    className,
    ...props
}: Props) {
    if (variant === 'sidebar') {
        return (
            <SidebarInset
                data-backend="true"
                className={cn(
                    'bg-gradient-to-br from-amber-50 via-background to-yellow-100/70 dark:from-amber-950/35 dark:via-background dark:to-yellow-950/25',
                    className,
                )}
                {...props}
            >
                {children}
            </SidebarInset>
        );
    }

    return (
        <main
            data-backend="true"
            className={cn(
                'mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl bg-gradient-to-br from-amber-50 via-background to-yellow-100/70 dark:from-amber-950/35 dark:via-background dark:to-yellow-950/25',
                className,
            )}
            {...props}
        >
            {children}
        </main>
    );
}
