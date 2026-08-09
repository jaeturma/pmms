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
 * Stage 3) — team logo large and centered, municipal crest pinned small in
 * the top-right corner, name, nickname, and a Gold/Silver/Bronze mini-strip
 * with real text labels (WP's "medal type is not communicated by color
 * alone" accessibility rule), the whole card linking to the full team
 * profile. `logo_url` (the municipality's official crest) and
 * `team_logo_url` (the delegation's own logo) are two separate uploads —
 * see `District::logo`/`District::teamLogo`.
 */
export function PortalTeamCard({ team }: { team: PortalMunicipalityTeam }) {
    return (
        <Link
            href={teamShow(team.slug).url}
            className="relative flex flex-col items-center gap-2 rounded-[var(--portal-radius)] border border-[var(--portal-border)]/60 bg-[var(--portal-surface)]/60 p-5 text-center backdrop-blur-md transition-shadow hover:shadow-md focus-visible:ring-2 focus-visible:ring-[var(--portal-accent)] focus-visible:outline-none"
        >
            <MunicipalityCrest
                name={team.name}
                logoUrl={team.logo_url}
                size="md"
                shape="square"
                className="absolute top-3 right-3"
            />

            <MunicipalityCrest
                name={team.name}
                logoUrl={team.team_logo_url}
                size="xl"
                shape="square"
            />

            <h3 className="text-lg font-bold uppercase">{team.name}</h3>

            {team.nickname && (
                <p className="text-2xl text-[var(--portal-muted-foreground)]">
                    "{team.nickname}"
                </p>
            )}

            <dl className="mt-1 flex items-center gap-3 text-xs font-semibold">
                {MEDAL_STATS.map(({ key, label, className }) => (
                    <div key={key} className="flex items-center gap-1">
                        <dt className={className}>{label}</dt>
                        <dd className="tabular-nums">{team.medals[key]}</dd>
                    </div>
                ))}
            </dl>
        </Link>
    );
}
