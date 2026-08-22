import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Siren } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { store as storeLog } from '@/routes/emergency-communication-logs';
import {
    index,
    status as updateIncidentStatus,
    store as storeIncident,
} from '@/routes/emergency-incidents';

type CommunicationLog = {
    id: number;
    message: string;
    sent_by: string;
    sent_at: string;
};
type Incident = {
    id: number;
    meet_id: number;
    meet: string;
    venue: string | null;
    category: string;
    category_label: string;
    description: string;
    status: string;
    status_label: string;
    reported_by: string;
    reported_at: string;
    communication_logs: CommunicationLog[];
};

type Option = { id: number; label: string };
type ValueLabel = { value: string; label: string };

type Props = {
    incidents: Incident[];
    filters: { meet_id: number | null };
    meetOptions: Option[];
    venueOptions: Option[];
    categoryOptions: ValueLabel[];
};

const statusOptions = [
    { value: 'reported', label: 'Reported' },
    { value: 'responding', label: 'Responding' },
    { value: 'resolved', label: 'Resolved' },
];

function ReportIncidentDialog({
    open,
    onOpenChange,
    meetOptions,
    venueOptions,
    categoryOptions,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    meetOptions: Option[];
    venueOptions: Option[];
    categoryOptions: ValueLabel[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        meet_id: string;
        venue_id: string;
        category: string;
        description: string;
    }>({
        meet_id: meetOptions[0] ? String(meetOptions[0].id) : '',
        venue_id: '',
        category: '',
        description: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeIncident().url, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                if (!next) {
                    reset();
                }

                onOpenChange(next);
            }}
        >
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Report emergency incident</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="incident-venue">Venue (optional)</Label>
                        <Select
                            value={data.venue_id}
                            onValueChange={(v) => setData('venue_id', v)}
                        >
                            <SelectTrigger id="incident-venue">
                                <SelectValue placeholder="Select a venue" />
                            </SelectTrigger>
                            <SelectContent>
                                {venueOptions.map((v) => (
                                    <SelectItem key={v.id} value={String(v.id)}>
                                        {v.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="incident-category">Category</Label>
                        <Select
                            value={data.category}
                            onValueChange={(v) => setData('category', v)}
                        >
                            <SelectTrigger id="incident-category">
                                <SelectValue placeholder="Select a category" />
                            </SelectTrigger>
                            <SelectContent>
                                {categoryOptions.map((c) => (
                                    <SelectItem key={c.value} value={c.value}>
                                        {c.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.category} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="incident-description">
                            Description
                        </Label>
                        <Textarea
                            id="incident-description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                        />
                        <InputError message={errors.description} />
                    </div>
                    <DialogFooter>
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={processing}
                        >
                            Report incident
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function LogMessageDialog({
    incidentId,
    open,
    onOpenChange,
}: {
    incidentId: number;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        emergency_incident_id: string;
        message: string;
    }>({
        emergency_incident_id: String(incidentId),
        message: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeLog().url, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                if (!next) {
                    reset();
                }

                onOpenChange(next);
            }}
        >
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Log a message</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="log-message">Message</Label>
                        <Textarea
                            id="log-message"
                            value={data.message}
                            onChange={(e) => setData('message', e.target.value)}
                        />
                        <InputError message={errors.message} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Log message
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function IncidentCard({ incident }: { incident: Incident }) {
    const [logOpen, setLogOpen] = useState(false);

    return (
        <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-4">
                <div>
                    <CardTitle className="flex items-center gap-2">
                        {incident.category_label}
                        <Badge variant="outline">{incident.status_label}</Badge>
                    </CardTitle>
                    <p className="text-sm text-muted-foreground">
                        {incident.meet}
                        {incident.venue && ` — ${incident.venue}`} — reported by{' '}
                        {incident.reported_by} ({incident.reported_at})
                    </p>
                    <p className="mt-1 text-sm">{incident.description}</p>
                </div>
                <Select
                    value={incident.status}
                    onValueChange={(value) =>
                        router.patch(
                            updateIncidentStatus(incident.id).url,
                            { status: value },
                            { preserveScroll: true },
                        )
                    }
                >
                    <SelectTrigger
                        className="w-32"
                        aria-label="Incident status"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {statusOptions.map((s) => (
                            <SelectItem key={s.value} value={s.value}>
                                {s.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </CardHeader>
            <CardContent>
                {incident.communication_logs.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No messages logged yet.
                    </p>
                ) : (
                    <ul className="space-y-1 text-sm">
                        {incident.communication_logs.map((log) => (
                            <li key={log.id} className="rounded-lg border p-2">
                                <p>{log.message}</p>
                                <p className="text-xs text-muted-foreground">
                                    {log.sent_by} — {log.sent_at}
                                </p>
                            </li>
                        ))}
                    </ul>
                )}
                <Button
                    variant="outline"
                    size="sm"
                    className="mt-3"
                    onClick={() => setLogOpen(true)}
                >
                    <Plus aria-hidden="true" />
                    Log message
                </Button>
            </CardContent>

            <LogMessageDialog
                incidentId={incident.id}
                open={logOpen}
                onOpenChange={setLogOpen}
            />
        </Card>
    );
}

export default function DrrmIncidents({
    incidents,
    meetOptions,
    venueOptions,
    categoryOptions,
}: Props) {
    const [reportOpen, setReportOpen] = useState(false);

    return (
        <>
            <Head title="Emergency Incidents" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Emergency Incidents"
                    description="Live emergency-incident response and communication log."
                    actions={
                        <Button
                            variant="destructive"
                            onClick={() => setReportOpen(true)}
                        >
                            <Plus aria-hidden="true" />
                            Report incident
                        </Button>
                    }
                />

                {incidents.length === 0 ? (
                    <EmptyState
                        icon={Siren}
                        title="No incidents reported"
                        description="Report an emergency incident when one occurs."
                    />
                ) : (
                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        {incidents.map((incident) => (
                            <IncidentCard
                                key={incident.id}
                                incident={incident}
                            />
                        ))}
                    </div>
                )}
            </div>

            <ReportIncidentDialog
                open={reportOpen}
                onOpenChange={setReportOpen}
                meetOptions={meetOptions}
                venueOptions={venueOptions}
                categoryOptions={categoryOptions}
            />
        </>
    );
}

DrrmIncidents.layout = {
    breadcrumbs: [
        {
            title: 'Emergency Incidents',
            href: index(),
        },
    ],
};
