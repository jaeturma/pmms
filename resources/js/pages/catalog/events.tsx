import { Head, router, useForm } from '@inertiajs/react';
import { Medal, Plus, Trash2 } from 'lucide-react';
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
    medal_config: MedalConfig;
    sport: { id: number; name: string };
    venues: EventVenue[];
};

type MedalConfig = {
    awards_medals: boolean;
    award_type: 'INDIVIDUAL' | 'PAIR' | 'TEAM' | 'RELAY' | 'GROUP';
    physical_quantity_mode: 'FIXED' | 'TEAM_MEMBER_COUNT';
    gold_physical_quantity: number | null;
    silver_physical_quantity: number | null;
    bronze_physical_quantity: number | null;
    gold_tally_quantity: number | null;
    silver_tally_quantity: number | null;
    bronze_tally_quantity: number | null;
    notes: string | null;
    status: string;
};

type EventVenue = {
    venue_id: string;
    venue_name?: string;
    playing_area_type: 'venue' | 'court' | 'table';
    playing_area_count: string;
    coordinator_ids: string[];
    coordinator_names?: string[];
};

type SportOption = {
    id: number;
    name: string;
};

type Props = {
    events: Paginated<MeetEvent>;
    filters: { search: string; sport_id: number | null };
    sports: SportOption[];
    venues: { id: number; name: string }[];
    people: { id: number; full_name: string }[];
    canManage: boolean;
    canArchive: boolean;
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
    venues,
    people,
    open,
    onOpenChange,
}: {
    event: MeetEvent | null;
    sports: SportOption[];
    venues: { id: number; name: string }[];
    people: { id: number; full_name: string }[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const existingConfig = event?.medal_config;
    const [tallyFollowsPhysical, setTallyFollowsPhysical] = useState(
        existingConfig
            ? existingConfig.gold_physical_quantity === existingConfig.gold_tally_quantity &&
                  existingConfig.silver_physical_quantity === existingConfig.silver_tally_quantity &&
                  existingConfig.bronze_physical_quantity === existingConfig.bronze_tally_quantity
            : true,
    );
    const { data, setData, post, put, processing, errors, reset } = useForm({
        sport_id: event ? String(event.sport_id) : '',
        name: event?.name ?? '',
        gender: event?.gender ?? '',
        age_division: event?.age_division ?? '',
        is_team_event: event?.is_team_event ?? false,
        max_entries_per_delegation: event
            ? String(event.max_entries_per_delegation)
            : '1',
        medal_config: {
            awards_medals: existingConfig?.awards_medals ?? true,
            award_type: existingConfig?.award_type ?? 'INDIVIDUAL',
            physical_quantity_mode:
                existingConfig?.physical_quantity_mode ?? 'FIXED',
            gold_physical_quantity: String(
                existingConfig?.gold_physical_quantity ?? 1,
            ),
            silver_physical_quantity: String(
                existingConfig?.silver_physical_quantity ?? 1,
            ),
            bronze_physical_quantity: String(
                existingConfig?.bronze_physical_quantity ?? 1,
            ),
            gold_tally_quantity: String(
                existingConfig?.gold_tally_quantity ?? 1,
            ),
            silver_tally_quantity: String(
                existingConfig?.silver_tally_quantity ?? 1,
            ),
            bronze_tally_quantity: String(
                existingConfig?.bronze_tally_quantity ?? 1,
            ),
            notes: existingConfig?.notes ?? '',
        },
        venues:
            event?.venues.map((venue) => ({
                ...venue,
                venue_id: String(venue.venue_id),
                playing_area_count: String(venue.playing_area_count),
                coordinator_ids: venue.coordinator_ids.map(String),
            })) ?? [],
    });

    const updateVenue = (index: number, values: Partial<EventVenue>) => {
        setData(
            'venues',
            data.venues.map((venue, venueIndex) =>
                venueIndex === index ? { ...venue, ...values } : venue,
            ),
        );
    };

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
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
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
                    <div className="space-y-4 border-t pt-4">
                        <div>
                            <Label className="text-base">Medal configuration</Label>
                            <p className="text-sm text-muted-foreground">
                                Physical quantity is the number of actual medal pieces awarded. Official tally quantity is the number credited to the Delegation&apos;s official Gold/Silver/Bronze totals.
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="awards-medals"
                                checked={data.medal_config.awards_medals}
                                onCheckedChange={(checked) =>
                                    setData('medal_config', {
                                        ...data.medal_config,
                                        awards_medals: checked === true,
                                    })
                                }
                            />
                            <Label htmlFor="awards-medals">Awards medals</Label>
                        </div>
                        {data.medal_config.awards_medals && (
                            <>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label>Award type</Label>
                                        <Select
                                            value={data.medal_config.award_type}
                                            onValueChange={(value) =>
                                                setData('medal_config', {
                                                    ...data.medal_config,
                                                    award_type: value as MedalConfig['award_type'],
                                                })
                                            }
                                        >
                                            <SelectTrigger><SelectValue /></SelectTrigger>
                                            <SelectContent>
                                                {['INDIVIDUAL', 'PAIR', 'TEAM', 'RELAY', 'GROUP'].map((type) => <SelectItem key={type} value={type}>{type}</SelectItem>)}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Physical quantity mode</Label>
                                        <Select
                                            value={data.medal_config.physical_quantity_mode}
                                            onValueChange={(value) => setData('medal_config', { ...data.medal_config, physical_quantity_mode: value as MedalConfig['physical_quantity_mode'] })}
                                        >
                                            <SelectTrigger><SelectValue /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="FIXED">Fixed</SelectItem>
                                                <SelectItem value="TEAM_MEMBER_COUNT">Team member count</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    {(['physical', 'tally'] as const).map((kind) => (
                                        <div key={kind} className="space-y-3 rounded-lg border p-3">
                                            <Label>{kind === 'physical' ? 'Physical medal pieces' : 'Official medal tally'}</Label>
                                            <div className="grid grid-cols-3 gap-2">
                                                {(['gold', 'silver', 'bronze'] as const).map((medal) => {
                                                    const key = `${medal}_${kind}_quantity` as keyof typeof data.medal_config;
                                                    return <div key={medal} className="space-y-1"><Label className="capitalize">{medal}</Label><Input type="number" min={0} value={String(data.medal_config[key])} disabled={kind === 'tally' && tallyFollowsPhysical} onChange={(e) => {
                                                        const next = { ...data.medal_config, [key]: e.target.value };
                                                        if (kind === 'physical' && tallyFollowsPhysical) {
                                                            const tallyKey = `${medal}_tally_quantity` as keyof typeof data.medal_config;
                                                            next[tallyKey] = e.target.value as never;
                                                        }
                                                        setData('medal_config', next);
                                                    }} /></div>;
                                                })}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="tally-follows"
                                        checked={tallyFollowsPhysical}
                                        onCheckedChange={(checked) => {
                                            const follows = checked === true;
                                            setTallyFollowsPhysical(follows);
                                            if (follows) setData('medal_config', {
                                                ...data.medal_config,
                                                gold_tally_quantity: data.medal_config.gold_physical_quantity,
                                                silver_tally_quantity: data.medal_config.silver_physical_quantity,
                                                bronze_tally_quantity: data.medal_config.bronze_physical_quantity,
                                            });
                                        }}
                                    />
                                    <Label htmlFor="tally-follows">Tally follows physical quantity</Label>
                                </div>
                                <InputError message={(errors as Record<string, string>)['medal_config.gold_physical_quantity'] ?? (errors as Record<string, string>)['medal_config.gold_tally_quantity']} />
                            </>
                        )}
                    </div>
                    <div className="space-y-3 border-t pt-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <Label>Playing venues</Label>
                                <p className="text-sm text-muted-foreground">
                                    Add venues, courts, or tables and up to two
                                    game coordinators.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setData('venues', [
                                        ...data.venues,
                                        {
                                            venue_id: '',
                                            playing_area_type: 'venue',
                                            playing_area_count: '1',
                                            coordinator_ids: [],
                                        },
                                    ])
                                }
                            >
                                <Plus /> Add venue
                            </Button>
                        </div>
                        {data.venues.map((assignment, index) => (
                            <div
                                key={index}
                                className="space-y-3 rounded-lg border p-3"
                            >
                                <div className="grid gap-3 sm:grid-cols-[1fr_140px_90px_auto]">
                                    <Select
                                        value={String(assignment.venue_id)}
                                        onValueChange={(value) =>
                                            updateVenue(index, {
                                                venue_id: value,
                                            })
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select venue" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {venues.map((venue) => (
                                                <SelectItem
                                                    key={venue.id}
                                                    value={String(venue.id)}
                                                >
                                                    {venue.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <Select
                                        value={assignment.playing_area_type}
                                        onValueChange={(value) =>
                                            updateVenue(index, {
                                                playing_area_type:
                                                    value as EventVenue['playing_area_type'],
                                            })
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="venue">
                                                Venue
                                            </SelectItem>
                                            <SelectItem value="court">
                                                Court
                                            </SelectItem>
                                            <SelectItem value="table">
                                                Table
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Input
                                        type="number"
                                        min={1}
                                        max={100}
                                        aria-label="Number of playing areas"
                                        value={String(
                                            assignment.playing_area_count,
                                        )}
                                        onChange={(e) =>
                                            updateVenue(index, {
                                                playing_area_count:
                                                    e.target.value,
                                            })
                                        }
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Remove venue"
                                        onClick={() =>
                                            setData(
                                                'venues',
                                                data.venues.filter(
                                                    (_, venueIndex) =>
                                                        venueIndex !== index,
                                                ),
                                            )
                                        }
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                                <div className="grid gap-3 sm:grid-cols-2">
                                    {[0, 1].map((coordinatorIndex) => (
                                        <select
                                            key={coordinatorIndex}
                                            className="h-9 rounded-md border bg-background px-3 text-sm"
                                            aria-label={`Game coordinator ${coordinatorIndex + 1}`}
                                            value={String(
                                                assignment.coordinator_ids[
                                                    coordinatorIndex
                                                ] ?? '',
                                            )}
                                            onChange={(e) => {
                                                const ids = [
                                                    ...assignment.coordinator_ids,
                                                ];

                                                if (e.target.value) {
                                                    ids[coordinatorIndex] =
                                                        e.target.value;
                                                } else {
                                                    ids.splice(
                                                        coordinatorIndex,
                                                        1,
                                                    );
                                                }

                                                updateVenue(index, {
                                                    coordinator_ids:
                                                        ids.filter(Boolean),
                                                });
                                            }}
                                        >
                                            <option value="">
                                                Coordinator{' '}
                                                {coordinatorIndex + 1}{' '}
                                                (optional)
                                            </option>
                                            {people.map((person) => (
                                                <option
                                                    key={person.id}
                                                    value={person.id}
                                                >
                                                    {person.full_name}
                                                </option>
                                            ))}
                                        </select>
                                    ))}
                                </div>
                                <InputError
                                    message={
                                        (errors as Record<string, string>)[
                                            `venues.${index}.venue_id`
                                        ] ??
                                        (errors as Record<string, string>)[
                                            `venues.${index}.coordinator_ids`
                                        ]
                                    }
                                />
                            </div>
                        ))}
                    </div>
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

export default function Events({
    events,
    filters,
    sports,
    venues,
    people,
    canManage,
    canArchive,
}: Props) {
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

                <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_16rem]">
                    <SearchBar
                        initial={filters.search}
                        placeholder="Search events"
                        url={index().url}
                        extraParams={
                            filters.sport_id
                                ? { sport_id: String(filters.sport_id) }
                                : undefined
                        }
                    />
                    <Select
                        value={String(filters.sport_id ?? 'all')}
                        onValueChange={(value) =>
                            router.get(
                                index().url,
                                {
                                    search: filters.search || undefined,
                                    sport_id:
                                        value === 'all' ? undefined : value,
                                },
                                { preserveState: true },
                            )
                        }
                    >
                        <SelectTrigger aria-label="Filter sports events by sport">
                            <SelectValue placeholder="All sports" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All sports</SelectItem>
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
                </div>

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
                                    <TableHead>Physical G/S/B</TableHead>
                                    <TableHead>Official tally G/S/B</TableHead>
                                    <TableHead>Playing areas</TableHead>
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
                                            {event.medal_config.awards_medals
                                                ? `${event.medal_config.gold_physical_quantity ?? '—'} / ${event.medal_config.silver_physical_quantity ?? '—'} / ${event.medal_config.bronze_physical_quantity ?? '—'}`
                                                : 'N/A'}
                                        </TableCell>
                                        <TableCell>
                                            <div>{event.medal_config.awards_medals ? `${event.medal_config.gold_tally_quantity ?? '—'} / ${event.medal_config.silver_tally_quantity ?? '—'} / ${event.medal_config.bronze_tally_quantity ?? '—'}` : 'N/A'}</div>
                                            <Badge variant={event.medal_config.status === 'MEDAL_CONFIGURATION_REQUIRED' ? 'destructive' : 'outline'}>{event.medal_config.status.replaceAll('_', ' ')}</Badge>
                                        </TableCell>
                                        <TableCell className="max-w-64 text-sm">
                                            {event.venues.length === 0
                                                ? 'Not assigned'
                                                : event.venues
                                                      .map(
                                                          (venue) =>
                                                              `${venue.venue_name}: ${venue.playing_area_count} ${venue.playing_area_type}${Number(venue.playing_area_count) === 1 ? '' : 's'}`,
                                                      )
                                                      .join(', ')}
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
                                                    {canArchive && (
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
                                                    )}
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
                    params={{
                        ...(filters.search ? { search: filters.search } : {}),
                        ...(filters.sport_id
                            ? { sport_id: String(filters.sport_id) }
                            : {}),
                    }}
                />
            </div>

            <EventFormDialog
                key={editing?.id ?? 'create'}
                event={editing}
                sports={sports}
                venues={venues}
                people={people}
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
