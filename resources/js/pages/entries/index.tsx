import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ListChecks, Plus, Printer, UsersRound } from 'lucide-react';
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
    confirm as confirmRoute,
    destroy,
    index,
    store,
    withdraw,
} from '@/routes/entries';
import { eventEntries } from '@/routes/reports';

type EntryRow = {
    id: number;
    athlete: string;
    event: string;
    school: string;
    delegation: string;
    meet: string;
    status: string;
    status_label: string;
    eligibility_approved: boolean;
    can_confirm: boolean;
    can_withdraw: boolean;
    can_delete: boolean;
};

type FilterOption = {
    id: number;
    label: string;
};

type AthleteOption = {
    id: number;
    meet_id: number;
    delegation_id: number;
    label: string;
    event_ids: number[];
};

type EventOption = {
    id: number;
    meet_id: number;
    sport: string;
    is_team_event: boolean;
    delegation_ids: number[];
    label: string;
};

type Props = {
    entries: Paginated<EntryRow>;
    filters: {
        search: string;
        event_id: number | null;
        delegation_id: number | null;
    };
    eventFilterOptions: FilterOption[];
    delegationFilterOptions: FilterOption[];
    athleteOptions: AthleteOption[];
    eventOptionsByMeet: EventOption[];
    teamEntries: Array<{
        id: number;
        event: string;
        delegation: string;
        member_count: number;
        minimum: number | null;
        maximum: number | null;
        complete: boolean;
        locked: boolean;
        status: string;
        members: Array<{ id: number; name: string }>;
    }>;
};

const statusVariants: Record<string, 'default' | 'secondary' | 'outline'> = {
    submitted: 'default',
    confirmed: 'secondary',
    withdrawn: 'outline',
};

function SubmitEntryDialog({
    athleteOptions,
    eventOptionsByMeet,
    open,
    onOpenChange,
}: {
    athleteOptions: AthleteOption[];
    eventOptionsByMeet: EventOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        athlete_id: '',
        event_ids: [] as number[],
    });

    const selectedAthlete = athleteOptions.find(
        (athlete) => String(athlete.id) === data.athlete_id,
    );

    const availableEvents = selectedAthlete
        ? eventOptionsByMeet.filter(
              (event) =>
                  event.meet_id === selectedAthlete.meet_id &&
                  event.delegation_ids.includes(selectedAthlete.delegation_id) &&
                  !event.is_team_event,
          )
        : [];

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(store().url, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add athlete to events</DialogTitle>
                </DialogHeader>
                {athleteOptions.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No athletes are available for entry submission right
                        now.
                    </p>
                ) : (
                    <form onSubmit={submit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="entry-athlete">Athlete</Label>
                            <Select
                                value={data.athlete_id}
                                onValueChange={(value) => {
                                    setData('athlete_id', value);
                                    setData('event_ids', []);
                                }}
                            >
                                <SelectTrigger id="entry-athlete">
                                    <SelectValue placeholder="Select an athlete" />
                                </SelectTrigger>
                                <SelectContent>
                                    {athleteOptions.map((athlete) => (
                                        <SelectItem
                                            key={athlete.id}
                                            value={String(athlete.id)}
                                        >
                                            {athlete.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.athlete_id} />
                        </div>
                        <div className="space-y-2">
                            <Label>Events</Label>
                            <p className="text-xs text-muted-foreground">
                                Select one or more individual events. Existing
                                entries are marked below. Use Team / Pair entry
                                for doubles, relays, and team events.
                            </p>
                            <div className="max-h-72 space-y-2 overflow-y-auto rounded-md border p-3">
                                {!selectedAthlete ? (
                                    <p className="text-sm text-muted-foreground">
                                        Select an athlete first.
                                    </p>
                                ) : availableEvents.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No eligible meet events are available.
                                    </p>
                                ) : (
                                    availableEvents.map((event) => (
                                        <label
                                            key={`${event.meet_id}-${event.id}`}
                                            className="flex cursor-pointer items-start gap-3 rounded p-2 hover:bg-muted/50"
                                        >
                                            <Checkbox
                                                disabled={selectedAthlete.event_ids.includes(event.id)}
                                                checked={data.event_ids.includes(
                                                    event.id,
                                                ) || selectedAthlete.event_ids.includes(event.id)}
                                                onCheckedChange={(checked) =>
                                                    setData(
                                                        'event_ids',
                                                        checked === true
                                                            ? [
                                                                  ...data.event_ids,
                                                                  event.id,
                                                              ]
                                                            : data.event_ids.filter(
                                                                  (id) =>
                                                                      id !==
                                                                      event.id,
                                                              ),
                                                    )
                                                }
                                            />
                                            <span className="text-sm">
                                                {event.label}
                                                <span className="ml-2 text-xs text-muted-foreground">
                                                    {selectedAthlete.event_ids.includes(event.id)
                                                        ? 'Already entered'
                                                        : 'Individual'}
                                                </span>
                                            </span>
                                        </label>
                                    ))
                                )}
                            </div>
                            <InputError message={errors.event_ids} />
                        </div>
                        <DialogFooter>
                            <Button
                                type="submit"
                                disabled={
                                    processing || data.event_ids.length === 0
                                }
                            >
                                Submit {data.event_ids.length || ''}{' '}
                                {data.event_ids.length === 1
                                    ? 'entry'
                                    : 'entries'}
                            </Button>
                        </DialogFooter>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}

function TeamEntryDialog({
    athleteOptions,
    eventOptionsByMeet,
    open,
    onOpenChange,
}: {
    athleteOptions: AthleteOption[];
    eventOptionsByMeet: EventOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        event_id: '',
        athlete_ids: [] as number[],
    });
    const teamEvents = eventOptionsByMeet.filter(
        (event) => event.is_team_event,
    );
    const selectedEvent = teamEvents.find(
        (event) => String(event.id) === data.event_id,
    );
    const athletes = selectedEvent
        ? athleteOptions.filter(
              (athlete) =>
                  athlete.meet_id === selectedEvent.meet_id &&
                  selectedEvent.delegation_ids.includes(athlete.delegation_id),
          )
        : [];

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Create team / pair entry</DialogTitle>
                </DialogHeader>
                <form
                    className="space-y-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        post('/team-entries', {
                            preserveScroll: true,
                            onSuccess: () => {
                                reset();
                                onOpenChange(false);
                            },
                        });
                    }}
                >
                    <div className="space-y-2">
                        <Label>Team, pair, doubles, or relay event</Label>
                        <Select
                            value={data.event_id}
                            onValueChange={(value) => {
                                setData('event_id', value);
                                setData('athlete_ids', []);
                            }}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select a team event" />
                            </SelectTrigger>
                            <SelectContent>
                                {teamEvents.map((event) => (
                                    <SelectItem
                                        key={`${event.meet_id}-${event.id}`}
                                        value={String(event.id)}
                                    >
                                        {event.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.event_id} />
                    </div>
                    <div className="space-y-2">
                        <Label>Official team members</Label>
                        <div className="max-h-72 space-y-1 overflow-y-auto rounded-md border p-3">
                            {athletes.map((athlete) => (
                                <label
                                    key={athlete.id}
                                    className="flex cursor-pointer items-center gap-3 rounded p-2 hover:bg-muted/50"
                                >
                                    <Checkbox
                                        checked={data.athlete_ids.includes(
                                            athlete.id,
                                        )}
                                        onCheckedChange={(checked) =>
                                            setData(
                                                'athlete_ids',
                                                checked === true
                                                    ? [
                                                          ...data.athlete_ids,
                                                          athlete.id,
                                                      ]
                                                    : data.athlete_ids.filter(
                                                          (id) =>
                                                              id !== athlete.id,
                                                      ),
                                            )
                                        }
                                    />
                                    <span className="text-sm">
                                        {athlete.label}
                                    </span>
                                </label>
                            ))}
                        </div>
                        <InputError message={errors.athlete_ids} />
                    </div>
                    <DialogFooter>
                        <Button
                            type="submit"
                            disabled={
                                processing || data.athlete_ids.length === 0
                            }
                        >
                            Save team ({data.athlete_ids.length})
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Entries({
    entries,
    filters,
    eventFilterOptions,
    delegationFilterOptions,
    athleteOptions,
    eventOptionsByMeet,
    teamEntries,
}: Props) {
    const [submitOpen, setSubmitOpen] = useState(false);
    const [teamOpen, setTeamOpen] = useState(false);
    const isTournamentScoped =
        usePage().props.auth.user?.is_tournament_scoped ?? false;

    const applyFilters = (overrides: {
        event_id?: string;
        delegation_id?: string;
    }) => {
        const params: Record<string, string> = {};

        const eventId = overrides.event_id ?? String(filters.event_id ?? '');
        const delegationId =
            overrides.delegation_id ?? String(filters.delegation_id ?? '');

        if (eventId && eventId !== 'all') {
            params.event_id = eventId;
        }

        if (delegationId && delegationId !== 'all') {
            params.delegation_id = delegationId;
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
        ...(filters.event_id ? { event_id: String(filters.event_id) } : {}),
        ...(filters.delegation_id
            ? { delegation_id: String(filters.delegation_id) }
            : {}),
    };

    const filterParams = {
        ...selectParams,
        ...(filters.search ? { search: filters.search } : {}),
    };

    return (
        <>
            <Head title="Entries" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Entries"
                    description="Event entries per delegation and event."
                    actions={
                        <>
                            {filters.event_id && (
                                <Button variant="outline" asChild>
                                    <Link href={eventEntries(filters.event_id)}>
                                        <Printer />
                                        Event list
                                    </Link>
                                </Button>
                            )}
                            {athleteOptions.length > 0 && (
                                <>
                                    <Button
                                        variant="outline"
                                        onClick={() => setTeamOpen(true)}
                                    >
                                        <UsersRound />
                                        Team / Pair entry
                                    </Button>
                                    <Button onClick={() => setSubmitOpen(true)}>
                                        <Plus />
                                        Add athlete event(s)
                                    </Button>
                                </>
                            )}
                            <Button variant="outline" asChild>
                                <Link href="/swimming/rosters">
                                    <UsersRound />
                                    Swimming rosters
                                </Link>
                            </Button>
                        </>
                    }
                />

                {teamEntries.length > 0 && (
                    <div className="overflow-x-auto rounded-xl border">
                        <div className="border-b bg-muted/30 px-4 py-3">
                            <h2 className="font-semibold">Team entries</h2>
                        </div>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Event</TableHead>
                                    <TableHead>Delegation</TableHead>
                                    <TableHead>Members</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Action
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {teamEntries.map((team) => (
                                    <TableRow key={team.id}>
                                        <TableCell className="font-medium">
                                            {team.event}
                                        </TableCell>
                                        <TableCell>{team.delegation}</TableCell>
                                        <TableCell>
                                            <div>
                                                {team.member_count} members
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {team.members
                                                    .map(
                                                        (member) => member.name,
                                                    )
                                                    .join(', ')}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    team.complete
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {team.complete
                                                    ? team.status
                                                    : `Below minimum (${team.minimum})`}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {!team.locked && team.complete && (
                                                <Button
                                                    size="sm"
                                                    onClick={() =>
                                                        router.patch(
                                                            `/team-entries/${team.id}/confirm`,
                                                        )
                                                    }
                                                >
                                                    Finalize
                                                </Button>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <div className="flex flex-wrap gap-2">
                    <SearchBar
                        initial={filters.search}
                        placeholder="Search by athlete name"
                        url={index().url}
                        extraParams={selectParams}
                    />
                    {!isTournamentScoped && (
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
                                        key={option.id}
                                        value={String(option.id)}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                    <Select
                        value={String(filters.delegation_id ?? 'all')}
                        onValueChange={(value) =>
                            applyFilters({ delegation_id: value })
                        }
                    >
                        <SelectTrigger
                            className="w-72"
                            aria-label="Filter by delegation"
                        >
                            <SelectValue placeholder="All delegations" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All delegations</SelectItem>
                            {delegationFilterOptions.map((option) => (
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

                {entries.data.length === 0 ? (
                    <EmptyState
                        icon={ListChecks}
                        title="No entries found"
                        description="Submitted event entries will appear here."
                    />
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-xl border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Athlete</TableHead>
                                        <TableHead>Event</TableHead>
                                        <TableHead>Team / Delegation</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {entries.data.map((entry) => (
                                        <TableRow key={entry.id}>
                                            <TableCell>
                                                <div className="font-medium">
                                                    {entry.athlete}
                                                </div>
                                                <div className="mt-1">
                                                    <Badge
                                                        variant={
                                                            entry.eligibility_approved
                                                                ? 'secondary'
                                                                : 'outline'
                                                        }
                                                    >
                                                        {entry.eligibility_approved
                                                            ? 'Eligible'
                                                            : 'Eligibility pending'}
                                                    </Badge>
                                                </div>
                                            </TableCell>
                                            <TableCell className="max-w-sm align-top whitespace-normal">
                                                <div className="font-medium">
                                                    {
                                                        entry.event.split(
                                                            ' — ',
                                                        )[0]
                                                    }
                                                </div>
                                                <div className="text-sm text-muted-foreground">
                                                    {entry.event
                                                        .split(' — ')
                                                        .slice(1)
                                                        .join(' — ')}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {entry.delegation}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        statusVariants[
                                                            entry.status
                                                        ] ?? 'outline'
                                                    }
                                                >
                                                    {entry.status_label}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    {entry.can_confirm && (
                                                        <ConfirmDialog
                                                            trigger={
                                                                <Button size="sm">
                                                                    Confirm
                                                                </Button>
                                                            }
                                                            title="Confirm entry?"
                                                            description={`${entry.athlete} — ${entry.event}`}
                                                            confirmLabel="Confirm"
                                                            onConfirm={() =>
                                                                router.patch(
                                                                    confirmRoute(
                                                                        entry.id,
                                                                    ).url,
                                                                    {},
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        />
                                                    )}
                                                    {entry.can_withdraw && (
                                                        <ConfirmDialog
                                                            trigger={
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                >
                                                                    Withdraw
                                                                </Button>
                                                            }
                                                            title="Withdraw entry?"
                                                            description={`${entry.athlete} — ${entry.event}`}
                                                            confirmLabel="Withdraw"
                                                            onConfirm={() =>
                                                                router.patch(
                                                                    withdraw(
                                                                        entry.id,
                                                                    ).url,
                                                                    {},
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        />
                                                    )}
                                                    {entry.can_delete && (
                                                        <ConfirmDialog
                                                            trigger={
                                                                <Button
                                                                    variant="destructive"
                                                                    size="sm"
                                                                >
                                                                    Delete
                                                                </Button>
                                                            }
                                                            title="Delete withdrawn entry?"
                                                            description="This frees the athlete's slot for this event."
                                                            confirmLabel="Delete"
                                                            destructive
                                                            onConfirm={() =>
                                                                router.delete(
                                                                    destroy(
                                                                        entry.id,
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
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <PaginationControls
                            page={entries}
                            url={index().url}
                            label="entries"
                            params={filterParams}
                        />
                    </>
                )}
            </div>

            <SubmitEntryDialog
                athleteOptions={athleteOptions}
                eventOptionsByMeet={eventOptionsByMeet}
                open={submitOpen}
                onOpenChange={setSubmitOpen}
            />
            <TeamEntryDialog
                athleteOptions={athleteOptions}
                eventOptionsByMeet={eventOptionsByMeet}
                open={teamOpen}
                onOpenChange={setTeamOpen}
            />
        </>
    );
}

Entries.layout = {
    breadcrumbs: [
        {
            title: 'Entries',
            href: index(),
        },
    ],
};
