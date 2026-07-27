import type { ReactNode } from 'react';

type Props = {
    title: string;
    description?: string;
    meta?: ReactNode;
};

/**
 * The public portal's branded page-top band (WP-08-07) — reuses the
 * existing sidebar/primary brand tokens (WP-08-02) rather than
 * introducing new colors, so every public page that adopts it stays in
 * the same palette as the rest of the app.
 */
export function PublicPageHero({ title, description, meta }: Props) {
    return (
        <div className="-mx-4 overflow-hidden bg-gradient-to-r from-sidebar to-primary px-4 py-8 text-white sm:mx-0 sm:rounded-xl sm:px-8">
            <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
                {title}
            </h1>
            {description && (
                <p className="mt-2 max-w-2xl text-sm text-white/80 sm:text-base">
                    {description}
                </p>
            )}
            {meta && <div className="mt-3 text-sm text-white/90">{meta}</div>}
        </div>
    );
}
