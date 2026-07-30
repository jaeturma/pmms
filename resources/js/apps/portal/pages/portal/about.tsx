import { Head, usePage } from '@inertiajs/react';
import { School, Trophy, Users } from 'lucide-react';
import { PortalHero } from '@/apps/portal/components/hero';
import type { PortalMeetSummary } from '@/apps/portal/types';

type Props = {
    meet: PortalMeetSummary;
    municipalityCount: number;
    schoolCount: number;
    sportCount: number;
};

type PortalAboutPageProps = {
    division: { type: string; name: string; areaLabel: string };
};

export default function PortalAbout({ meet, municipalityCount, schoolCount, sportCount }: Props) {
    const { props } = usePage<PortalAboutPageProps>();

    return (
        <>
            <Head title={`About — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PortalHero eyebrow="About" title={meet.name} description={`Organized by ${props.division.name} (${props.division.areaLabel}).`} />

                <section className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div className="flex flex-col items-center gap-1 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-5 text-center">
                        <Users aria-hidden="true" className="size-6 text-[var(--portal-accent)]" />
                        <p className="text-2xl font-bold tabular-nums">{municipalityCount}</p>
                        <p className="text-xs text-[var(--portal-muted-foreground)]">Competing municipalities</p>
                    </div>
                    <div className="flex flex-col items-center gap-1 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-5 text-center">
                        <School aria-hidden="true" className="size-6 text-[var(--portal-accent)]" />
                        <p className="text-2xl font-bold tabular-nums">{schoolCount}</p>
                        <p className="text-xs text-[var(--portal-muted-foreground)]">Participating schools</p>
                    </div>
                    <div className="flex flex-col items-center gap-1 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-5 text-center">
                        <Trophy aria-hidden="true" className="size-6 text-[var(--portal-accent)]" />
                        <p className="text-2xl font-bold tabular-nums">{sportCount}</p>
                        <p className="text-xs text-[var(--portal-muted-foreground)]">Sports contested</p>
                    </div>
                </section>
            </div>
        </>
    );
}
