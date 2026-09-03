import { Head, router, useForm } from '@inertiajs/react';
import { ExternalLink, MapPin, Phone, Plus, Users } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { PaginationControls } from '@/components/pagination-controls';
import type { Paginated } from '@/components/pagination-controls';
import { SearchBar } from '@/components/search-bar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { Textarea } from '@/components/ui/textarea';
import {
    archive,
    destroy,
    index,
    restore,
    store,
    update,
} from '@/routes/venues';

type Venue = {
    id: number;
    name: string;
    address: string | null;
    short_name: string | null;
    municipality_id: number | null;
    municipality: string | null;
    latitude: string | number | null;
    longitude: string | number | null;
    gps_location: string | null;
    public_notes: string | null;
    internal_notes: string | null;
    readiness_status: string;
    sports: string[];
    competition_areas: string[];
    game_coordinators: VenueCoordinator[];
    notes: string | null;
    active: boolean;
};

type VenueCoordinator = {
    id: number;
    name: string;
    contact_number: string | null;
    sport: string | null;
    is_lead: boolean;
};

type Props = {
    venues: Paginated<Venue>;
    filters: { search: string };
    canManage: boolean;
    canArchive: boolean;
    municipalityOptions: Option[];
    sportOptions: Array<{ id: number; name: string }>;
};
type Option = { id: number; label: string };

function VenueDetailsDialog({
    venue,
    open,
    onOpenChange,
}: {
    venue: Venue | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    if (!venue) {
        return null;
    }

    const mapsUrl =
        venue.latitude && venue.longitude
            ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${venue.latitude},${venue.longitude}`)}`
            : null;
    const mapsEmbedUrl =
        venue.latitude && venue.longitude
            ? `https://maps.google.com/maps?q=${encodeURIComponent(`${venue.latitude},${venue.longitude}`)}&z=15&output=embed`
            : null;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[92vh] w-[calc(100vw-2rem)] overflow-y-auto sm:max-w-5xl">
                <DialogHeader>
                    <DialogTitle>{venue.name}</DialogTitle>
                </DialogHeader>

                <div className="grid gap-5 sm:grid-cols-2">
                    <div className="space-y-4">
                        <div>
                            <p className="text-sm font-medium">Address</p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {venue.address ?? 'No address provided'}
                            </p>
                            {venue.municipality && (
                                <p className="text-sm text-muted-foreground">
                                    {venue.municipality}
                                </p>
                            )}
                        </div>

                        <div>
                            <p className="text-sm font-medium">Readiness</p>
                            <Badge className="mt-1" variant="outline">
                                {venue.readiness_status.replaceAll('_', ' ')}
                            </Badge>
                        </div>

                        <div>
                            <p className="text-sm font-medium">
                                Competition areas
                            </p>
                            {venue.competition_areas.length > 0 ? (
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {venue.competition_areas.map((area) => (
                                        <Badge key={area} variant="secondary">
                                            {area}
                                        </Badge>
                                    ))}
                                </div>
                            ) : (
                                <p className="mt-1 text-sm text-muted-foreground">
                                    No courts, tables, boards, or other areas
                                    configured.
                                </p>
                            )}
                        </div>

                        {mapsUrl ? (
                            <Button asChild variant="outline">
                                <a
                                    href={mapsUrl}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <MapPin /> Open in Google Maps
                                    <ExternalLink />
                                </a>
                            </Button>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Map unavailable—coordinates have not been set.
                            </p>
                        )}
                    </div>

                    <div>
                        <div className="mb-3 flex items-center gap-2">
                            <Users className="size-4" />
                            <p className="text-sm font-medium">
                                Game coordinators
                            </p>
                        </div>
                        {venue.game_coordinators.length > 0 ? (
                            <div className="space-y-3">
                                {venue.game_coordinators.map((coordinator) => (
                                    <div
                                        key={coordinator.id}
                                        className="rounded-lg border p-3"
                                    >
                                        <div className="flex items-start justify-between gap-2">
                                            <div>
                                                <p className="font-medium">
                                                    {coordinator.name}
                                                </p>
                                                {coordinator.sport && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {coordinator.sport}
                                                    </p>
                                                )}
                                            </div>
                                            {coordinator.is_lead && (
                                                <Badge variant="secondary">
                                                    Lead
                                                </Badge>
                                            )}
                                        </div>
                                        <div className="mt-2 flex items-center gap-2 text-sm text-muted-foreground">
                                            <Phone className="size-4" />
                                            {coordinator.contact_number ? (
                                                <a
                                                    href={`tel:${coordinator.contact_number}`}
                                                    className="hover:text-foreground hover:underline"
                                                >
                                                    {coordinator.contact_number}
                                                </a>
                                            ) : (
                                                <span>No contact number</span>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                No coordinator assigned.
                            </p>
                        )}
                    </div>

                    {mapsEmbedUrl && (
                        <div className="overflow-hidden rounded-xl border sm:col-span-2">
                            <iframe
                                title={`${venue.name} location on Google Maps`}
                                src={mapsEmbedUrl}
                                className="h-72 w-full"
                                loading="lazy"
                                referrerPolicy="no-referrer-when-downgrade"
                                allowFullScreen
                            />
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}

function VenueFormDialog({
    venue,
    municipalityOptions,
    sportOptions,
    canArchive,
    open,
    onOpenChange,
}: {
    venue: Venue | null;
    municipalityOptions: Option[];
    sportOptions: Array<{ id: number; name: string }>;
    canArchive: boolean;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        sport_id:
            !venue && sportOptions.length === 1
                ? String(sportOptions[0].id)
                : '',
        name: venue?.name ?? '',
        short_name: venue?.short_name ?? '',
        address: venue?.address ?? '',
        municipality_id: venue?.municipality_id
            ? String(venue.municipality_id)
            : '',
        gps_location: venue?.gps_location ?? '',
        public_notes: venue?.public_notes ?? '',
        internal_notes: venue?.internal_notes ?? '',
        readiness_status: venue?.readiness_status ?? 'planned',
        notes: venue?.notes ?? '',
        competition_area_type: '',
        competition_area_label: '',
        competition_area_count: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        };

        if (venue) {
            put(update(venue.id).url, options);
        } else {
            post(store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[92vh] w-[calc(100vw-2rem)] overflow-y-auto sm:max-w-5xl">
                <DialogHeader>
                    <DialogTitle>
                        {venue ? 'Edit venue' : 'Add venue'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-4 sm:grid-cols-2">
                    {!venue && (
                        <div className="space-y-2 sm:col-span-2">
                            <Label htmlFor="venue-sport">Sport</Label>
                            <Select
                                value={data.sport_id || 'none'}
                                onValueChange={(value) =>
                                    setData(
                                        'sport_id',
                                        value === 'none' ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger id="venue-sport">
                                    <SelectValue placeholder="Select a sport" />
                                </SelectTrigger>
                                <SelectContent>
                                    {canArchive && (
                                        <SelectItem value="none">
                                            No sport association
                                        </SelectItem>
                                    )}
                                    {sportOptions.map((sport) => (
                                        <SelectItem
                                            key={sport.id}
                                            value={String(sport.id)}
                                        >
                                            {sport.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.sport_id} />
                        </div>
                    )}
                    <div className="space-y-2">
                        <Label htmlFor="venue-name">Name</Label>
                        <Input
                            id="venue-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            autoFocus
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="venue-short-name">Short name</Label>
                        <Input
                            id="venue-short-name"
                            value={data.short_name}
                            onChange={(e) =>
                                setData('short_name', e.target.value)
                            }
                        />
                        <InputError message={errors.short_name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="venue-address">Address</Label>
                        <Input
                            id="venue-address"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                        />
                        <InputError message={errors.address} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="venue-municipality">Municipality</Label>
                        <Select
                            value={data.municipality_id || 'none'}
                            onValueChange={(value) =>
                                setData(
                                    'municipality_id',
                                    value === 'none' ? '' : value,
                                )
                            }
                        >
                            <SelectTrigger id="venue-municipality">
                                <SelectValue placeholder="Unassigned" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Unassigned</SelectItem>
                                {municipalityOptions.map((option) => (
                                    <SelectItem
                                        key={option.id}
                                        value={String(option.id)}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.municipality_id} />
                    </div>
                    <div className="space-y-2 sm:col-span-2">
                        <Label htmlFor="venue-gps-location">
                            Google Maps location
                        </Label>
                        <Input
                            id="venue-gps-location"
                            value={data.gps_location}
                            onChange={(e) =>
                                setData('gps_location', e.target.value)
                            }
                            placeholder="7.123456, 125.123456 or paste a Google Maps URL"
                        />
                        <p className="text-xs text-muted-foreground">
                            Accepts coordinates, a Google Maps share link, or an
                            embed URL.
                        </p>
                        <InputError message={errors.gps_location} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="venue-readiness">
                            Readiness status
                        </Label>
                        <Select
                            value={data.readiness_status}
                            onValueChange={(value) =>
                                setData('readiness_status', value)
                            }
                        >
                            <SelectTrigger id="venue-readiness">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="planned">Planned</SelectItem>
                                <SelectItem value="for_validation">
                                    For validation
                                </SelectItem>
                                <SelectItem value="ready">Ready</SelectItem>
                                <SelectItem value="needs_attention">
                                    Needs attention
                                </SelectItem>
                                <SelectItem value="unavailable">
                                    Unavailable
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="venue-notes">Notes</Label>
                        <Input
                            id="venue-notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            placeholder="Facilities, capacity, contact person…"
                        />
                        <InputError message={errors.notes} />
                    </div>
                    <div className="space-y-2 sm:col-span-2">
                        <p className="text-sm font-medium">
                            Add competition areas
                        </p>
                        <p className="text-xs text-muted-foreground">
                            Create numbered courts, tables, boards, triangles,
                            fields, or another custom area within this venue.
                        </p>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="venue-area-type">Area type</Label>
                        <Select
                            value={data.competition_area_type || 'none'}
                            onValueChange={(value) =>
                                setData(
                                    'competition_area_type',
                                    value === 'none' ? '' : value,
                                )
                            }
                        >
                            <SelectTrigger id="venue-area-type">
                                <SelectValue placeholder="No areas to add" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">None</SelectItem>
                                <SelectItem value="court">Court</SelectItem>
                                <SelectItem value="table">Table</SelectItem>
                                <SelectItem value="board">Board</SelectItem>
                                <SelectItem value="triangle">
                                    Triangle
                                </SelectItem>
                                <SelectItem value="field">Field</SelectItem>
                                <SelectItem value="custom">Custom</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.competition_area_type} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="venue-area-count">
                            Total numbered areas
                        </Label>
                        <Input
                            id="venue-area-count"
                            type="number"
                            min={1}
                            max={100}
                            disabled={!data.competition_area_type}
                            value={data.competition_area_count}
                            onChange={(e) =>
                                setData(
                                    'competition_area_count',
                                    e.target.value,
                                )
                            }
                            placeholder="e.g. 3"
                        />
                        <InputError message={errors.competition_area_count} />
                    </div>
                    {data.competition_area_type === 'custom' && (
                        <div className="space-y-2 sm:col-span-2">
                            <Label htmlFor="venue-area-label">
                                Custom area label
                            </Label>
                            <Input
                                id="venue-area-label"
                                value={data.competition_area_label}
                                onChange={(e) =>
                                    setData(
                                        'competition_area_label',
                                        e.target.value,
                                    )
                                }
                                placeholder="e.g. Diamond, Mat, Lane"
                            />
                            <InputError
                                message={errors.competition_area_label}
                            />
                        </div>
                    )}
                    <div className="space-y-2">
                        <Label htmlFor="venue-public-notes">Public notes</Label>
                        <Textarea
                            id="venue-public-notes"
                            className="min-h-20"
                            value={data.public_notes}
                            onChange={(e) =>
                                setData('public_notes', e.target.value)
                            }
                        />
                        <InputError message={errors.public_notes} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="venue-internal-notes">
                            Internal notes
                        </Label>
                        <Textarea
                            id="venue-internal-notes"
                            className="min-h-20"
                            value={data.internal_notes}
                            onChange={(e) =>
                                setData('internal_notes', e.target.value)
                            }
                        />
                        <InputError message={errors.internal_notes} />
                    </div>
                    <DialogFooter className="sm:col-span-2">
                        <Button type="submit" disabled={processing}>
                            {venue ? 'Save changes' : 'Create venue'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Venues({
    venues,
    filters,
    canManage,
    canArchive,
    municipalityOptions,
    sportOptions,
}: Props) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Venue | null>(null);
    const [viewing, setViewing] = useState<Venue | null>(null);

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (venue: Venue) => {
        setEditing(venue);
        setFormOpen(true);
    };

    return (
        <>
            <Head title="Venues" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Venues"
                    description="Playing venues and facilities for meet events."
                    actions={
                        canManage && (
                            <Button onClick={openCreate}>
                                <Plus />
                                Add venue
                            </Button>
                        )
                    }
                />

                <SearchBar
                    initial={filters.search}
                    placeholder="Search venues"
                    url={index().url}
                />

                {venues.data.length === 0 ? (
                    <EmptyState
                        icon={MapPin}
                        title="No venues yet"
                        description="Venues where meet events are held will appear here."
                        action={
                            canManage && (
                                <Button onClick={openCreate}>Add venue</Button>
                            )
                        }
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Address</TableHead>
                                    <TableHead>Assignments</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {venues.data.map((venue) => (
                                    <TableRow key={venue.id}>
                                        <TableCell className="font-medium">
                                            {venue.name}
                                        </TableCell>
                                        <TableCell>
                                            {venue.address ?? '—'}
                                            {venue.municipality && (
                                                <span className="block text-xs text-muted-foreground">
                                                    {venue.municipality}
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="max-w-64 truncate">
                                            <span className="block">
                                                {venue.sports.join(', ') ||
                                                    'No sports assigned'}
                                            </span>
                                            <span className="block text-xs text-muted-foreground">
                                                {venue.competition_areas.length}{' '}
                                                area(s) ·{' '}
                                                {venue.game_coordinators.length}{' '}
                                                coordinator(s)
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    venue.active
                                                        ? 'success'
                                                        : 'outline'
                                                }
                                            >
                                                {venue.active
                                                    ? 'Active'
                                                    : 'Archived'}
                                            </Badge>
                                        </TableCell>
                                        {canManage && (
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            setViewing(venue)
                                                        }
                                                    >
                                                        View
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            openEdit(venue)
                                                        }
                                                    >
                                                        Edit
                                                    </Button>
                                                    {canArchive && (
                                                        <ConfirmDialog
                                                            trigger={
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                >
                                                                    {venue.active
                                                                        ? 'Archive'
                                                                        : 'Restore'}
                                                                </Button>
                                                            }
                                                            title={
                                                                venue.active
                                                                    ? 'Archive venue?'
                                                                    : 'Restore venue?'
                                                            }
                                                            description={
                                                                venue.active
                                                                    ? 'Archived venues stay in records but are hidden from new schedules.'
                                                                    : 'The venue becomes available for scheduling again.'
                                                            }
                                                            confirmLabel={
                                                                venue.active
                                                                    ? 'Archive'
                                                                    : 'Restore'
                                                            }
                                                            onConfirm={() =>
                                                                router.patch(
                                                                    venue.active
                                                                        ? archive(
                                                                              venue.id,
                                                                          ).url
                                                                        : restore(
                                                                              venue.id,
                                                                          ).url,
                                                                    {},
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        />
                                                    )}
                                                    {canArchive && (
                                                        <ConfirmDialog
                                                            trigger={
                                                                <Button
                                                                    variant="destructive"
                                                                    size="sm"
                                                                >
                                                                    Delete
                                                                </Button>
                                                            }
                                                            title="Delete venue?"
                                                            description="This permanently removes the venue. Only venues without scheduled events can be deleted."
                                                            confirmLabel="Delete"
                                                            destructive
                                                            onConfirm={() =>
                                                                router.delete(
                                                                    destroy(
                                                                        venue.id,
                                                                    ).url,
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        />
                                                    )}
                                                </div>
                                            </TableCell>
                                        )}
                                        {!canManage && (
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        setViewing(venue)
                                                    }
                                                >
                                                    View
                                                </Button>
                                            </TableCell>
                                        )}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <PaginationControls
                    page={venues}
                    url={index().url}
                    label="venues"
                    params={filters.search ? { search: filters.search } : {}}
                />
            </div>

            <VenueFormDialog
                key={editing?.id ?? 'create'}
                venue={editing}
                municipalityOptions={municipalityOptions}
                sportOptions={sportOptions}
                canArchive={canArchive}
                open={formOpen}
                onOpenChange={(open) => {
                    setFormOpen(open);

                    if (!open) {
                        setEditing(null);
                    }
                }}
            />
            <VenueDetailsDialog
                venue={viewing}
                open={viewing !== null}
                onOpenChange={(open) => !open && setViewing(null)}
            />
        </>
    );
}

Venues.layout = {
    breadcrumbs: [
        {
            title: 'Venues',
            href: index(),
        },
    ],
};
