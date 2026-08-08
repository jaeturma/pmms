import { LayoutGrid } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import type { PortalSportCategorySummary } from '@/apps/portal/types';

/**
 * Real `SportCategory` rows for this sport (catalog-wide plus this
 * meet's own scoped categories — see `PortalController::
 * sportProfileCategories()`), not invented from `AgeDivision`/
 * `GenderCategory` combinatorics.
 */
export function PortalSportCategories({ categories }: { categories: PortalSportCategorySummary[] }) {
    if (categories.length === 0) {
        return <PortalEmptyState icon={LayoutGrid} title="No categories configured yet" />;
    }

    return (
        <ul className="flex flex-wrap gap-2">
            {categories.map((category) => (
                <li
                    key={category.id}
                    className="rounded-full border border-[var(--portal-border)] bg-[var(--portal-surface)] px-3 py-1.5 text-sm font-medium text-[var(--portal-fg)]"
                >
                    {category.display_name}
                </li>
            ))}
        </ul>
    );
}
