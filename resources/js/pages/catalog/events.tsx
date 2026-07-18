import { Head, router, useForm } from '@inertiajs/react';
import { Medal, Plus } from 'lucide-react';
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
    archive,
    destroy,
    index,
    restore,
    store,
    update,
} from '@/routes/events';

type MeetEvent = {
    id: number;
    sport_id: number;
    name: string;
    gender: string;
    age_division: string;
    is_team_event: boolean;
    max_entries_per_delegation: number;
    active: boolean;
    sport: { id: number; name: string };
};

type SportOption = {
    id: number;
    name: string;
};

type Props = {
    events: Paginated<MeetEvent>;
    filters: { search: string };
    sports: SportOption[];
    canManage: boolean;
};

const genderLabels: Record<string, string> = {
    boys: 'Boys',
    girls: 'Girls',
    mixed: 'Mixed',
};

const divisionLabels: Record<string, string> = {
    elementary: 'Elementary',
    secondary: 'Secondary',
};

function EventFormDialog({
    event,
    sports,
    open,
    onOpenChange,
}: {
    event: MeetEvent | null;
    sports: SportOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        sport_id: event ? String(event.sport_id) : '',
        name: event?.name ?? '',
        gender: event?.gender ?? '',
        age_division: event?.age_division ?? '',
        is_team_event: event?.is_team_event ?? false,
        max_entries_per_delegation: event
            ? String(event.max_entries_per_delegation)
            : '1',
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

        if (event) {
            put(update(event.id).url, options);
        } else {
            post(store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {event ? 'Edit event' : 'Add event'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="event-sport">Sport</Label>
                        <Select
                            value={data.sport_id}
                            onValueChange={(value) =>
                                setData('sport_id', value)
                            }
                        >
                            <SelectTrigger id="event-sport">
                                <SelectValue placeholder="Select a sport" />
                            </SelectTrigger>
                            <SelectContent>
                                {sports.map((sport) => (
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
                    <div className="space-y-2">
                        <Label htmlFor="event-name">Name</Label>
                        <Input
                            id="event-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="event-gender">Gender</Label>
                            <Select
                                value={data.gender}
                                onValueChange={(value) =>
                                    setData('gender', value)
                                }
                            >
                                <SelectTrigger id="event-gender">
                                    <SelectValue placeholder="Select" />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(genderLabels).map(
                                        ([value, label]) => (
                                            <SelectItem
                                                key={value}
                                                value={value}
                                            >
                                                {label}
                                            </SelectItem>
                                        ),
                                    )}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.gender} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="event-division">Division</Label>
                            <Select
                                value={data.age_division}
                                onValueChange={(value) =>
                                    setData('age_division', value)
                                }
                            >
                                <SelectTrigger id="event-division">
                                    <SelectValue placeholder="Select" />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(divisionLabels).map(
                                        ([value, label]) => (
                                            <SelectItem
                                                key={value}
                                                value={value}
                                            >
                                                {label}
                                            </SelectItem>
                                        ),
                                    )}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.age_division} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="event-max-entries">
                            Max entries per delegation
                        </Label>
                        <Input
                            id="event-max-entries"
                            type="number"
                            min={1}
                            max={50}
                            value={data.max_entries_per_delegation}
                            onChange={(e) =>
                                setData(
                                    'max_entries_per_delegation',
                                    e.target.value,
                                )
                            }
                        />
                        <InputError
                            message={errors.max_entries_per_delegation}
                        />
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="event-team"
                            checked={data.is_team_event}
                            onCheckedChange={(checked) =>
                                setData('is_team_event', checked === true)
                            }
                        />
                        <Label htmlFor="event-team">Team event</Label>
                    </div>
                    <InputError message={errors.is_team_event} />
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {event ? 'Save changes' : 'Create event'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Events({ events, filters, sports, canManage }: Props) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<MeetEvent | null>(null);

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (event: MeetEvent) => {
        setEditing(event);
        setFormOpen(true);
    };

    return (
        <>
            <Head title="Events" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Events"
                    description="Competition events under each sport."
                    actions={
                        canManage && (
                            <Button onClick={openCreate}>
                                <Plus />
                                Add event
                            </Button>
                        )
                    }
                />

                <SearchBar
                    initial={filters.search}
                    placeholder="Search events"
                    url={index().url}
                />

                {events.data.length === 0 ? (
                    <EmptyState
                        icon={Medal}
                        title="No events yet"
                        description="Events configured under the division's sports will appear here."
                        action={
                            canManage && (
                                <Button onClick={openCreate}>Add event</Button>
                            )
                        }
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Sport</TableHead>
                                    <TableHead>Gender</TableHead>
                                    <TableHead>Division</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Max entries</TableHead>
                                    <TableHead>Status</TableHead>
                                    {canManage && (
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {events.data.map((event) => (
                                    <TableRow key={event.id}>
                                        <TableCell className="font-medium">
                                            {event.name}
                                        </TableCell>
                                        <TableCell>
                                            {event.sport.name}
                                        </TableCell>
                                        <TableCell>
                                            {genderLabels[event.gender] ??
                                                event.gender}
                                        </TableCell>
                                        <TableCell>
                                            {divisionLabels[
                                                event.age_division
                                            ] ?? event.age_division}
                                        </TableCell>
                                        <TableCell>
                                            {event.is_team_event
                                                ? 'Team'
                                                : 'Individual'}
                                        </TableCell>
                                        <TableCell>
                                            {event.max_entries_per_delegation}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    event.active
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {event.active
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
                                                            openEdit(event)
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
                                                                {event.active
                                                                    ? 'Archive'
                                                                    : 'Restore'}
                                                            </Button>
                                                        }
                                                        title={
                                                            event.active
                                                                ? 'Archive event?'
                                                                : 'Restore event?'
                                                        }
                                                        description={
                                                            event.active
                                                                ? 'Archived events stay in records but are hidden from new meets.'
                                                                : 'The event becomes available for meets again.'
                                                        }
                                                        confirmLabel={
                                                            event.active
                                                                ? 'Archive'
                                                                : 'Restore'
                                                        }
                                                        onConfirm={() =>
                                                            router.patch(
                                                                event.active
                                                                    ? archive(
                                                                          event.id,
                                                                      ).url
                                                                    : restore(
                                                                          event.id,
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
                                                        title="Delete event?"
                                                        description="This permanently removes the event from the catalog."
                                                        confirmLabel="Delete"
                                                        destructive
                                                        onConfirm={() =>
                                                            router.delete(
                                                                destroy(
                                                                    event.id,
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
                    page={events}
                    url={index().url}
                    label="events"
                    params={filters.search ? { search: filters.search } : {}}
                />
            </div>

            <EventFormDialog
                key={editing?.id ?? 'create'}
                event={editing}
                sports={sports}
                open={formOpen}
                onOpenChange={setFormOpen}
            />
        </>
    );
}

Events.layout = {
    breadcrumbs: [
        {
            title: 'Events',
            href: index(),
        },
    ],
};
