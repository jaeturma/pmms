import { Head, router, useForm } from '@inertiajs/react';
import { MapPin, Plus } from 'lucide-react';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
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
    notes: string | null;
    active: boolean;
};

type Props = {
    venues: Paginated<Venue>;
    filters: { search: string };
    canManage: boolean;
};

function VenueFormDialog({
    venue,
    open,
    onOpenChange,
}: {
    venue: Venue | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: venue?.name ?? '',
        address: venue?.address ?? '',
        notes: venue?.notes ?? '',
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
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {venue ? 'Edit venue' : 'Add venue'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
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
                        <Label htmlFor="venue-address">Address</Label>
                        <Input
                            id="venue-address"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                        />
                        <InputError message={errors.address} />
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
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {venue ? 'Save changes' : 'Create venue'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Venues({ venues, filters, canManage }: Props) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Venue | null>(null);

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
                                    <TableHead>Notes</TableHead>
                                    <TableHead>Status</TableHead>
                                    {canManage && (
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    )}
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
                                        </TableCell>
                                        <TableCell className="max-w-64 truncate">
                                            {venue.notes ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    venue.active
                                                        ? 'secondary'
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
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            openEdit(venue)
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
                                                </div>
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
                open={formOpen}
                onOpenChange={setFormOpen}
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
