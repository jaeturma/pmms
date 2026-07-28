import type { ReactNode } from 'react';

type Props = {
    title: string;
    description?: string;
    actions?: ReactNode;
};

/**
 * Shared header for every admin resource/report page (WP-10-09) — title
 * matches the public portal's own `text-2xl` heading scale, so the
 * admin app reads at a consistent, more confident type scale without
 * adopting any public-only color or layout treatment.
 */
export function PageHeader({ title, description, actions }: Props) {
    return (
        <header className="flex flex-wrap items-start justify-between gap-4">
            <div className="space-y-1">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {title}
                </h1>
                {description && (
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            {actions && (
                <div className="flex shrink-0 items-center gap-2">
                    {actions}
                </div>
            )}
        </header>
    );
}
