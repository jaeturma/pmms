import { Head, router, useForm } from '@inertiajs/react';
import { Bus, Plus, Trash2 } from 'lucide-react';
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
import { index } from '@/routes/transport';
import {
    destroy as destroyRequest,
    store as storeRequest,
} from '@/routes/transport-requests';
import {
    destroy as destroyTrip,
    status as updateTripStatus,
    store as storeTrip,
} from '@/routes/transport-trips';
import {
    destroy as destroyVehicle,
    store as storeVehicle,
} from '@/routes/vehicles';

type Trip = {
    id: number;
    delegation: string | null;
    pickup_location: string;
    dropoff_location: string;
    status: string;
    status_label: string;
    scheduled_at: string;
    fulfills_request_id: number | null;
};

type Vehicle = {
    id: number;
    meet_id: number;
    meet: string;
    plate_number: string;
    type: string | null;
    capacity: number | null;
    driver_name: string | null;
    driver_phone: string | null;
    notes: string | null;
    trips: Trip[];
};

type TransportRequestRow = {
    id: number;
    meet_id: number;
    meet: string;
    delegation_id: number;
    delegation: string;
    pickup_location: string;
    dropoff_location: string;
    requested_at: string;
    passenger_count: number | null;
    notes: string | null;
};

type Option = { id: number; meet_id?: number; label: string };

type Props = {
    vehicles: Vehicle[];
    requests: TransportRequestRow[];
    filters: { meet_id: number | null };
    meetOptions: Option[];
    delegationOptions: Option[];
    canManage: boolean;
};

const tripStatusOptions = [
    { value: 'dispatched', label: 'Dispatched' },
    { value: 'boarding', label: 'Boarding' },
    { value: 'en_route', label: 'En Route' },
    { value: 'arrived', label: 'Arrived' },
    { value: 'delayed', label: 'Delayed' },
    { value: 'cancelled', label: 'Cancelled' },
];

function CreateVehicleDialog({
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
        plate_number: string;
        type: string;
        capacity: string;
        driver_name: string;
        driver_phone: string;
        notes: string;
    }>({
        meet_id: '',
        plate_number: '',
        type: '',
        capacity: '',
        driver_name: '',
        driver_phone: '',
        notes: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeVehicle().url, {
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
                    <DialogTitle>Add vehicle</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="vehicle-meet">Meet</Label>
                        <Select
                            value={data.meet_id}
                            onValueChange={(value) => setData('meet_id', value)}
                        >
                            <SelectTrigger id="vehicle-meet">
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
                        <Label htmlFor="vehicle-plate">Plate number</Label>
                        <Input
                            id="vehicle-plate"
                            value={data.plate_number}
                            onChange={(e) =>
                                setData('plate_number', e.target.value)
                            }
                        />
                        <InputError message={errors.plate_number} />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="vehicle-type">
                                Type (optional)
                            </Label>
                            <Input
                                id="vehicle-type"
                                placeholder="Bus, Van, Car"
                                value={data.type}
                                onChange={(e) =>
                                    setData('type', e.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="vehicle-capacity">
                                Capacity (optional)
                            </Label>
                            <Input
                                id="vehicle-capacity"
                                type="number"
                                min={1}
                                value={data.capacity}
                                onChange={(e) =>
                                    setData('capacity', e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="vehicle-driver-name">
                                Driver name (optional)
                            </Label>
                            <Input
                                id="vehicle-driver-name"
                                value={data.driver_name}
                                onChange={(e) =>
                                    setData('driver_name', e.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="vehicle-driver-phone">
                                Driver phone (optional)
                            </Label>
                            <Input
                                id="vehicle-driver-phone"
                                value={data.driver_phone}
                                onChange={(e) =>
                                    setData('driver_phone', e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="vehicle-notes">Notes (optional)</Label>
                        <Textarea
                            id="vehicle-notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                        />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Add vehicle
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DispatchTripDialog({
    vehicleId,
    open,
    onOpenChange,
    delegationOptions,
    pendingRequests,
}: {
    vehicleId: number;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    delegationOptions: Option[];
    pendingRequests: TransportRequestRow[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        vehicle_id: string;
        delegation_id: string;
        transport_request_id: string;
        pickup_location: string;
        dropoff_location: string;
        scheduled_at: string;
    }>({
        vehicle_id: String(vehicleId),
        delegation_id: '',
        transport_request_id: '',
        pickup_location: '',
        dropoff_location: '',
        scheduled_at: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeTrip().url, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    const applyRequest = (requestId: string) => {
        setData('transport_request_id', requestId);
        const match = pendingRequests.find((r) => String(r.id) === requestId);

        if (match) {
            setData((current) => ({
                ...current,
                transport_request_id: requestId,
                pickup_location: match.pickup_location,
                dropoff_location: match.dropoff_location,
            }));
        }
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
                    <DialogTitle>Dispatch trip</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    {pendingRequests.length > 0 && (
                        <div className="space-y-2">
                            <Label htmlFor="trip-request">
                                Fulfill a pending request (optional)
                            </Label>
                            <Select
                                value={data.transport_request_id}
                                onValueChange={applyRequest}
                            >
                                <SelectTrigger id="trip-request">
                                    <SelectValue placeholder="None" />
                                </SelectTrigger>
                                <SelectContent>
                                    {pendingRequests.map((r) => (
                                        <SelectItem
                                            key={r.id}
                                            value={String(r.id)}
                                        >
                                            {r.delegation}: {r.pickup_location}{' '}
                                            → {r.dropoff_location}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.transport_request_id} />
                        </div>
                    )}
                    <div className="space-y-2">
                        <Label htmlFor="trip-delegation">
                            Delegation (optional)
                        </Label>
                        <Select
                            value={data.delegation_id}
                            onValueChange={(value) =>
                                setData('delegation_id', value)
                            }
                        >
                            <SelectTrigger id="trip-delegation">
                                <SelectValue placeholder="None (e.g. officials shuttle)" />
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
                        <Label htmlFor="trip-pickup">Pickup location</Label>
                        <Input
                            id="trip-pickup"
                            value={data.pickup_location}
                            onChange={(e) =>
                                setData('pickup_location', e.target.value)
                            }
                        />
                        <InputError message={errors.pickup_location} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="trip-dropoff">Dropoff location</Label>
                        <Input
                            id="trip-dropoff"
                            value={data.dropoff_location}
                            onChange={(e) =>
                                setData('dropoff_location', e.target.value)
                            }
                        />
                        <InputError message={errors.dropoff_location} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="trip-scheduled">Scheduled at</Label>
                        <Input
                            id="trip-scheduled"
                            type="datetime-local"
                            value={data.scheduled_at}
                            onChange={(e) =>
                                setData('scheduled_at', e.target.value)
                            }
                        />
                        <InputError message={errors.scheduled_at} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Dispatch
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function CreateRequestDialog({
    open,
    onOpenChange,
    delegationOptions,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    delegationOptions: Option[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        delegation_id: string;
        pickup_location: string;
        dropoff_location: string;
        requested_at: string;
        passenger_count: string;
        notes: string;
    }>({
        delegation_id: '',
        pickup_location: '',
        dropoff_location: '',
        requested_at: '',
        passenger_count: '',
        notes: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeRequest().url, {
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
                    <DialogTitle>Request transport</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="request-delegation">Delegation</Label>
                        <Select
                            value={data.delegation_id}
                            onValueChange={(value) =>
                                setData('delegation_id', value)
                            }
                        >
                            <SelectTrigger id="request-delegation">
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
                        <Label htmlFor="request-pickup">Pickup location</Label>
                        <Input
                            id="request-pickup"
                            value={data.pickup_location}
                            onChange={(e) =>
                                setData('pickup_location', e.target.value)
                            }
                        />
                        <InputError message={errors.pickup_location} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="request-dropoff">
                            Dropoff location
                        </Label>
                        <Input
                            id="request-dropoff"
                            value={data.dropoff_location}
                            onChange={(e) =>
                                setData('dropoff_location', e.target.value)
                            }
                        />
                        <InputError message={errors.dropoff_location} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="request-when">Needed at</Label>
                        <Input
                            id="request-when"
                            type="datetime-local"
                            value={data.requested_at}
                            onChange={(e) =>
                                setData('requested_at', e.target.value)
                            }
                        />
                        <InputError message={errors.requested_at} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="request-passengers">
                            Passenger count (optional)
                        </Label>
                        <Input
                            id="request-passengers"
                            type="number"
                            min={1}
                            value={data.passenger_count}
                            onChange={(e) =>
                                setData('passenger_count', e.target.value)
                            }
                        />
                        <InputError message={errors.passenger_count} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="request-notes">Notes (optional)</Label>
                        <Textarea
                            id="request-notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                        />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Request
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function VehicleCard({
    vehicle,
    canManage,
    delegationOptions,
    pendingRequests,
}: {
    vehicle: Vehicle;
    canManage: boolean;
    delegationOptions: Option[];
    pendingRequests: TransportRequestRow[];
}) {
    const [dispatchOpen, setDispatchOpen] = useState(false);

    const vehicleDelegationOptions = delegationOptions.filter(
        (d) => d.meet_id === vehicle.meet_id,
    );
    const vehiclePendingRequests = pendingRequests.filter(
        (r) => r.meet_id === vehicle.meet_id,
    );

    return (
        <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-4">
                <div>
                    <CardTitle>{vehicle.plate_number}</CardTitle>
                    <p className="text-sm text-muted-foreground">
                        {vehicle.type ?? 'Vehicle'} — {vehicle.meet}
                        {vehicle.capacity && ` — capacity ${vehicle.capacity}`}
                    </p>
                    {(vehicle.driver_name || vehicle.driver_phone) && (
                        <p className="text-sm text-muted-foreground">
                            Driver:{' '}
                            {[vehicle.driver_name, vehicle.driver_phone]
                                .filter(Boolean)
                                .join(' — ')}
                        </p>
                    )}
                </div>
                {canManage && (
                    <ConfirmDialog
                        trigger={
                            <Button variant="ghost" size="icon">
                                <Trash2 className="size-4" />
                            </Button>
                        }
                        title="Remove vehicle?"
                        description={`${vehicle.plate_number} and its ${vehicle.trips.length} trip(s) will be removed.`}
                        confirmLabel="Remove"
                        destructive
                        onConfirm={() =>
                            router.delete(destroyVehicle(vehicle.id).url, {
                                preserveScroll: true,
                            })
                        }
                    />
                )}
            </CardHeader>
            <CardContent>
                {vehicle.trips.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No trips yet.
                    </p>
                ) : (
                    <ul className="space-y-2">
                        {vehicle.trips.map((trip) => (
                            <li
                                key={trip.id}
                                className="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-2 text-sm"
                            >
                                <div>
                                    <span className="font-medium">
                                        {trip.pickup_location} →{' '}
                                        {trip.dropoff_location}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {' '}
                                        — {trip.scheduled_at}
                                        {trip.delegation &&
                                            ` (${trip.delegation})`}
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    {canManage ? (
                                        <Select
                                            value={trip.status}
                                            onValueChange={(value) =>
                                                router.patch(
                                                    updateTripStatus(trip.id)
                                                        .url,
                                                    { status: value },
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <SelectTrigger
                                                className="w-32"
                                                aria-label={`Status for trip ${trip.id}`}
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {tripStatusOptions.map((s) => (
                                                    <SelectItem
                                                        key={s.value}
                                                        value={s.value}
                                                    >
                                                        {s.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    ) : (
                                        <Badge variant="outline">
                                            {trip.status_label}
                                        </Badge>
                                    )}
                                    {canManage && (
                                        <ConfirmDialog
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label="Remove trip"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            }
                                            title="Remove trip?"
                                            description="This trip will be removed."
                                            confirmLabel="Remove"
                                            destructive
                                            onConfirm={() =>
                                                router.delete(
                                                    destroyTrip(trip.id).url,
                                                    {
                                                        preserveScroll: true,
                                                    },
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
                        onClick={() => setDispatchOpen(true)}
                    >
                        <Plus aria-hidden="true" />
                        Dispatch trip
                    </Button>
                )}
            </CardContent>

            {canManage && (
                <DispatchTripDialog
                    vehicleId={vehicle.id}
                    open={dispatchOpen}
                    onOpenChange={setDispatchOpen}
                    delegationOptions={vehicleDelegationOptions}
                    pendingRequests={vehiclePendingRequests}
                />
            )}
        </Card>
    );
}

export default function Transport({
    vehicles,
    requests,
    filters,
    meetOptions,
    delegationOptions,
    canManage,
}: Props) {
    const [createVehicleOpen, setCreateVehicleOpen] = useState(false);
    const [createRequestOpen, setCreateRequestOpen] = useState(false);

    const applyMeetFilter = (value: string) => {
        router.get(index().url, value === 'all' ? {} : { meet_id: value }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Transport" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Transport"
                    description="Vehicle roster, trip dispatch, and transport requests per meet."
                    actions={
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                onClick={() => setCreateRequestOpen(true)}
                            >
                                <Plus aria-hidden="true" />
                                Request transport
                            </Button>
                            {canManage && (
                                <Button
                                    onClick={() => setCreateVehicleOpen(true)}
                                >
                                    <Plus aria-hidden="true" />
                                    Add vehicle
                                </Button>
                            )}
                        </div>
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

                {requests.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Pending requests</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul className="space-y-2">
                                {requests.map((r) => (
                                    <li
                                        key={r.id}
                                        className="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-2 text-sm"
                                    >
                                        <div>
                                            <span className="font-medium">
                                                {r.delegation}
                                            </span>{' '}
                                            <span className="text-muted-foreground">
                                                {r.pickup_location} →{' '}
                                                {r.dropoff_location} —{' '}
                                                {r.requested_at}
                                                {r.passenger_count &&
                                                    ` (${r.passenger_count} passengers)`}
                                            </span>
                                        </div>
                                        {canManage && (
                                            <ConfirmDialog
                                                trigger={
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label="Remove request"
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </Button>
                                                }
                                                title="Remove request?"
                                                description={`${r.delegation}'s request will be removed.`}
                                                confirmLabel="Remove"
                                                destructive
                                                onConfirm={() =>
                                                    router.delete(
                                                        destroyRequest(r.id)
                                                            .url,
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            />
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                )}

                {vehicles.length === 0 ? (
                    <EmptyState
                        icon={Bus}
                        title="No vehicles yet"
                        description="Add a vehicle to the Transport Team's roster."
                    />
                ) : (
                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        {vehicles.map((vehicle) => (
                            <VehicleCard
                                key={vehicle.id}
                                vehicle={vehicle}
                                canManage={canManage}
                                delegationOptions={delegationOptions}
                                pendingRequests={requests}
                            />
                        ))}
                    </div>
                )}
            </div>

            {canManage && (
                <CreateVehicleDialog
                    open={createVehicleOpen}
                    onOpenChange={setCreateVehicleOpen}
                    meetOptions={meetOptions}
                />
            )}
            <CreateRequestDialog
                open={createRequestOpen}
                onOpenChange={setCreateRequestOpen}
                delegationOptions={delegationOptions}
            />
        </>
    );
}

Transport.layout = {
    breadcrumbs: [
        {
            title: 'Transport',
            href: index(),
        },
    ],
};
