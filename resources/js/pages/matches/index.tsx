import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Swords } from 'lucide-react';
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
    participants as participantsRoute,
    status as statusRoute,
    store,
    update,
} from '@/routes/matches';

type Participant = {
    entry_id: number;
    name: string;
    school: string;
};

type Transition = {
    value: string;
    action_label: string;
};

type Match = {
    id: number;
    meet_id: number;
    event_id: number;
    event_schedule_id: number | null;
    meet: string;
    event: string;
    round_label: string;
    sequence: number;
    status: string;
    status_label: string;
    schedule_label: string | null;
    participants: Participant[];
    transitions: Transition[];
    is_scheduled: boolean;
};

type Option = { id: number; label: string };

type EventOption = Option & { meet_id: number };

type ScheduleOption = Option & { meet_id: number; event_id: number };

type EntryOption = Option & { meet_id: number; event_id: number };

type Props = {
    matches: Paginated<Match>;
    filters: { meet_id: number | null; event_id: number | null };
    meetOptions: Option[];
    eventOptionsByMeet: EventOption[];
    scheduleOptions: ScheduleOption[];
    entryOptions: EntryOption[];
    canManage: boolean;
};

const statusVariants: Record<string, 'default' | 'secondary' | 'outline'> = {
    scheduled: 'default',
    completed: 'secondary',
    walkover: 'outline',
    cancelled: 'outline',
};

function MatchFormDialog({
    match,
    meetOptions,
    eventOptionsByMeet,
    scheduleOptions,
    open,
    onOpenChange,
}: {
    match: Match | null;
    meetOptions: Option[];
    eventOptionsByMeet: EventOption[];
    scheduleOptions: ScheduleOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset, transform } =
        useForm({
            meet_id: match ? String(match.meet_id) : '',
            event_id: match ? String(match.event_id) : '',
            event_schedule_id: match?.event_schedule_id
                ? String(match.event_schedule_id)
                : 'none',
            round_label: match?.round_label ?? '',
            sequence: match ? String(match.sequence) : '1',
        });

    transform((current) => ({
        ...current,
        event_schedule_id:
            current.event_schedule_id === 'none'
                ? null
                : current.event_schedule_id,
    }));

    const eventOptions = eventOptionsByMeet.filter(
        (option) => String(option.meet_id) === data.meet_id,
    );

    const slotOptions = scheduleOptions.filter(
        (option) =>
            String(option.meet_id) === data.meet_id &&
            String(option.event_id) === data.event_id,
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

        if (match) {
            put(update(match.id).url, options);
        } else {
            post(store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {match ? 'Edit match' : 'Add match'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="match-meet">Meet</Label>
                        <Select
                            value={data.meet_id}
                            onValueChange={(value) => {
                                setData('meet_id', value);
                                setData('event_id', '');
                                setData('event_schedule_id', 'none');
                            }}
                        >
                            <SelectTrigger id="match-meet">
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
                        <Label htmlFor="match-event">Event</Label>
                        <Select
                            value={data.event_id}
                            onValueChange={(value) => {
                                setData('event_id', value);
                                setData('event_schedule_id', 'none');
                            }}
                            disabled={!data.meet_id}
                        >
                            <SelectTrigger id="match-event">
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
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="match-round">Round</Label>
                            <Input
                                id="match-round"
                                value={data.round_label}
                                onChange={(e) =>
                                    setData('round_label', e.target.value)
                                }
                                placeholder="Heat 1, Semifinal, Final…"
                            />
                            <InputError message={errors.round_label} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="match-sequence">Sequence</Label>
                            <Input
                                id="match-sequence"
                                type="number"
                                min={1}
                                value={data.sequence}
                                onChange={(e) =>
                                    setData('sequence', e.target.value)
                                }
                            />
                            <InputError message={errors.sequence} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="match-slot">
                            Schedule slot (optional)
                        </Label>
                        <Select
                            value={data.event_schedule_id}
                            onValueChange={(value) =>
                                setData('event_schedule_id', value)
                            }
                            disabled={!data.event_id}
                        >
                            <SelectTrigger id="match-slot">
                                <SelectValue placeholder="No slot" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">No slot</SelectItem>
                                {slotOptions.map((option) => (
                                    <SelectItem
                                        key={option.id}
                                        value={String(option.id)}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.event_schedule_id} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {match ? 'Save changes' : 'Create match'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function ParticipantsDialog({
    match,
    entryOptions,
    open,
    onOpenChange,
}: {
    match: Match;
    entryOptions: EntryOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, put, processing, errors } = useForm({
        entry_ids: match.participants.map((p) => p.entry_id),
    });

    const options = entryOptions.filter(
        (option) =>
            option.meet_id === match.meet_id &&
            option.event_id === match.event_id,
    );

    const toggle = (entryId: number, checked: boolean) => {
        setData(
            'entry_ids',
            checked
                ? [...data.entry_ids, entryId]
                : data.entry_ids.filter((id) => id !== entryId),
        );
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(participantsRoute(match.id).url, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        Participants — {match.round_label}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    {options.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No confirmed entries for this event yet. Confirm
                            entries first.
                        </p>
                    ) : (
                        <div className="max-h-72 space-y-2 overflow-y-auto rounded-lg border p-3">
                            {options.map((option) => (
                                <label
                                    key={option.id}
                                    className="flex items-center gap-2 text-sm"
                                >
                                    <Checkbox
                                        checked={data.entry_ids.includes(
                                            option.id,
                                        )}
                                        onCheckedChange={(checked) =>
                                            toggle(option.id, checked === true)
                                        }
                                    />
                                    {option.label}
                                </label>
                            ))}
                        </div>
                    )}
                    <InputError message={errors.entry_ids} />
                    <DialogFooter>
                        <Button
                            type="submit"
                            disabled={processing || options.length === 0}
                        >
                            Save participants
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Matches({
    matches,
    filters,
    meetOptions,
    eventOptionsByMeet,
    scheduleOptions,
    entryOptions,
    canManage,
}: Props) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Match | null>(null);
    const [participantsFor, setParticipantsFor] = useState<Match | null>(null);

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (match: Match) => {
        setEditing(match);
        setFormOpen(true);
    };

    const applyFilters = (overrides: {
        meet_id?: string;
        event_id?: string;
    }) => {
        const params: Record<string, string> = {};

        const meetId = overrides.meet_id ?? String(filters.meet_id ?? '');
        const eventId = overrides.event_id ?? String(filters.event_id ?? '');

        if (meetId && meetId !== 'all') {
            params.meet_id = meetId;
        }

        if (eventId && eventId !== 'all') {
            params.event_id = eventId;
        }

        router.get(index().url, params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const filterParams = {
        ...(filters.meet_id ? { meet_id: String(filters.meet_id) } : {}),
        ...(filters.event_id ? { event_id: String(filters.event_id) } : {}),
    };

    const eventFilterOptions = filters.meet_id
        ? eventOptionsByMeet.filter(
              (option) => option.meet_id === filters.meet_id,
          )
        : eventOptionsByMeet.filter(
              (option, i, all) =>
                  all.findIndex((other) => other.id === option.id) === i,
          );

    return (
        <>
            <Head title="Matches" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Matches"
                    description="Matches and heats per meet event."
                    actions={
                        canManage && (
                            <Button onClick={openCreate}>
                                <Plus />
                                Add match
                            </Button>
                        )
                    }
                />

                <div className="flex flex-wrap gap-2">
                    <Select
                        value={String(filters.meet_id ?? 'all')}
                        onValueChange={(value) =>
                            applyFilters({ meet_id: value, event_id: 'all' })
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
                    <Select
                        value={String(filters.event_id ?? 'all')}
                        onValueChange={(value) =>
                            applyFilters({ event_id: value })
                        }
                    >
                        <SelectTrigger
                            className="w-72"
                            aria-label="Filter by event"
                        >
                            <SelectValue placeholder="All events" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All events</SelectItem>
                            {eventFilterOptions.map((option) => (
                                <SelectItem
                                    key={`${option.meet_id}-${option.id}`}
                                    value={String(option.id)}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {matches.data.length === 0 ? (
                    <EmptyState
                        icon={Swords}
                        title="No matches found"
                        description="Matches and heats created for meet events will appear here."
                        action={
                            canManage && (
                                <Button onClick={openCreate}>Add match</Button>
                            )
                        }
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Event</TableHead>
                                    <TableHead>Round</TableHead>
                                    <TableHead>Schedule</TableHead>
                                    <TableHead>Participants</TableHead>
                                    <TableHead>Status</TableHead>
                                    {canManage && (
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {matches.data.map((match) => (
                                    <TableRow key={match.id}>
                                        <TableCell>
                                            <p className="font-medium">
                                                {match.event}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {match.meet}
                                            </p>
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap">
                                            {match.round_label} · #
                                            {match.sequence}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap">
                                            {match.schedule_label ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {match.participants.length === 0 ? (
                                                '—'
                                            ) : (
                                                <ul className="space-y-0.5 text-sm">
                                                    {match.participants.map(
                                                        (participant) => (
                                                            <li
                                                                key={
                                                                    participant.entry_id
                                                                }
                                                            >
                                                                {
                                                                    participant.name
                                                                }{' '}
                                                                <span className="text-muted-foreground">
                                                                    (
                                                                    {
                                                                        participant.school
                                                                    }
                                                                    )
                                                                </span>
                                                            </li>
                                                        ),
                                                    )}
                                                </ul>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariants[
                                                        match.status
                                                    ] ?? 'outline'
                                                }
                                            >
                                                {match.status_label}
                                            </Badge>
                                        </TableCell>
                                        {canManage && (
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    {match.is_scheduled && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                setParticipantsFor(
                                                                    match,
                                                                )
                                                            }
                                                        >
                                                            Participants
                                                        </Button>
                                                    )}
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            openEdit(match)
                                                        }
                                                    >
                                                        Edit
                                                    </Button>
                                                    {match.transitions.map(
                                                        (transition) => (
                                                            <ConfirmDialog
                                                                key={
                                                                    transition.value
                                                                }
                                                                trigger={
                                                                    <Button
                                                                        variant="outline"
                                                                        size="sm"
                                                                    >
                                                                        {
                                                                            transition.action_label
                                                                        }
                                                                    </Button>
                                                                }
                                                                title={`${transition.action_label}?`}
                                                                description="This is a final match status. Participants can no longer be changed."
                                                                confirmLabel={
                                                                    transition.action_label
                                                                }
                                                                onConfirm={() =>
                                                                    router.patch(
                                                                        statusRoute(
                                                                            match.id,
                                                                        ).url,
                                                                        {
                                                                            status: transition.value,
                                                                        },
                                                                        {
                                                                            preserveScroll: true,
                                                                        },
                                                                    )
                                                                }
                                                            />
                                                        ),
                                                    )}
                                                    <ConfirmDialog
                                                        trigger={
                                                            <Button
                                                                variant="destructive"
                                                                size="sm"
                                                            >
                                                                Delete
                                                            </Button>
                                                        }
                                                        title="Delete match?"
                                                        description="This removes the match and its participant list."
                                                        confirmLabel="Delete"
                                                        destructive
                                                        onConfirm={() =>
                                                            router.delete(
                                                                destroy(
                                                                    match.id,
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
                    page={matches}
                    url={index().url}
                    label="matches"
                    params={filterParams}
                />
            </div>

            <MatchFormDialog
                key={editing?.id ?? 'create'}
                match={editing}
                meetOptions={meetOptions}
                eventOptionsByMeet={eventOptionsByMeet}
                scheduleOptions={scheduleOptions}
                open={formOpen}
                onOpenChange={setFormOpen}
            />

            {participantsFor && (
                <ParticipantsDialog
                    key={participantsFor.id}
                    match={participantsFor}
                    entryOptions={entryOptions}
                    open={participantsFor !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setParticipantsFor(null);
                        }
                    }}
                />
            )}
        </>
    );
}

Matches.layout = {
    breadcrumbs: [
        {
            title: 'Matches',
            href: index(),
        },
    ],
};
