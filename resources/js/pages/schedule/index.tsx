import { Head, Link, router, useForm } from '@inertiajs/react';
import { CalendarDays, Plus, Printer } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { PaginationControls } from '@/components/pagination-controls';
import type { Paginated } from '@/components/pagination-controls';
import { SearchBar } from '@/components/search-bar';
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
import { schedule as scheduleSheet } from '@/routes/reports';
import { destroy, index, store, update } from '@/routes/schedule';

type ScheduleSlot = {
    id: number;
    meet_id: number;
    event_id: number;
    venue_id: number;
    meet: string;
    event: string;
    venue: string;
    date: string;
    date_label: string;
    starts_at: string;
    ends_at: string;
    note: string | null;
};

type Option = { id: number; label: string };

type EventOption = Option & { meet_id: number };

type Props = {
    schedules: Paginated<ScheduleSlot>;
    filters: {
        search: string;
        meet_id: number | null;
        venue_id: number | null;
        date: string | null;
    };
    meetFilterOptions: Option[];
    venueFilterOptions: Option[];
    schedulableMeets: Option[];
    eventOptionsByMeet: EventOption[];
    venueOptions: Option[];
    canManage: boolean;
};

function SlotFormDialog({
    slot,
    schedulableMeets,
    eventOptionsByMeet,
    venueOptions,
    open,
    onOpenChange,
}: {
    slot: ScheduleSlot | null;
    schedulableMeets: Option[];
    eventOptionsByMeet: EventOption[];
    venueOptions: Option[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        meet_id: slot ? String(slot.meet_id) : '',
        event_id: slot ? String(slot.event_id) : '',
        venue_id: slot ? String(slot.venue_id) : '',
        scheduled_date: slot?.date ?? '',
        starts_at: slot?.starts_at ?? '',
        ends_at: slot?.ends_at ?? '',
        note: slot?.note ?? '',
    });

    const eventOptions = eventOptionsByMeet.filter(
        (option) => String(option.meet_id) === data.meet_id,
    );

    const submit = (e: FormEvent) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        };

        if (slot) {
            put(update(slot.id).url, options);
        } else {
            post(store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {slot ? 'Edit schedule slot' : 'Add schedule slot'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="slot-meet">Meet</Label>
                        <Select
                            value={data.meet_id}
                            onValueChange={(value) => {
                                setData('meet_id', value);
                                setData('event_id', '');
                            }}
                        >
                            <SelectTrigger id="slot-meet">
                                <SelectValue placeholder="Select a meet" />
                            </SelectTrigger>
                            <SelectContent>
                                {schedulableMeets.map((option) => (
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
                        <Label htmlFor="slot-event">Event</Label>
                        <Select
                            value={data.event_id}
                            onValueChange={(value) =>
                                setData('event_id', value)
                            }
                            disabled={!data.meet_id}
                        >
                            <SelectTrigger id="slot-event">
                                <SelectValue
                                    placeholder={
                                        data.meet_id
                                            ? 'Select an event'
                                            : 'Select a meet first'
                                    }
                                />
                            </SelectTrigger>
                            <SelectContent>
                                {eventOptions.map((option) => (
                                    <SelectItem
                                        key={option.id}
                                        value={String(option.id)}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.event_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="slot-venue">Venue</Label>
                        <Select
                            value={data.venue_id}
                            onValueChange={(value) =>
                                setData('venue_id', value)
                            }
                        >
                            <SelectTrigger id="slot-venue">
                                <SelectValue placeholder="Select a venue" />
                            </SelectTrigger>
                            <SelectContent>
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
                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="space-y-2">
                            <Label htmlFor="slot-date">Date</Label>
                            <Input
                                id="slot-date"
                                type="date"
                                value={data.scheduled_date}
                                onChange={(e) =>
                                    setData('scheduled_date', e.target.value)
                                }
                            />
                            <InputError message={errors.scheduled_date} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="slot-start">Start</Label>
                            <Input
                                id="slot-start"
                                type="time"
                                value={data.starts_at}
                                onChange={(e) =>
                                    setData('starts_at', e.target.value)
                                }
                            />
                            <InputError message={errors.starts_at} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="slot-end">End</Label>
                            <Input
                                id="slot-end"
                                type="time"
                                value={data.ends_at}
                                onChange={(e) =>
                                    setData('ends_at', e.target.value)
                                }
                            />
                            <InputError message={errors.ends_at} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="slot-note">Note</Label>
                        <Input
                            id="slot-note"
                            value={data.note}
                            onChange={(e) => setData('note', e.target.value)}
                            placeholder="Session, division, reminders…"
                        />
                        <InputError message={errors.note} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {slot ? 'Save changes' : 'Create slot'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Schedule({
    schedules,
    filters,
    meetFilterOptions,
    venueFilterOptions,
    schedulableMeets,
    eventOptionsByMeet,
    venueOptions,
    canManage,
}: Props) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<ScheduleSlot | null>(null);

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (slot: ScheduleSlot) => {
        setEditing(slot);
        setFormOpen(true);
    };

    const applyFilters = (overrides: {
        meet_id?: string;
        venue_id?: string;
        date?: string;
    }) => {
        const params: Record<string, string> = {};

        const meetId = overrides.meet_id ?? String(filters.meet_id ?? '');
        const venueId = overrides.venue_id ?? String(filters.venue_id ?? '');
        const date = overrides.date ?? filters.date ?? '';

        if (meetId && meetId !== 'all') {
            params.meet_id = meetId;
        }

        if (venueId && venueId !== 'all') {
            params.venue_id = venueId;
        }

        if (date) {
            params.date = date;
        }

        if (filters.search) {
            params.search = filters.search;
        }

        router.get(index().url, params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const selectParams = {
        ...(filters.meet_id ? { meet_id: String(filters.meet_id) } : {}),
        ...(filters.venue_id ? { venue_id: String(filters.venue_id) } : {}),
        ...(filters.date ? { date: filters.date } : {}),
    };

    const filterParams = {
        ...selectParams,
        ...(filters.search ? { search: filters.search } : {}),
    };

    return (
        <>
            <Head title="Schedule" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Schedule"
                    description="When and where each event is played."
                    actions={
                        <>
                            <Button variant="outline" asChild>
                                <Link
                                    href={
                                        scheduleSheet({
                                            query: filters.date
                                                ? { date: filters.date }
                                                : {},
                                        }).url
                                    }
                                >
                                    <Printer />
                                    Daily sheet
                                </Link>
                            </Button>
                            {canManage && schedulableMeets.length > 0 && (
                                <Button onClick={openCreate}>
                                    <Plus />
                                    Add slot
                                </Button>
                            )}
                        </>
                    }
                />

                <div className="flex flex-wrap gap-2">
                    <SearchBar
                        initial={filters.search}
                        placeholder="Search by event name"
                        url={index().url}
                        extraParams={selectParams}
                    />
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
                            {meetFilterOptions.map((option) => (
                                <SelectItem
                                    key={option.id}
                                    value={String(option.id)}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={String(filters.venue_id ?? 'all')}
                        onValueChange={(value) =>
                            applyFilters({ venue_id: value })
                        }
                    >
                        <SelectTrigger
                            className="w-56"
                            aria-label="Filter by venue"
                        >
                            <SelectValue placeholder="All venues" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All venues</SelectItem>
                            {venueFilterOptions.map((option) => (
                                <SelectItem
                                    key={option.id}
                                    value={String(option.id)}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Input
                        type="date"
                        className="w-44"
                        aria-label="Filter by day"
                        value={filters.date ?? ''}
                        onChange={(e) => applyFilters({ date: e.target.value })}
                    />
                </div>

                {schedules.data.length === 0 ? (
                    <EmptyState
                        icon={CalendarDays}
                        title="No schedule slots found"
                        description="Scheduled events with their venues will appear here."
                        action={
                            canManage &&
                            schedulableMeets.length > 0 && (
                                <Button onClick={openCreate}>Add slot</Button>
                            )
                        }
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Day</TableHead>
                                    <TableHead>Time</TableHead>
                                    <TableHead>Event</TableHead>
                                    <TableHead>Venue</TableHead>
                                    <TableHead>Meet</TableHead>
                                    <TableHead>Note</TableHead>
                                    {canManage && (
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {schedules.data.map((slot) => (
                                    <TableRow key={slot.id}>
                                        <TableCell className="font-medium whitespace-nowrap">
                                            {slot.date_label}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap">
                                            {slot.starts_at}–{slot.ends_at}
                                        </TableCell>
                                        <TableCell>{slot.event}</TableCell>
                                        <TableCell>{slot.venue}</TableCell>
                                        <TableCell>{slot.meet}</TableCell>
                                        <TableCell className="max-w-48 truncate">
                                            {slot.note ?? '—'}
                                        </TableCell>
                                        {canManage && (
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            openEdit(slot)
                                                        }
                                                    >
                                                        Edit
                                                    </Button>
                                                    <ConfirmDialog
                                                        trigger={
                                                            <Button
                                                                variant="destructive"
                                                                size="sm"
                                                            >
                                                                Delete
                                                            </Button>
                                                        }
                                                        title="Delete schedule slot?"
                                                        description="This removes the slot from the schedule. The event itself is not affected."
                                                        confirmLabel="Delete"
                                                        destructive
                                                        onConfirm={() =>
                                                            router.delete(
                                                                destroy(slot.id)
                                                                    .url,
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
                    page={schedules}
                    url={index().url}
                    label="slots"
                    params={filterParams}
                />
            </div>

            <SlotFormDialog
                key={editing?.id ?? 'create'}
                slot={editing}
                schedulableMeets={schedulableMeets}
                eventOptionsByMeet={eventOptionsByMeet}
                venueOptions={venueOptions}
                open={formOpen}
                onOpenChange={setFormOpen}
            />
        </>
    );
}

Schedule.layout = {
    breadcrumbs: [
        {
            title: 'Schedule',
            href: index(),
        },
    ],
};
