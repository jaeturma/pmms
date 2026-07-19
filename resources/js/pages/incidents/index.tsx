import { Head, router, useForm } from '@inertiajs/react';
import { Plus, TriangleAlert } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { PaginationControls } from '@/components/pagination-controls';
import type { Paginated } from '@/components/pagination-controls';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    destroy,
    index,
    reopen,
    resolve,
    store,
    update,
} from '@/routes/incidents';

type Incident = {
    id: number;
    meet_id: number;
    venue_id: number | null;
    meet: string;
    venue: string | null;
    description: string;
    severity: string;
    severity_label: string;
    medical_referral: boolean;
    status: string;
    status_label: string;
    reported_by: string | null;
    reported_at: string | null;
    resolved_at: string | null;
};

type Option = { id: number; label: string };

type ValueOption = { value: string; label: string };

type Props = {
    incidents: Paginated<Incident>;
    filters: { status: string | null; meet_id: number | null };
    severityOptions: ValueOption[];
    meetOptions: Option[];
    venueOptions: Option[];
};

const severityVariants: Record<
    string,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    minor: 'outline',
    moderate: 'default',
    serious: 'destructive',
};

function IncidentFormDialog({
    incident,
    severityOptions,
    meetOptions,
    venueOptions,
    open,
    onOpenChange,
}: {
    incident: Incident | null;
    severityOptions: ValueOption[];
    meetOptions: Option[];
    venueOptions: Option[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset, transform } =
        useForm({
            meet_id: incident ? String(incident.meet_id) : '',
            venue_id: incident?.venue_id ? String(incident.venue_id) : 'none',
            description: incident?.description ?? '',
            severity: incident?.severity ?? 'minor',
            medical_referral: incident?.medical_referral ?? false,
        });

    transform((current) => ({
        ...current,
        venue_id: current.venue_id === 'none' ? null : current.venue_id,
    }));

    const submit = (e: FormEvent) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        };

        if (incident) {
            put(update(incident.id).url, options);
        } else {
            post(store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {incident ? 'Edit incident' : 'Log incident'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="incident-meet">Meet</Label>
                            <Select
                                value={data.meet_id}
                                onValueChange={(value) =>
                                    setData('meet_id', value)
                                }
                            >
                                <SelectTrigger id="incident-meet">
                                    <SelectValue placeholder="Select a meet" />
                                </SelectTrigger>
                                <SelectContent>
                                    {meetOptions.map((option) => (
                                        <SelectItem
                                            key={option.id}
                                            value={String(option.id)}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.meet_id} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="incident-venue">
                                Venue (optional)
                            </Label>
                            <Select
                                value={data.venue_id}
                                onValueChange={(value) =>
                                    setData('venue_id', value)
                                }
                            >
                                <SelectTrigger id="incident-venue">
                                    <SelectValue placeholder="No venue" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        No venue
                                    </SelectItem>
                                    {venueOptions.map((option) => (
                                        <SelectItem
                                            key={option.id}
                                            value={String(option.id)}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.venue_id} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="incident-description">
                            Description
                        </Label>
                        <Input
                            id="incident-description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            placeholder="What happened — no medical details"
                        />
                        <InputError message={errors.description} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="incident-severity">Severity</Label>
                            <Select
                                value={data.severity}
                                onValueChange={(value) =>
                                    setData('severity', value)
                                }
                            >
                                <SelectTrigger id="incident-severity">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {severityOptions.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.severity} />
                        </div>
                        <label className="flex items-end gap-2 pb-2.5 text-sm">
                            <Checkbox
                                checked={data.medical_referral}
                                onCheckedChange={(checked) =>
                                    setData(
                                        'medical_referral',
                                        checked === true,
                                    )
                                }
                            />
                            Medical referral made
                        </label>
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {incident ? 'Save changes' : 'Log incident'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Incidents({
    incidents,
    filters,
    severityOptions,
    meetOptions,
    venueOptions,
}: Props) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Incident | null>(null);

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (incident: Incident) => {
        setEditing(incident);
        setFormOpen(true);
    };

    const applyFilters = (overrides: { status?: string; meet_id?: string }) => {
        const params: Record<string, string> = {};

        const status = overrides.status ?? filters.status ?? '';
        const meetId = overrides.meet_id ?? String(filters.meet_id ?? '');

        if (status && status !== 'all') {
            params.status = status;
        }

        if (meetId && meetId !== 'all') {
            params.meet_id = meetId;
        }

        router.get(index().url, params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const filterParams = {
        ...(filters.status ? { status: filters.status } : {}),
        ...(filters.meet_id ? { meet_id: String(filters.meet_id) } : {}),
    };

    return (
        <>
            <Head title="Incidents" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Incidents"
                    description="Meet-day incident log. Medical incidents record only that a referral happened."
                    actions={
                        <Button onClick={openCreate}>
                            <Plus />
                            Log incident
                        </Button>
                    }
                />

                <div className="flex flex-wrap gap-2">
                    <Select
                        value={filters.status ?? 'all'}
                        onValueChange={(value) =>
                            applyFilters({ status: value })
                        }
                    >
                        <SelectTrigger
                            className="w-44"
                            aria-label="Filter by status"
                        >
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            <SelectItem value="open">Open</SelectItem>
                            <SelectItem value="resolved">Resolved</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select
                        value={String(filters.meet_id ?? 'all')}
                        onValueChange={(value) =>
                            applyFilters({ meet_id: value })
                        }
                    >
                        <SelectTrigger
                            className="w-56"
                            aria-label="Filter by meet"
                        >
                            <SelectValue placeholder="All meets" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All meets</SelectItem>
                            {meetOptions.map((option) => (
                                <SelectItem
                                    key={option.id}
                                    value={String(option.id)}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {incidents.data.length === 0 ? (
                    <EmptyState
                        icon={TriangleAlert}
                        title="No incidents logged"
                        description="Meet-day incidents will appear here."
                        action={
                            <Button onClick={openCreate}>Log incident</Button>
                        }
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Description</TableHead>
                                    <TableHead>Meet / Venue</TableHead>
                                    <TableHead>Severity</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {incidents.data.map((incident) => (
                                    <TableRow key={incident.id}>
                                        <TableCell className="max-w-72">
                                            <p className="truncate font-medium">
                                                {incident.description}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {incident.reported_by ?? '—'} ·{' '}
                                                {incident.reported_at}
                                                {incident.medical_referral &&
                                                    ' · Medical referral'}
                                            </p>
                                        </TableCell>
                                        <TableCell className="max-w-48">
                                            <p className="truncate">
                                                {incident.meet}
                                            </p>
                                            <p className="truncate text-sm text-muted-foreground">
                                                {incident.venue ?? '—'}
                                            </p>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    severityVariants[
                                                        incident.severity
                                                    ] ?? 'outline'
                                                }
                                            >
                                                {incident.severity_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    incident.status === 'open'
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {incident.status_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        openEdit(incident)
                                                    }
                                                >
                                                    Edit
                                                </Button>
                                                <ConfirmDialog
                                                    trigger={
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                        >
                                                            {incident.status ===
                                                            'open'
                                                                ? 'Resolve'
                                                                : 'Reopen'}
                                                        </Button>
                                                    }
                                                    title={
                                                        incident.status ===
                                                        'open'
                                                            ? 'Resolve incident?'
                                                            : 'Reopen incident?'
                                                    }
                                                    description={
                                                        incident.status ===
                                                        'open'
                                                            ? 'Marks the incident as handled.'
                                                            : 'Returns the incident to the open list.'
                                                    }
                                                    confirmLabel={
                                                        incident.status ===
                                                        'open'
                                                            ? 'Resolve'
                                                            : 'Reopen'
                                                    }
                                                    onConfirm={() =>
                                                        router.patch(
                                                            incident.status ===
                                                                'open'
                                                                ? resolve(
                                                                      incident.id,
                                                                  ).url
                                                                : reopen(
                                                                      incident.id,
                                                                  ).url,
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                />
                                                <ConfirmDialog
                                                    trigger={
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                        >
                                                            Delete
                                                        </Button>
                                                    }
                                                    title="Delete incident?"
                                                    description="This permanently removes the log entry."
                                                    confirmLabel="Delete"
                                                    destructive
                                                    onConfirm={() =>
                                                        router.delete(
                                                            destroy(incident.id)
                                                                .url,
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                />
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <PaginationControls
                    page={incidents}
                    url={index().url}
                    label="incidents"
                    params={filterParams}
                />
            </div>

            <IncidentFormDialog
                key={editing?.id ?? 'create'}
                incident={editing}
                severityOptions={severityOptions}
                meetOptions={meetOptions}
                venueOptions={venueOptions}
                open={formOpen}
                onOpenChange={setFormOpen}
            />
        </>
    );
}

Incidents.layout = {
    breadcrumbs: [
        {
            title: 'Incidents',
            href: index(),
        },
    ],
};
