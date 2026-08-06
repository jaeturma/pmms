import { Head } from '@inertiajs/react';
import { CalendarDays, MapPin } from 'lucide-react';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import type { PortalMeetSummary } from '@/apps/portal/types';

type Props = {
    meet: PortalMeetSummary;
};

export default function PortalContact({ meet }: Props) {
    return (
        <>
            <Head title={`Contact — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PortalHero title="Contact" description="Meet information and quick links." />

                <section className="space-y-3">
                    <PortalSectionHeader title="Meet details" />
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="flex items-center gap-3 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4 text-sm">
                            <span className="portal-icon-badge size-12 shrink-0 bg-[var(--portal-accent-soft)] text-[var(--portal-accent)]">
                                <CalendarDays aria-hidden="true" className="size-6" />
                            </span>
                            <span>
                                {meet.starts_at} – {meet.ends_at} (SY {meet.school_year})
                            </span>
                        </div>
                        {meet.venue && (
                            <div className="flex items-center gap-3 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4 text-sm">
                                <span className="portal-icon-badge size-12 shrink-0 bg-[var(--portal-maroon-soft)] text-[var(--portal-maroon)]">
                                    <MapPin aria-hidden="true" className="size-6" />
                                </span>
                                <span>{meet.venue}</span>
                            </div>
                        )}
                    </div>
                </section>
            </div>
        </>
    );
}
