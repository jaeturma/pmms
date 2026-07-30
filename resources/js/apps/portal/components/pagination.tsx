import { Link } from '@inertiajs/react';
import { cn } from '@/apps/portal/lib/utils';

export type PortalPaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PortalPaginationProps = {
    links: PortalPaginationLink[];
};

/** Matches Laravel's `LengthAwarePaginator::toArray()['links']` shape
 * exactly (`url`/`label`/`active`) — the same paginator every
 * `->paginate()->withQueryString()` call already serializes, so no
 * page needs to reshape it before passing it here. */
export function PortalPagination({ links }: PortalPaginationProps) {
    if (links.length <= 1) {
        return null;
    }

    return (
        <nav className="flex flex-wrap items-center gap-1" aria-label="Pagination">
            {links.map((link, index) =>
                link.url === null ? (
                    <span
                        key={index}
                        className="rounded-[calc(var(--portal-radius)-0.25rem)] px-3 py-1.5 text-sm text-[var(--portal-muted-foreground)]"
                        // eslint-disable-next-line react/no-danger -- Laravel's own "&laquo;"/"&raquo;" labels
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ) : (
                    <Link
                        key={index}
                        href={link.url}
                        preserveScroll
                        className={cn(
                            'rounded-[calc(var(--portal-radius)-0.25rem)] px-3 py-1.5 text-sm transition-colors',
                            link.active
                                ? 'bg-[var(--portal-accent)] text-[var(--portal-accent-foreground)]'
                                : 'text-[var(--portal-fg)] hover:bg-[var(--portal-muted)]',
                        )}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ),
            )}
        </nav>
    );
}
