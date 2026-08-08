import { FileText } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';

/**
 * The sport's full mini-portal description — locally authored copy
 * (`Sport.description`, `SportsCatalogSeeder`), never fabricated rule
 * detail. `null` (a sport an admin hasn't filled in yet) renders an
 * honest empty state rather than a blank gap in the page.
 */
export function PortalSportDescription({ description }: { description: string | null }) {
    if (description === null) {
        return <PortalEmptyState icon={FileText} title="No description yet" />;
    }

    return (
        <div className="space-y-3 text-sm leading-relaxed text-[var(--portal-fg)] sm:text-base">
            {description.split('\n\n').map((paragraph, index) => (
                <p key={index}>{paragraph}</p>
            ))}
        </div>
    );
}
