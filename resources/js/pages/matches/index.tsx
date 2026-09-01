import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Plus, Radio, Swords } from 'lucide-react';
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
import { board as scoringBoard } from '@/routes/scoring';

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
    event_id: number;
    event_schedule_id: number | null;
    event: string;
    round_label: string;
    sequence: number;
    status: string;
    status_label: string;
    schedule_label: string | null;
    competition_area: string | null;
    live_scoring_enabled: boolean;
    awards_medals: boolean;
    participants: Participant[];
    transitions: Transition[];
    is_scheduled: boolean;
};

type Option = { id: number; label: string };

type ScheduleOption = Option & { event_id: number };

type EntryOption = Option & { event_id: number };

type Props = {
    matches: Paginated<Match>;
    filters: { event_id: number | null };
    eventOptions: Option[];
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
    eventOptions,
    scheduleOptions,
    open,
    onOpenChange,
}: {
    match: Match | null;
    eventOptions: Option[];
    scheduleOptions: ScheduleOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset, transform } =
        useForm({
            event_id: match ? String(match.event_id) : '',
            event_schedule_id: match?.event_schedule_id
                ? String(match.event_schedule_id)
                : 'none',
            round_label: match?.round_label ?? '',
            sequence: match ? String(match.sequence) : '1',
            competition_area: match?.competition_area ?? '',
            live_scoring_enabled: match?.live_scoring_enabled ?? false,
            awards_medals: match?.awards_medals ?? false,
        });

    transform((current) => ({
        ...current,
        event_schedule_id:
            current.event_schedule_id === 'none'
                ? null
                : current.event_schedule_id,
    }));

    const slotOptions = scheduleOptions.filter(
        (option) => String(option.event_id) === data.event_id,
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
            <DialogContent className="max-h-[92vh] w-[calc(100vw-2rem)] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>
                        {match ? 'Edit match' : 'Add match'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="match-event">Event</Label>
                        <Select
                            value={data.event_id}
                            onValueChange={(value) => {
                                setData('event_id', value);
                                setData('event_schedule_id', 'none');
                            }}
                        >
                            <SelectTrigger id="match-event">
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
                    <div className="space-y-2">
                        <Label htmlFor="competition-area">
                            Competition area
                        </Label>
                        <Input
                            id="competition-area"
                            value={data.competition_area}
                            onChange={(e) =>
                                setData('competition_area', e.target.value)
                            }
                            placeholder="Court, lane, mat, or ring"
                        />
                        <InputError message={errors.competition_area} />
                    </div>
                    <div className="flex flex-wrap gap-6">
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={data.live_scoring_enabled}
                                onCheckedChange={(checked) =>
                                    setData(
                                        'live_scoring_enabled',
                                        checked === true,
                                    )
                                }
                            />
                            Live scoreboard
                        </label>
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={data.awards_medals}
                                onCheckedChange={(checked) =>
                                    setData('awards_medals', checked === true)
                                }
                            />
                            Awards medals
                        </label>
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
        (option) => option.event_id === match.event_id,
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
            <DialogContent className="max-h-[92vh] w-[calc(100vw-2rem)] overflow-y-auto sm:max-w-4xl">
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
    eventOptions,
    scheduleOptions,
    entryOptions,
    canManage,
}: Props) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Match | null>(null);
    const [participantsFor, setParticipantsFor] = useState<Match | null>(null);
    const isTournamentScoped =
        usePage().props.auth.user?.is_tournament_scoped ?? false;

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (match: Match) => {
        setEditing(match);
        setFormOpen(true);
    };

    const applyFilters = (overrides: { event_id?: string }) => {
        const params: Record<string, string> = {};

        const eventId = overrides.event_id ?? String(filters.event_id ?? '');

        if (eventId && eventId !== 'all') {
            params.event_id = eventId;
        }

        router.get(index().url, params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const filterParams = {
        ...(filters.event_id ? { event_id: String(filters.event_id) } : {}),
    };

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

                {!isTournamentScoped && (
                    <div className="flex flex-wrap gap-2">
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
                    </div>
                )}

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
                                    <TableHead>Live</TableHead>
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
                                        <TableCell className="font-medium">
                                            {match.event}
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
                                        <TableCell>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                                disabled={
                                                    !match.live_scoring_enabled
                                                }
                                            >
                                                <Link
                                                    href={
                                                        scoringBoard(match.id)
                                                            .url
                                                    }
                                                >
                                                    <Radio aria-hidden="true" />
                                                    Live
                                                </Link>
                                            </Button>
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
                eventOptions={eventOptions}
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
