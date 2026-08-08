import { ShieldCheck } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import type { PortalTechnicalOfficial } from '@/apps/portal/types';

/**
 * Technical Officials for this sport (`sport_user`, see
 * `PortalController::sportProfileTechnicalOfficials()`) — `duty` renders
 * as the generic "Technical Official" label when `null` (no admin form
 * sets it yet). Public-safe fields only: name and duty — never phone/
 * email/address/birth date/employee id/medical/account.
 */
export function PortalTechnicalOfficials({ officials }: { officials: PortalTechnicalOfficial[] }) {
    if (officials.length === 0) {
        return <PortalEmptyState icon={ShieldCheck} title="Not yet assigned" description="Technical officials appear here once assigned." />;
    }

    return (
        <ul className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            {officials.map((official, index) => (
                <li
                    key={`${official.name}-${index}`}
                    className="flex items-center gap-3 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-3"
                >
                    <span className="portal-icon-badge size-10 shrink-0 bg-[var(--portal-maroon-soft)] text-[var(--portal-maroon)]">
                        <ShieldCheck aria-hidden="true" className="size-5" />
                    </span>
                    <span className="min-w-0">
                        <span className="block truncate font-medium text-[var(--portal-fg)]">{official.name}</span>
                        <span className="block text-xs text-[var(--portal-muted-foreground)]">{official.duty ?? 'Technical Official'}</span>
                    </span>
                </li>
            ))}
        </ul>
    );
}
