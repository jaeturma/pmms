import { Head, Link, router, useForm } from '@inertiajs/react';
import { CalendarDays, Plus, Printer, Radio } from 'lucide-react';
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
import { schedule as scheduleSheet } from '@/routes/reports';
import { destroy, index, store, update } from '@/routes/schedule';
import { board as scoringBoard } from '@/routes/scoring';

type ScheduleSlot = {
    id: number;
    event_id: number;
    sport_category_id: number | null;
    venue_id: number;
    competition_area_id: number | null;
    event: string;
    sport_category: string | null;
    venue: string;
    competition_area: string | null;
    date: string;
    date_label: string;
    starts_at: string;
    ends_at: string;
    note: string | null;
    match_id: number | null;
    is_live: boolean;
    can_manage: boolean;
};

type Option = { id: number; label: string };

type EventOption = Option & { sport_id: number };

type VenueOption = Option & {
    event_id: number;
    sport_category_id: number | null;
    playing_area_type: string;
    playing_area_count: number;
    competition_area_ids: number[];
};

type SportCategoryOption = Option & { sport_id: number };
type CompetitionAreaOption = Option & {
    venue_id: number;
    area_type: string;
};

type Props = {
    schedules: Paginated<ScheduleSlot>;
    filters: {
        search: string;
        venue_id: number | null;
        date: string | null;
    };
    venueFilterOptions: Option[];
    meetIsSchedulable: boolean;
    eventOptions: EventOption[];
    venueOptions: VenueOption[];
    competitionAreaOptions: CompetitionAreaOption[];
    sportCategoryOptions: SportCategoryOption[];
    canManage: boolean;
};

function SlotFormDialog({
    slot,
    eventOptions,
    venueOptions,
    competitionAreaOptions,
    sportCategoryOptions,
    open,
    onOpenChange,
}: {
    slot: ScheduleSlot | null;
    eventOptions: EventOption[];
    venueOptions: VenueOption[];
    competitionAreaOptions: CompetitionAreaOption[];
    sportCategoryOptions: SportCategoryOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        event_id: slot ? String(slot.event_id) : '',
        sport_category_id: slot?.sport_category_id
            ? String(slot.sport_category_id)
            : '',
        venue_id: slot ? String(slot.venue_id) : '',
        competition_area_id: slot?.competition_area_id
            ? String(slot.competition_area_id)
            : '',
        scheduled_date: slot?.date ?? '',
        starts_at: slot?.starts_at ?? '',
        ends_at: slot?.ends_at ?? '',
        note: slot?.note ?? '',
    });

    const selectedEventSportId = eventOptions.find(
        (option) => String(option.id) === data.event_id,
    )?.sport_id;

    const categoryOptions = sportCategoryOptions.filter(
        (option) => option.sport_id === selectedEventSportId,
    );
    const eventVenueOptions = venueOptions.filter(
        (option) =>
            String(option.event_id) === data.event_id &&
            (option.sport_category_id === null ||
                String(option.sport_category_id) === data.sport_category_id),
    );
    const selectedVenue = eventVenueOptions.find(
        (option) => String(option.id) === data.venue_id,
    );
    const areaOptions = competitionAreaOptions.filter(
        (option) =>
            String(option.venue_id) === data.venue_id &&
            option.area_type === selectedVenue?.playing_area_type &&
            (selectedVenue?.competition_area_ids.length === 0 ||
                selectedVenue?.competition_area_ids.includes(option.id)),
    );
    const needsArea =
        (selectedVenue?.competition_area_ids.length ?? 0) > 0 ||
        (selectedVenue?.playing_area_count ?? 1) > 1;

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
            <DialogContent className="max-h-[92vh] w-[calc(100vw-2rem)] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>
                        {slot ? 'Edit schedule slot' : 'Add schedule slot'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="slot-event">Event</Label>
                        <Select
                            value={data.event_id}
                            onValueChange={(value) => {
                                setData('event_id', value);
                                setData('sport_category_id', '');
                                setData('venue_id', '');
                                setData('competition_area_id', '');
                            }}
                        >
                            <SelectTrigger id="slot-event">
                                <SelectValue placeholder="Select an event" />
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
                        <Label htmlFor="slot-category">Sport category</Label>
                        <Select
                            value={data.sport_category_id || 'none'}
                            onValueChange={(value) => {
                                setData('sport_category_id', value === 'none' ? '' : value);
                                setData('venue_id', '');
                                setData('competition_area_id', '');
                            }}
                            disabled={!data.event_id || (venueOptions.some(
                                (option) => String(option.event_id) === data.event_id && option.sport_category_id !== null,
                            ) && !data.sport_category_id)}
                        >
                            <SelectTrigger id="slot-category">
                                <SelectValue
                                    placeholder={
                                        data.event_id
                                            ? 'None'
                                            : 'Select an event first'
                                    }
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">None</SelectItem>
                                {categoryOptions.map((option) => (
                                    <SelectItem
                                        key={option.id}
                                        value={String(option.id)}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.sport_category_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="slot-venue">Venue</Label>
                        <Select
                            value={data.venue_id}
                            onValueChange={(value) => {
                                setData('venue_id', value);
                                setData('competition_area_id', '');
                            }}
                            disabled={!data.event_id}
                        >
                            <SelectTrigger id="slot-venue">
                                <SelectValue placeholder="Select a venue" />
                            </SelectTrigger>
                            <SelectContent>
                                {eventVenueOptions.map((option) => (
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
                    {needsArea && (
                        <div className="space-y-2">
                            <Label htmlFor="slot-area">
                                Competition area
                            </Label>
                            <Select
                                value={data.competition_area_id}
                                onValueChange={(value) =>
                                    setData('competition_area_id', value)
                                }
                                disabled={areaOptions.length === 0}
                            >
                                <SelectTrigger id="slot-area">
                                    <SelectValue
                                        placeholder="Select a competition area"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {areaOptions.map((option) => (
                                        <SelectItem
                                            key={option.id}
                                            value={String(option.id)}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.competition_area_id} />
                        </div>
                    )}
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
    venueFilterOptions,
    meetIsSchedulable,
    eventOptions,
    venueOptions,
    competitionAreaOptions,
    sportCategoryOptions,
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
        venue_id?: string;
        date?: string;
    }) => {
        const params: Record<string, string> = {};

        const venueId = overrides.venue_id ?? String(filters.venue_id ?? '');
        const date = overrides.date ?? filters.date ?? '';

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
                            {canManage && meetIsSchedulable && (
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
                            meetIsSchedulable && (
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
                                    <TableHead>Category</TableHead>
                                    <TableHead>Venue</TableHead>
                                    <TableHead>Note</TableHead>
                                    <TableHead>Live</TableHead>
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
                                        <TableCell>
                                            {slot.sport_category ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {slot.venue}
                                            {slot.competition_area && (
                                                <span className="block text-xs text-muted-foreground">
                                                    {slot.competition_area}
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="max-w-48 truncate">
                                            {slot.note ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {slot.match_id ? (
                                                <div className="flex items-center gap-2">
                                                    {slot.is_live && (
                                                        <Badge
                                                            variant="destructive"
                                                            className="gap-1"
                                                        >
                                                            <span
                                                                className="inline-block h-1.5 w-1.5 rounded-full bg-current"
                                                                aria-hidden="true"
                                                            />
                                                            Live
                                                        </Badge>
                                                    )}
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={
                                                                scoringBoard(
                                                                    slot.match_id,
                                                                ).url
                                                            }
                                                        >
                                                            <Radio aria-hidden="true" />
                                                            {slot.is_live
                                                                ? 'Watch'
                                                                : 'Scoreboard'}
                                                        </Link>
                                                    </Button>
                                                </div>
                                            ) : (
                                                <span className="text-sm text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        {canManage && (
                                            <TableCell className="text-right">
                                                {slot.can_manage ? (
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
                                                                    destroy(
                                                                        slot.id,
                                                                    ).url,
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                ) : (
                                                    <span className="text-sm text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
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
                eventOptions={eventOptions}
                venueOptions={venueOptions}
                competitionAreaOptions={competitionAreaOptions}
                sportCategoryOptions={sportCategoryOptions}
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
