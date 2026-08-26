import { Head } from '@inertiajs/react';
import { CalendarClock } from 'lucide-react';
import { PortalDaySwitcher } from '@/apps/portal/components/day-switcher';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalMedalTotalsRow } from '@/apps/portal/components/medal-totals';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import type { PortalAthleticsSlot, PortalDay, PortalMeetSummary, PortalMedalTotals } from '@/apps/portal/types';
import { athletics as publicAthletics } from '@/routes/public';

type Props = {
    meet: PortalMeetSummary;
    days: PortalDay[];
    selectedDay: string | null;
    medalTotals: PortalMedalTotals;
    slots: PortalAthleticsSlot[];
};

const statusLabel: Record<PortalAthleticsSlot['status'], string> = {
    completed: 'Completed',
    ongoing: 'Ongoing',
    upcoming: 'Upcoming',
};

const statusTone: Record<PortalAthleticsSlot['status'], string> = {
    completed: 'bg-[var(--portal-muted)] text-[var(--portal-muted-foreground)]',
    ongoing: 'bg-[var(--portal-live)]/10 text-[var(--portal-live)]',
    upcoming: 'bg-[var(--portal-accent-soft)] text-[var(--portal-accent)]',
};

export default function PortalAthletics({ meet, days, selectedDay, medalTotals, slots }: Props) {
    return (
        <>
            <Head title={`Athletics — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <PortalHero title="Athletics" description="Real schedule and, once validated, top-3 placements — no live per-athlete tracking." />

                <PortalMedalTotalsRow totals={medalTotals} />

                {selectedDay && (
                    <PortalSectionHeader title="Events by day" action={<PortalDaySwitcher days={days} selected={selectedDay} baseUrl={publicAthletics(meet.id).url} />} />
                )}

                {slots.length === 0 ? (
                    <PortalEmptyState icon={CalendarClock} title="Nothing scheduled this day" />
                ) : (
                    <div className="space-y-3">
                        {slots.map((slot) => (
                            <div key={slot.id} className="portal-animate-in rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${statusTone[slot.status]}`}>{statusLabel[slot.status]}</span>
                                    <span className="text-xs text-[var(--portal-muted-foreground)]">
                                        {slot.starts_at}–{slot.ends_at} · {slot.venue}
                                    </span>
                                </div>
                                <p className="mt-2 text-sm font-medium">{slot.event}</p>
                                {slot.top_placements.length > 0 && (
                                    <ol className="mt-2 space-y-1 text-sm">
                                        {slot.top_placements.map((placement) => (
                                            <li key={placement.rank} className="flex justify-between gap-2">
                                                <span>
                                                    {placement.rank}. {placement.athlete}
                                                </span>
                                                <span className="text-[var(--portal-muted-foreground)]">
                                                    {placement.school}
                                                    {placement.mark && <span className="ml-2 font-medium text-[var(--portal-fg)]">{placement.mark}</span>}
                                                </span>
                                            </li>
                                        ))}
                                    </ol>
                                )}
                                {slot.official_as_of && <p className="mt-2 text-xs text-[var(--portal-muted-foreground)]">Official as of {slot.official_as_of}</p>}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
