import type { PortalTeamCoach } from '@/apps/portal/types';

/** One coach row on the Players & Coaches roster — public-safe fields
 * only (name, role, school; never phone/email, see
 * `PortalTeamsController::sportPersonnel()`). */
export function PortalCoachCard({ coach }: { coach: PortalTeamCoach }) {
    return (
        <li className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-3">
            <p className="font-semibold">{coach.name}</p>
            <p className="text-sm text-[var(--portal-muted-foreground)]">
                {coach.role}
            </p>
            <p className="text-sm text-[var(--portal-muted-foreground)]">
                {coach.school}
            </p>
        </li>
    );
}
