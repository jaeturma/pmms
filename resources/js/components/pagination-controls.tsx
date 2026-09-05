import { Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
};

type Props = {
    page: Pick<Paginated<unknown>, 'current_page' | 'last_page' | 'total'>;
    url: string;
    label: string;
    params?: Record<string, string>;
    pageName?: string;
};

export function PaginationControls({
    page,
    url,
    label,
    params = {},
    pageName = 'page',
}: Props) {
    if (page.last_page <= 1) {
        return null;
    }

    const go = (target: number) => {
        router.get(
            url,
            { ...params, [pageName]: String(target) },
            { preserveState: true, preserveScroll: true },
        );
    };
    const start = Math.max(
        1,
        Math.min(page.current_page - 1, page.last_page - 2),
    );
    const numbers = [
        ...new Set([
            start,
            start + 1,
            start + 2,
            page.last_page - 2,
            page.last_page - 1,
            page.last_page,
        ]),
    ]
        .filter((n) => n > 0 && n <= page.last_page)
        .sort((a, b) => a - b);
    const visiblePages: (number | 'gap')[] = [];
    numbers.forEach((number, i) => {
        if (i > 0 && number > numbers[i - 1] + 1) {
            visiblePages.push('gap');
        }

        visiblePages.push(number);
    });

    return (
        <div className="flex flex-wrap items-center justify-between gap-3">
            <p className="text-sm text-muted-foreground">
                Page {page.current_page} of {page.last_page} ({page.total}{' '}
                {label})
            </p>
            <div className="flex flex-wrap gap-1">
                <Button
                    variant="outline"
                    size="sm"
                    aria-label="First page"
                    disabled={page.current_page === 1}
                    onClick={() => go(1)}
                >
                    {'|<'}
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    disabled={page.current_page === 1}
                    aria-label="Previous page"
                    onClick={() => go(page.current_page - 1)}
                >
                    {'<'}
                </Button>
                {visiblePages.map((number) =>
                    number === 'gap' ? (
                        <span
                            key="gap"
                            className="px-2 py-1"
                            aria-hidden="true"
                        >
                            ...
                        </span>
                    ) : (
                        <Button
                            key={number}
                            variant={
                                number === page.current_page
                                    ? 'default'
                                    : 'outline'
                            }
                            size="sm"
                            asChild
                        >
                            <Link
                                aria-current={
                                    number === page.current_page
                                        ? 'page'
                                        : undefined
                                }
                                aria-label={`Page ${number}`}
                                href={url}
                                data={{ ...params, [pageName]: String(number) }}
                                preserveState
                                preserveScroll
                                prefetch
                            >
                                {number}
                            </Link>
                        </Button>
                    ),
                )}
                <Button
                    variant="outline"
                    size="sm"
                    disabled={page.current_page === page.last_page}
                    aria-label="Next page"
                    onClick={() => go(page.current_page + 1)}
                >
                    {'>'}
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    aria-label="Last page"
                    disabled={page.current_page === page.last_page}
                    onClick={() => go(page.last_page)}
                >
                    {'>|'}
                </Button>
            </div>
        </div>
    );
}
