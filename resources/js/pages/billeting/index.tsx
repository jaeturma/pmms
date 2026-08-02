import { Head, router, useForm } from '@inertiajs/react';
import { BedDouble, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { index } from '@/routes/billeting';
import {
    destroy as destroyAssignment,
    status as updateAssignmentStatus,
    store as storeAssignment,
} from '@/routes/billeting-assignments';
import {
    destroy as destroyVenue,
    store as storeVenue,
    update as updateVenue,
} from '@/routes/billeting-venues';

type Assignment = {
    id: number;
    delegation_id: number;
    delegation: string;
    room_detail: string | null;
    contact_name: string | null;
    status: string;
    status_label: string;
};

type Venue = {
    id: number;
    meet_id: number;
    meet: string;
    name: string;
    address: string | null;
    capacity: number | null;
    contact_name: string | null;
    contact_phone: string | null;
    notes: string | null;
    assignments: Assignment[];
};

type Option = { id: number; meet_id?: number; label: string };

type Props = {
    venues: Venue[];
    filters: { meet_id: number | null };
    meetOptions: Option[];
    venueOptions: Option[];
    delegationOptions: Option[];
    canManage: boolean;
};

const assignmentStatusOptions = [
    { value: 'assigned', label: 'Assigned' },
    { value: 'checked_in', label: 'Checked In' },
    { value: 'checked_out', label: 'Checked Out' },
];

function CreateVenueDialog({
    open,
    onOpenChange,
    meetOptions,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    meetOptions: Option[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        meet_id: string;
        name: string;
        address: string;
        capacity: string;
        contact_name: string;
        contact_phone: string;
        notes: string;
    }>({
        meet_id: '',
        name: '',
        address: '',
        capacity: '',
        contact_name: '',
        contact_phone: '',
        notes: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeVenue().url, {
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
                    <DialogTitle>Add billeting venue</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="venue-meet">Meet</Label>
                        <Select
                            value={data.meet_id}
                            onValueChange={(value) => setData('meet_id', value)}
                        >
                            <SelectTrigger id="venue-meet">
                                <SelectValue placeholder="Select a meet" />
                            </SelectTrigger>
                            <SelectContent>
                                {meetOptions.map((meet) => (
                                    <SelectItem
                                        key={meet.id}
                                        value={String(meet.id)}
                                    >
                                        {meet.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.meet_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="venue-name">Name</Label>
                        <Input
                            id="venue-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="venue-address">
                            Address (optional)
                        </Label>
                        <Textarea
                            id="venue-address"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="venue-capacity">
                            Capacity (optional)
                        </Label>
                        <Input
                            id="venue-capacity"
                            type="number"
                            min={1}
                            value={data.capacity}
                            onChange={(e) =>
                                setData('capacity', e.target.value)
                            }
                        />
                        <InputError message={errors.capacity} />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="venue-contact-name">
                                Contact name (optional)
                            </Label>
                            <Input
                                id="venue-contact-name"
                                value={data.contact_name}
                                onChange={(e) =>
                                    setData('contact_name', e.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="venue-contact-phone">
                                Contact phone (optional)
                            </Label>
                            <Input
                                id="venue-contact-phone"
                                value={data.contact_phone}
                                onChange={(e) =>
                                    setData('contact_phone', e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="venue-notes">Notes (optional)</Label>
                        <Textarea
                            id="venue-notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                        />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Add venue
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function AssignDelegationDialog({
    venueId,
    open,
    onOpenChange,
    delegationOptions,
}: {
    venueId: number;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    delegationOptions: Option[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        billeting_venue_id: string;
        delegation_id: string;
        room_detail: string;
        contact_name: string;
    }>({
        billeting_venue_id: String(venueId),
        delegation_id: '',
        room_detail: '',
        contact_name: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeAssignment().url, {
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
                    <DialogTitle>Assign delegation</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="assign-delegation">Delegation</Label>
                        <Select
                            value={data.delegation_id}
                            onValueChange={(value) =>
                                setData('delegation_id', value)
                            }
                        >
                            <SelectTrigger id="assign-delegation">
                                <SelectValue placeholder="Select a delegation" />
                            </SelectTrigger>
                            <SelectContent>
                                {delegationOptions.map((d) => (
                                    <SelectItem key={d.id} value={String(d.id)}>
                                        {d.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.delegation_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="assign-room">
                            Room detail (optional)
                        </Label>
                        <Input
                            id="assign-room"
                            value={data.room_detail}
                            onChange={(e) =>
                                setData('room_detail', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="assign-contact">
                            Contact name (optional)
                        </Label>
                        <Input
                            id="assign-contact"
                            value={data.contact_name}
                            onChange={(e) =>
                                setData('contact_name', e.target.value)
                            }
                        />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Assign
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function VenueCard({
    venue,
    canManage,
    delegationOptions,
}: {
    venue: Venue;
    canManage: boolean;
    delegationOptions: Option[];
}) {
    const [editOpen, setEditOpen] = useState(false);
    const [assignOpen, setAssignOpen] = useState(false);

    const { data, setData, put, processing } = useForm<{
        name: string;
        address: string;
        capacity: string;
        contact_name: string;
        contact_phone: string;
        notes: string;
    }>({
        name: venue.name,
        address: venue.address ?? '',
        capacity: venue.capacity ? String(venue.capacity) : '',
        contact_name: venue.contact_name ?? '',
        contact_phone: venue.contact_phone ?? '',
        notes: venue.notes ?? '',
    });

    const submitEdit = (e: FormEvent) => {
        e.preventDefault();
        put(updateVenue(venue.id).url, {
            preserveScroll: true,
            onSuccess: () => setEditOpen(false),
        });
    };

    const venueDelegationOptions = delegationOptions.filter(
        (d) => d.meet_id === venue.meet_id,
    );

    return (
        <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-4">
                <div>
                    <CardTitle>{venue.name}</CardTitle>
                    <p className="text-sm text-muted-foreground">
                        {venue.meet}
                    </p>
                    {venue.address && (
                        <p className="mt-1 text-sm text-muted-foreground">
                            {venue.address}
                            {venue.capacity && ` — capacity ${venue.capacity}`}
                        </p>
                    )}
                    {(venue.contact_name || venue.contact_phone) && (
                        <p className="text-sm text-muted-foreground">
                            {[venue.contact_name, venue.contact_phone]
                                .filter(Boolean)
                                .join(' — ')}
                        </p>
                    )}
                </div>
                {canManage && (
                    <div className="flex shrink-0 gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setEditOpen(true)}
                        >
                            Edit
                        </Button>
                        <ConfirmDialog
                            trigger={
                                <Button variant="ghost" size="icon">
                                    <Trash2 className="size-4" />
                                </Button>
                            }
                            title="Remove venue?"
                            description={`${venue.name} and its ${venue.assignments.length} assignment(s) will be removed.`}
                            confirmLabel="Remove"
                            destructive
                            onConfirm={() =>
                                router.delete(destroyVenue(venue.id).url, {
                                    preserveScroll: true,
                                })
                            }
                        />
                    </div>
                )}
            </CardHeader>
            <CardContent>
                {venue.assignments.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No delegations assigned yet.
                    </p>
                ) : (
                    <ul className="space-y-2">
                        {venue.assignments.map((assignment) => (
                            <li
                                key={assignment.id}
                                className="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-2 text-sm"
                            >
                                <div>
                                    <span className="font-medium">
                                        {assignment.delegation}
                                    </span>
                                    {assignment.room_detail && (
                                        <span className="text-muted-foreground">
                                            {' '}
                                            — Room {assignment.room_detail}
                                        </span>
                                    )}
                                    {assignment.contact_name && (
                                        <span className="text-muted-foreground">
                                            {' '}
                                            ({assignment.contact_name})
                                        </span>
                                    )}
                                </div>
                                <div className="flex items-center gap-2">
                                    {canManage ? (
                                        <Select
                                            value={assignment.status}
                                            onValueChange={(value) =>
                                                router.patch(
                                                    updateAssignmentStatus(
                                                        assignment.id,
                                                    ).url,
                                                    { status: value },
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <SelectTrigger
                                                className="w-32"
                                                aria-label={`Status for ${assignment.delegation}`}
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {assignmentStatusOptions.map(
                                                    (s) => (
                                                        <SelectItem
                                                            key={s.value}
                                                            value={s.value}
                                                        >
                                                            {s.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    ) : (
                                        <Badge variant="outline">
                                            {assignment.status_label}
                                        </Badge>
                                    )}
                                    {canManage && (
                                        <ConfirmDialog
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={`Remove ${assignment.delegation}`}
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            }
                                            title="Remove assignment?"
                                            description={`${assignment.delegation} will be unassigned from ${venue.name}.`}
                                            confirmLabel="Remove"
                                            destructive
                                            onConfirm={() =>
                                                router.delete(
                                                    destroyAssignment(
                                                        assignment.id,
                                                    ).url,
                                                    { preserveScroll: true },
                                                )
                                            }
                                        />
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
                {canManage && (
                    <Button
                        variant="outline"
                        size="sm"
                        className="mt-3"
                        onClick={() => setAssignOpen(true)}
                    >
                        <Plus aria-hidden="true" />
                        Assign delegation
                    </Button>
                )}
            </CardContent>

            {canManage && (
                <>
                    <Dialog open={editOpen} onOpenChange={setEditOpen}>
                        <DialogContent className="sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle>Edit {venue.name}</DialogTitle>
                            </DialogHeader>
                            <form onSubmit={submitEdit} className="space-y-4">
                                <div className="space-y-2">
                                    <Label
                                        htmlFor={`edit-venue-name-${venue.id}`}
                                    >
                                        Name
                                    </Label>
                                    <Input
                                        id={`edit-venue-name-${venue.id}`}
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label
                                        htmlFor={`edit-venue-address-${venue.id}`}
                                    >
                                        Address (optional)
                                    </Label>
                                    <Textarea
                                        id={`edit-venue-address-${venue.id}`}
                                        value={data.address}
                                        onChange={(e) =>
                                            setData('address', e.target.value)
                                        }
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label
                                        htmlFor={`edit-venue-capacity-${venue.id}`}
                                    >
                                        Capacity (optional)
                                    </Label>
                                    <Input
                                        id={`edit-venue-capacity-${venue.id}`}
                                        type="number"
                                        min={1}
                                        value={data.capacity}
                                        onChange={(e) =>
                                            setData('capacity', e.target.value)
                                        }
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label
                                            htmlFor={`edit-venue-contact-name-${venue.id}`}
                                        >
                                            Contact name (optional)
                                        </Label>
                                        <Input
                                            id={`edit-venue-contact-name-${venue.id}`}
                                            value={data.contact_name}
                                            onChange={(e) =>
                                                setData(
                                                    'contact_name',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label
                                            htmlFor={`edit-venue-contact-phone-${venue.id}`}
                                        >
                                            Contact phone (optional)
                                        </Label>
                                        <Input
                                            id={`edit-venue-contact-phone-${venue.id}`}
                                            value={data.contact_phone}
                                            onChange={(e) =>
                                                setData(
                                                    'contact_phone',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label
                                        htmlFor={`edit-venue-notes-${venue.id}`}
                                    >
                                        Notes (optional)
                                    </Label>
                                    <Textarea
                                        id={`edit-venue-notes-${venue.id}`}
                                        value={data.notes}
                                        onChange={(e) =>
                                            setData('notes', e.target.value)
                                        }
                                    />
                                </div>
                                <DialogFooter>
                                    <Button type="submit" disabled={processing}>
                                        Save changes
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                    <AssignDelegationDialog
                        venueId={venue.id}
                        open={assignOpen}
                        onOpenChange={setAssignOpen}
                        delegationOptions={venueDelegationOptions}
                    />
                </>
            )}
        </Card>
    );
}

export default function Billeting({
    venues,
    filters,
    meetOptions,
    delegationOptions,
    canManage,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);

    const applyMeetFilter = (value: string) => {
        router.get(index().url, value === 'all' ? {} : { meet_id: value }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Billeting" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Billeting"
                    description="Billeting venues and delegation assignments per meet."
                    actions={
                        canManage && (
                            <Button onClick={() => setCreateOpen(true)}>
                                <Plus aria-hidden="true" />
                                Add venue
                            </Button>
                        )
                    }
                />

                <Select
                    value={filters.meet_id ? String(filters.meet_id) : 'all'}
                    onValueChange={applyMeetFilter}
                >
                    <SelectTrigger className="w-64" aria-label="Filter by meet">
                        <SelectValue placeholder="All meets" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All meets</SelectItem>
                        {meetOptions.map((meet) => (
                            <SelectItem key={meet.id} value={String(meet.id)}>
                                {meet.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                {venues.length === 0 ? (
                    <EmptyState
                        icon={BedDouble}
                        title="No billeting venues yet"
                        description="Add a billeting venue and assign delegations to it."
                    />
                ) : (
                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        {venues.map((venue) => (
                            <VenueCard
                                key={venue.id}
                                venue={venue}
                                canManage={canManage}
                                delegationOptions={delegationOptions}
                            />
                        ))}
                    </div>
                )}
            </div>

            {canManage && (
                <CreateVenueDialog
                    open={createOpen}
                    onOpenChange={setCreateOpen}
                    meetOptions={meetOptions}
                />
            )}
        </>
    );
}

Billeting.layout = {
    breadcrumbs: [
        {
            title: 'Billeting',
            href: index(),
        },
    ],
};
