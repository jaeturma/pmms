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
    const windowStart =
        page.current_page % 3 === 0
            ? page.current_page
            : Math.floor((page.current_page - 1) / 3) * 3 + 1;
    const visiblePages = Array.from(
        { length: Math.min(3, page.last_page - windowStart + 1) },
        (_, index) => windowStart + index,
    );

    return (
        <div className="flex items-center justify-between">
            <p className="text-sm text-muted-foreground">
                Page {page.current_page} of {page.last_page} ({page.total}{' '}
                {label})
            </p>
            <div className="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    disabled={page.current_page === 1}
                    onClick={() => go(page.current_page - 1)}
                >
                    Previous
                </Button>
                {visiblePages.map((number) => (
                    <Button
                        key={number}
                        variant={
                            number === page.current_page ? 'default' : 'outline'
                        }
                        size="sm"
                        asChild
                    >
                        <Link
                            href={url}
                            data={{ ...params, [pageName]: String(number) }}
                            preserveState
                            preserveScroll
                            prefetch
                        >
                            {number}
                        </Link>
                    </Button>
                ))}
                <Button
                    variant="outline"
                    size="sm"
                    disabled={page.current_page === page.last_page}
                    onClick={() => go(page.current_page + 1)}
                >
                    Next
                </Button>
            </div>
        </div>
    );
}
