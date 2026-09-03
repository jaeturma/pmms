import { UserCog } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import type { PortalSportPersonnelAssignment } from '@/apps/portal/types';

/**
 * Tournament Manager/Assistant/Track/Field/Boys/Girls/Category TM,
 * Tournament Secretary, and Tournament ICT for this sport's meet
 * inclusion (`MeetSportAssignment`, see `PortalController::
 * sportProfileTournamentManagement()`) — genuinely supports several
 * people per role, so this renders every row, not one-per-role.
 * Public-safe fields only: name, role, category, lead flag — never
 * phone/email/address/birth date/employee id/medical/account.
 */
export function PortalTournamentManagement({ assignments }: { assignments: PortalSportPersonnelAssignment[] }) {
    if (assignments.length === 0) {
        return <PortalEmptyState icon={UserCog} title="Not yet assigned" description="Tournament management personnel appear here once assigned." />;
    }

    return (
        <ul className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            {assignments.map((assignment, index) => (
                <li
                    key={`${assignment.role_label}-${assignment.name}-${index}`}
                    className="flex items-start gap-3 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-3"
                >
                    {assignment.photo_url ? (
                        <img src={assignment.photo_url} alt="" className="size-10 shrink-0 rounded-full object-cover" />
                    ) : (
                        <span className="portal-icon-badge size-10 shrink-0 bg-[var(--portal-ink-soft)] text-[var(--portal-ink)]">
                            <UserCog aria-hidden="true" className="size-5" />
                        </span>
                    )}
                    <span className="min-w-0">
                        <span className="block truncate font-medium text-[var(--portal-fg)]">{assignment.name}</span>
                        <span className="block text-xs text-[var(--portal-muted-foreground)]">
                            {assignment.role_label}
                            {assignment.category && ` · ${assignment.category}`}
                            {assignment.is_lead && ' · Lead'}
                        </span>
                    </span>
                </li>
            ))}
        </ul>
    );
}
