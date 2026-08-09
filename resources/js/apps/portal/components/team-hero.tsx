import { MunicipalityCrest } from '@/apps/portal/components/municipality-crest';
import { cn } from '@/apps/portal/lib/utils';
import type { PortalMunicipalityProfile } from '@/apps/portal/types';

type PortalTeamHeroProps = {
    meetName: string;
    team: PortalMunicipalityProfile;
    className?: string;
};

/**
 * The municipal team profile's hero — same full-bleed gradient technique
 * as `PortalHero` (negative margins cancel `<main>`'s own padding), but a
 * bespoke layout `PortalHero` itself doesn't support: the municipality's
 * official crest (`logo_url`) and the delegation's own team logo
 * (`team_logo_url`) shown side by side above the name — two separate
 * uploads (`District::logo`/`District::teamLogo`), not one asset at two
 * sizes.
 */
export function PortalTeamHero({
    meetName,
    team,
    className,
}: PortalTeamHeroProps) {
    return (
        <section
            className={cn(
                'portal-hero-gradient portal-animate-in relative -mx-4 -mt-8 overflow-hidden border-b-4 border-[var(--portal-accent)] px-4 py-10 text-[var(--portal-ink-foreground)] sm:-mx-6 sm:-mt-10 sm:px-6 sm:py-14 lg:-mx-10 lg:px-10 xl:-mx-16 xl:px-16 2xl:-mx-24 2xl:px-24',
                className,
            )}
        >
            <div className="flex flex-col items-center gap-3 text-center">
                <div className="flex items-center gap-4">
                    <MunicipalityCrest
                        name={team.name}
                        logoUrl={team.logo_url}
                        size="lg"
                        shape="square"
                    />
                    <MunicipalityCrest
                        name={team.name}
                        logoUrl={team.team_logo_url}
                        size="lg"
                        shape="square"
                    />
                </div>

                <div>
                    <h1 className="text-2xl font-bold uppercase sm:text-4xl">
                        {team.name}
                    </h1>
                    <p className="mt-2 text-xs font-semibold tracking-wide text-[var(--portal-ink-foreground)]/70 uppercase">
                        {meetName} · Municipal Delegation
                    </p>
                </div>

                <p className="text-sm text-[var(--portal-ink-foreground)]/80">
                    Athletes: {team.athlete_count} · Sports: {team.sport_count}
                </p>
            </div>
        </section>
    );
}
