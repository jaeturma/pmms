import { Link } from '@inertiajs/react';
import { MunicipalityCrest } from '@/apps/portal/components/municipality-crest';
import type { PortalMunicipalityTeam } from '@/apps/portal/types';
import { show as teamShow } from '@/routes/public/teams';

const MEDAL_STATS = [
    { key: 'gold', label: 'Gold', className: 'text-[var(--portal-accent)]' },
    { key: 'silver', label: 'Silver', className: 'text-[oklch(0.5_0.01_258)]' },
    {
        key: 'bronze',
        label: 'Bronze',
        className: 'text-[var(--portal-maroon)]',
    },
] as const;

/**
 * One municipality's card on the Teams index (WP: public municipal teams,
 * Stage 3) — logo, name, congressional district, athlete/sport counts, and
 * a Gold/Silver/Bronze mini-strip with real text labels (WP's "medal type
 * is not communicated by color alone" accessibility rule), linking to the
 * full team profile.
 */
export function PortalTeamCard({ team }: { team: PortalMunicipalityTeam }) {
    return (
        <Link
            href={teamShow(team.slug).url}
            className="flex flex-col items-center gap-2 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-5 text-center transition-shadow hover:shadow-md focus-visible:ring-2 focus-visible:ring-[var(--portal-accent)] focus-visible:outline-none"
        >
            <MunicipalityCrest
                name={team.name}
                logoUrl={team.logo_url}
                size="lg"
            />

            <h3 className="text-lg font-bold uppercase">{team.name}</h3>

            {team.congressional_district && (
                <p className="text-xs text-[var(--portal-muted-foreground)]">
                    {team.congressional_district}
                </p>
            )}

            <p className="text-xs text-[var(--portal-muted-foreground)]">
                Athletes: {team.athlete_count} · Sports: {team.sport_count}
            </p>

            <dl className="mt-1 flex items-center gap-3 text-xs font-semibold">
                {MEDAL_STATS.map(({ key, label, className }) => (
                    <div key={key} className="flex items-center gap-1">
                        <dt className={className}>{label}</dt>
                        <dd className="tabular-nums">{team.medals[key]}</dd>
                    </div>
                ))}
            </dl>

            <span className="mt-1 text-sm font-semibold text-[var(--portal-accent)]">
                View team →
            </span>
        </Link>
    );
}
