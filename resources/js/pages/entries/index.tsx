import { Head, router, useForm } from '@inertiajs/react';
import { ListChecks, Plus } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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

type EntryRow = {
    id: number;
    athlete: string;
    event: string;
    school: string;
    meet: string;
    status: string;
    status_label: string;
    eligibility_approved: boolean;
    can_confirm: boolean;
    can_withdraw: boolean;
    can_delete: boolean;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
};

type FilterOption = {
    id: number;
    label: string;
};

type AthleteOption = {
    id: number;
    meet_id: number;
    label: string;
};

type EventOption = {
    id: number;
    meet_id: number;
    label: string;
};

type Props = {
    entries: Paginated<EntryRow>;
    filters: { event_id: number | null; delegation_id: number | null };
    eventFilterOptions: FilterOption[];
    delegationFilterOptions: FilterOption[];
    athleteOptions: AthleteOption[];
    eventOptionsByMeet: EventOption[];
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
        event_id: '',
    });

    const selectedAthlete = athleteOptions.find(
        (athlete) => String(athlete.id) === data.athlete_id,
    );

    const availableEvents = selectedAthlete
        ? eventOptionsByMeet.filter(
              (event) => event.meet_id === selectedAthlete.meet_id,
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
                    <DialogTitle>Submit entry</DialogTitle>
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
                                    setData('event_id', '');
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
                            <Label htmlFor="entry-event">Event</Label>
                            <Select
                                value={data.event_id}
                                onValueChange={(value) =>
                                    setData('event_id', value)
                                }
                                disabled={!selectedAthlete}
                            >
                                <SelectTrigger id="entry-event">
                                    <SelectValue
                                        placeholder={
                                            selectedAthlete
                                                ? 'Select an event'
                                                : 'Select an athlete first'
                                        }
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {availableEvents.map((event) => (
                                        <SelectItem
                                            key={event.id}
                                            value={String(event.id)}
                                        >
                                            {event.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.event_id} />
                        </div>
                        <DialogFooter>
                            <Button type="submit" disabled={processing}>
                                Submit entry
                            </Button>
                        </DialogFooter>
                    </form>
                )}
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
}: Props) {
    const [submitOpen, setSubmitOpen] = useState(false);

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

        router.get(index().url, params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const goToPage = (page: number) => {
        router.get(
            index().url,
            {
                ...(filters.event_id
                    ? { event_id: String(filters.event_id) }
                    : {}),
                ...(filters.delegation_id
                    ? { delegation_id: String(filters.delegation_id) }
                    : {}),
                page,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Entries" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Entries"
                    description="Event entries per delegation and event."
                    actions={
                        athleteOptions.length > 0 && (
                            <Button onClick={() => setSubmitOpen(true)}>
                                <Plus />
                                Submit entry
                            </Button>
                        )
                    }
                />

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
                                        <TableHead>School</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {entries.data.map((entry) => (
                                        <TableRow key={entry.id}>
                                            <TableCell className="font-medium">
                                                <span className="flex items-center gap-2">
                                                    {entry.athlete}
                                                    {!entry.eligibility_approved && (
                                                        <Badge variant="outline">
                                                            Eligibility pending
                                                        </Badge>
                                                    )}
                                                </span>
                                            </TableCell>
                                            <TableCell>{entry.event}</TableCell>
                                            <TableCell>
                                                {entry.school}
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

                        {entries.last_page > 1 && (
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Page {entries.current_page} of{' '}
                                    {entries.last_page} ({entries.total}{' '}
                                    entries)
                                </p>
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={entries.current_page === 1}
                                        onClick={() =>
                                            goToPage(entries.current_page - 1)
                                        }
                                    >
                                        Previous
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={
                                            entries.current_page ===
                                            entries.last_page
                                        }
                                        onClick={() =>
                                            goToPage(entries.current_page + 1)
                                        }
                                    >
                                        Next
                                    </Button>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>

            <SubmitEntryDialog
                athleteOptions={athleteOptions}
                eventOptionsByMeet={eventOptionsByMeet}
                open={submitOpen}
                onOpenChange={setSubmitOpen}
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
