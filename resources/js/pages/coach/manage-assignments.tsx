import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Eye } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

type EventOption = { id: number; name: string; sport: string; category: string };
type Registration = {
    id: number; coach: string; email: string; sport: string; meet: string;
    team: string | null; status: string; profile_url: string | null;
    certification_url: string | null; certification_mime_type: string | null;
    selected_events: string;
    registered_athletes: Array<{ id: number; name: string; school: string; events: string; profile_url: string; photo_url: string | null }>;
};

export default function ManageCoachAssignments({ registration, events, selectedEventIds, canApprove }: {
    registration: Registration; events: EventOption[]; selectedEventIds: number[]; canApprove: boolean;
}) {
    const form = useForm({ event_ids: selectedEventIds });
    const [showCertificate, setShowCertificate] = useState(false);
    const [activeTab, setActiveTab] = useState<'events' | 'athletes'>('events');
    const toggle = (id: number, checked: boolean) => form.setData('event_ids', checked ? [...form.data.event_ids, id] : form.data.event_ids.filter((eventId) => eventId !== id));
    const eventsBySport = events.reduce<Record<string, EventOption[]>>((groups, event) => {
        (groups[event.sport] ??= []).push(event);

        return groups;
    }, {});
    const toggleSport = (sportEvents: EventOption[], checked: boolean) => {
        const sportEventIds = sportEvents.map((event) => event.id);
        form.setData('event_ids', checked
            ? [...new Set([...form.data.event_ids, ...sportEventIds])]
            : form.data.event_ids.filter((eventId) => !sportEventIds.includes(eventId)));
    };

    return <div className="space-y-6 p-4">
        <Head title={`Manage ${registration.coach}'s Events`} />
        <PageHeader title="Manage Coach Sports Events" description={`${registration.meet} · Event assignment`} actions={<Button variant="outline" asChild><Link href="/coach/assignment-requests"><ArrowLeft />Back</Link></Button>} />
        <section className="grid gap-5 rounded-xl border p-5 md:grid-cols-[9rem_1fr]">
            <div className="flex size-36 items-center justify-center overflow-hidden rounded-xl border bg-muted text-2xl font-semibold">{registration.profile_url ? <img src={registration.profile_url} alt={`${registration.coach} profile`} className="size-full object-cover" /> : registration.coach.split(' ').map((part) => part[0]).slice(0, 2).join('')}</div>
            <div className="space-y-4">
                <div className="flex flex-wrap items-start justify-between gap-3"><div><h2 className="text-xl font-semibold">{registration.coach}</h2><p className="text-sm text-muted-foreground">{registration.email}</p></div><Badge variant="outline">{registration.status}</Badge></div>
                <dl className="grid gap-3 text-sm sm:grid-cols-2"><div><dt className="text-muted-foreground">Team / Delegation</dt><dd className="font-medium">{registration.team ?? 'Not assigned'}</dd></div><div><dt className="text-muted-foreground">Sports</dt><dd className="font-medium">{registration.sport}</dd></div><div className="sm:col-span-2"><dt className="text-muted-foreground">Current events</dt><dd className="font-medium">{registration.selected_events || 'No events selected'}</dd></div></dl>
                {registration.certification_url ? <Button type="button" variant="outline" onClick={() => setShowCertificate(true)}><Eye />View certification</Button> : <p className="text-sm text-muted-foreground">No coaching certificate uploaded (optional).</p>}
            </div>
        </section>
        <div className="flex gap-1 rounded-lg border bg-muted/40 p-1" role="tablist">
            <button type="button" role="tab" aria-selected={activeTab === 'events'} className={`rounded-md px-4 py-2 text-sm font-medium ${activeTab === 'events' ? 'bg-background shadow-sm' : 'text-muted-foreground'}`} onClick={() => setActiveTab('events')}>Available Events</button>
            <button type="button" role="tab" aria-selected={activeTab === 'athletes'} className={`rounded-md px-4 py-2 text-sm font-medium ${activeTab === 'athletes' ? 'bg-background shadow-sm' : 'text-muted-foreground'}`} onClick={() => setActiveTab('athletes')}>Registered Athletes ({registration.registered_athletes.length})</button>
        </div>
        {activeTab === 'events' ? <form className="space-y-4" onSubmit={(event) => {
 event.preventDefault(); form.put(`/coach/onboarding-requests/${registration.id}/assignments`, { preserveScroll: true }); 
}}>
            <div><h2 className="font-semibold">Available events</h2><p className="text-sm text-muted-foreground">Select one or more events. Saving assignments does not approve the coach account; ICT approval is separate.</p></div>
            <div className="space-y-6">{Object.entries(eventsBySport).map(([sport, sportEvents]) => {
                const allSelected = sportEvents.every((event) => form.data.event_ids.includes(event.id));

                return <section key={sport} className="space-y-3 rounded-xl border p-4">
                    <label className="flex cursor-pointer items-center gap-3 border-b pb-3 font-semibold">
                        <Checkbox checked={allSelected} onCheckedChange={(checked) => toggleSport(sportEvents, checked === true)} />
                        <span>{sport} <span className="font-normal text-muted-foreground">({sportEvents.length} events)</span></span>
                    </label>
                    <div className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">{sportEvents.map((event) => <label key={event.id} className="flex cursor-pointer items-start gap-3 rounded-lg border p-4 hover:bg-muted/50"><Checkbox checked={form.data.event_ids.includes(event.id)} onCheckedChange={(checked) => toggle(event.id, checked === true)} /><span><span className="block font-medium">{event.name}</span><span className="text-sm text-muted-foreground">{event.category}</span></span></label>)}</div>
                </section>;
            })}</div>
            <InputError message={form.errors.event_ids} />
            <div className="flex items-center gap-3"><Button disabled={form.processing || form.data.event_ids.length === 0}>Save event assignments</Button>{canApprove && registration.status !== 'approved' && <span className="text-sm text-muted-foreground">Return to Coach Sports Events after saving to approve the account.</span>}</div>
        </form> : <section className="space-y-3">
            <div><h2 className="font-semibold">Registered Athletes</h2><p className="text-sm text-muted-foreground">Athletes submitted by this coach under the assigned delegation.</p></div>
            {registration.registered_athletes.length === 0 ? <div className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">No athletes registered by this coach.</div> : <div className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">{registration.registered_athletes.map((athlete) => <a key={athlete.id} href={athlete.profile_url} className="flex items-center gap-3 rounded-xl border p-4 hover:bg-muted/50"><div className="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-full border bg-muted font-semibold">{athlete.photo_url ? <img src={athlete.photo_url} alt={`${athlete.name} profile`} className="size-full object-cover" loading="lazy" /> : athlete.name.charAt(0)}</div><div className="min-w-0"><p className="truncate font-medium">{athlete.name}</p><p className="truncate text-sm text-muted-foreground">{athlete.school}</p><p className="truncate text-xs text-muted-foreground">{athlete.events || 'Entry pending'}</p></div></a>)}</div>}
        </section>}
        <Dialog open={showCertificate} onOpenChange={setShowCertificate}><DialogContent className="flex h-[90vh] max-w-5xl flex-col"><DialogHeader><DialogTitle>{registration.coach} — Coaching Certification</DialogTitle></DialogHeader>{registration.certification_url && (registration.certification_mime_type === 'application/pdf' ? <iframe src={registration.certification_url} title="Coaching certification" className="min-h-0 flex-1 rounded-md border bg-white" /> : <div className="min-h-0 flex-1 overflow-auto rounded-md border bg-muted/30"><img src={registration.certification_url} alt="Coaching certification" className="size-full object-contain" /></div>)}</DialogContent></Dialog>
    </div>;
}
