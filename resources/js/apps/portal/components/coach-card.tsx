import type { PortalTeamCoach } from '@/apps/portal/types';

/** One coach row on the Players & Coaches roster — public-safe fields
 * only (name, role, school; never phone/email, see
 * `PortalTeamsController::sportPersonnel()`). */
export function PortalCoachCard({ coach }: { coach: PortalTeamCoach }) {
    return (
        <li className="rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-3">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="font-semibold">{coach.name}</p>
                    <p className="mt-1 text-sm text-[var(--portal-muted-foreground)]">
                        {coach.role} · {coach.school}
                    </p>
                </div>
                <span
                    className={`shrink-0 rounded-full px-2.5 py-1 text-xs font-bold ${
                        coach.is_accredited
                            ? 'bg-emerald-600 text-white'
                            : 'bg-[var(--portal-accent-soft)] text-[var(--portal-accent)]'
                    }`}
                >
                    {coach.status}
                </span>
            </div>
        </li>
    );
}
