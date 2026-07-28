import { Skeleton } from '@/components/ui/skeleton';

type Props = {
    rows?: number;
};

/**
 * A generic placeholder for the brief window between a filter change and
 * the fresh Inertia visit landing (WP-08.5-06's "loading skeletons") —
 * shown while `router.get`'s `onStart`/`onFinish` callbacks report a
 * visit in flight. Deliberately generic rather than a pixel match for
 * any one page's real content: the window is short (a local network
 * round trip), so an approximate shape reads better than over-building a
 * skeleton that has to be kept in sync with every table's exact columns.
 */
export function PublicLoadingSkeleton({ rows = 5 }: Props) {
    return (
        <div className="flex flex-col gap-3" role="status">
            <span className="sr-only">Loading…</span>
            {Array.from({ length: rows }, (_, i) => (
                <Skeleton key={i} className="h-12 w-full rounded-xl" />
            ))}
        </div>
    );
}
