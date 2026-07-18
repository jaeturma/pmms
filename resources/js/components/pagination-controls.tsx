import { router } from '@inertiajs/react';
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
};

export function PaginationControls({ page, url, label, params = {} }: Props) {
    if (page.last_page <= 1) {
        return null;
    }

    const go = (target: number) => {
        router.get(
            url,
            { ...params, page: String(target) },
            { preserveState: true, preserveScroll: true },
        );
    };

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
