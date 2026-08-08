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
 * bespoke layout `PortalHero` itself doesn't support: a large crest above
 * the municipality name, and — per the WP's explicit "small seal at the
 * upper-right" requirement (the Palarong reference layout) — a second,
 * smaller rendering of the *same* crest image pinned to the top-right
 * corner. `District` only has one uploaded logo; this is one asset shown
 * at two sizes, not two separate uploads.
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
            <MunicipalityCrest
                name={team.name}
                logoUrl={team.logo_url}
                size="sm"
                shape="circle"
                className="absolute top-4 right-4 sm:top-6 sm:right-6"
            />

            <div className="flex flex-col items-center gap-3 text-center">
                <MunicipalityCrest
                    name={team.name}
                    logoUrl={team.logo_url}
                    size="lg"
                    shape="circle"
                />

                <div>
                    <h1 className="text-2xl font-bold uppercase sm:text-4xl">
                        {team.name}
                    </h1>
                    <p className="mt-2 text-xs font-semibold tracking-wide text-[var(--portal-ink-foreground)]/70 uppercase">
                        {meetName} · Municipal Delegation
                    </p>
                    {team.congressional_district && (
                        <p className="mt-1 text-sm text-[var(--portal-ink-foreground)]/80">
                            {team.congressional_district}
                        </p>
                    )}
                </div>

                <p className="text-sm text-[var(--portal-ink-foreground)]/80">
                    Athletes: {team.athlete_count} · Sports: {team.sport_count}
                </p>
            </div>
        </section>
    );
}
