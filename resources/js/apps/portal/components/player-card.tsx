import type { PortalTeamAthlete } from '@/apps/portal/types';

/** One athlete row on the Players & Coaches roster — public-safe fields
 * only (name, event, category, school; never birthdate/LRN/photo, see
 * `PortalTeamsController::sportPersonnel()`). */
export function PortalPlayerCard({ athlete }: { athlete: PortalTeamAthlete }) {
    return (
        <li className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-3">
            <p className="font-semibold">{athlete.name}</p>
            <p className="text-sm text-[var(--portal-muted-foreground)]">
                {athlete.category} · {athlete.event}
            </p>
            <p className="text-sm text-[var(--portal-muted-foreground)]">
                {athlete.school}
            </p>
        </li>
    );
}
