import type { PortalTeamAthlete } from '@/apps/portal/types';

/** One athlete row on the Players & Coaches roster — public-safe fields
 * only (name, event, category, school; never birthdate/LRN/photo, see
 * `PortalTeamsController::sportPersonnel()`). */
export function PortalPlayerCard({ athlete }: { athlete: PortalTeamAthlete }) {
    return (
        <li className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-3">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="font-semibold">{athlete.name}</p>
                    <p className="mt-1 text-sm text-[var(--portal-muted-foreground)]">
                        {athlete.event} · {athlete.category}
                    </p>
                    <p className="text-sm text-[var(--portal-muted-foreground)]">
                        {athlete.school}
                    </p>
                </div>
                <span
                    className={`shrink-0 rounded-full px-2.5 py-1 text-xs font-bold ${
                        athlete.is_eligible
                            ? 'bg-emerald-600 text-white'
                            : 'bg-[var(--portal-accent-soft)] text-[var(--portal-accent)]'
                    }`}
                >
                    {athlete.eligibility_status}
                </span>
            </div>
        </li>
    );
}
