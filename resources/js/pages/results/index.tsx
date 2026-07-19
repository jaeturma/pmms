import { Head, Link, router, useForm } from '@inertiajs/react';
import { Award, Plus, Printer, Trash2 } from 'lucide-react';
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
import { resultSheet } from '@/routes/reports';
import {
    correct,
    destroy,
    index,
    store,
    update,
    validate,
} from '@/routes/results';

type Placement = {
    entry_id: number;
    rank: number;
    athlete: string;
    school: string;
    mark: string | null;
    is_tie: boolean;
};

type Result = {
    id: number;
    meet_id: number;
    event_id: number;
    meet: string;
    event: string;
    status: string;
    status_label: string;
    encoded_by: string | null;
    encoded_at: string;
    validated_by: string | null;
    validated_at: string | null;
    placements: Placement[];
};

type Option = { id: number; label: string };

type EventOption = Option & { meet_id: number };

type EntryOption = Option & { meet_id: number; event_id: number };

type PlacementRow = {
    entry_id: string;
    rank: string;
    mark: string;
    is_tie: boolean;
};

type Props = {
    results: Paginated<Result>;
    filters: { meet_id: number | null; event_id: number | null };
    meetOptions: Option[];
    eventOptionsByMeet: EventOption[];
    activeMeets: Option[];
    encodedEventKeys: string[];
    entryOptions: EntryOption[];
    canManage: boolean;
};

function EncodeDialog({
    result,
    activeMeets,
    eventOptionsByMeet,
    encodedEventKeys,
    entryOptions,
    open,
    onOpenChange,
}: {
    result: Result | null;
    activeMeets: Option[];
    eventOptionsByMeet: EventOption[];
    encodedEventKeys: string[];
    entryOptions: EntryOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset, transform } =
        useForm({
            meet_id: result ? String(result.meet_id) : '',
            event_id: result ? String(result.event_id) : '',
            placements: (result
                ? result.placements.map((placement) => ({
                      entry_id: String(placement.entry_id),
                      rank: String(placement.rank),
                      mark: placement.mark ?? '',
                      is_tie: placement.is_tie,
                  }))
                : [
                      { entry_id: '', rank: '1', mark: '', is_tie: false },
                  ]) as PlacementRow[],
        });

    transform((current) => ({
        ...current,
        placements: current.placements.map((row) => ({
            entry_id: row.entry_id,
            rank: row.rank,
            mark: row.mark === '' ? null : row.mark,
            is_tie: row.is_tie,
        })),
    }));

    const eventOptions = eventOptionsByMeet.filter(
        (option) =>
            String(option.meet_id) === data.meet_id &&
            (result !== null ||
                !encodedEventKeys.includes(`${option.meet_id}-${option.id}`)),
    );

    const availableEntries = entryOptions.filter(
        (option) =>
            String(option.meet_id) === data.meet_id &&
            String(option.event_id) === data.event_id,
    );

    const setRow = (i: number, patch: Partial<PlacementRow>) => {
        setData(
            'placements',
            data.placements.map((row, j) =>
                j === i ? { ...row, ...patch } : row,
            ),
        );
    };

    const addRow = () => {
        setData('placements', [
            ...data.placements,
            {
                entry_id: '',
                rank: String(data.placements.length + 1),
                mark: '',
                is_tie: false,
            },
        ]);
    };

    const removeRow = (i: number) => {
        setData(
            'placements',
            data.placements.filter((_, j) => j !== i),
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

        if (result) {
            put(update(result.id).url, options);
        } else {
            post(store().url, options);
        }
    };

    const placementError = Object.entries(errors).find(([key]) =>
        key.startsWith('placements'),
    )?.[1];

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {result
                            ? `Edit result — ${result.event}`
                            : 'Encode result'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    {!result && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="result-meet">Meet</Label>
                                <Select
                                    value={data.meet_id}
                                    onValueChange={(value) => {
                                        setData('meet_id', value);
                                        setData('event_id', '');
                                    }}
                                >
                                    <SelectTrigger id="result-meet">
                                        <SelectValue placeholder="Active meets" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {activeMeets.map((option) => (
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
                                <Label htmlFor="result-event">Event</Label>
                                <Select
                                    value={data.event_id}
                                    onValueChange={(value) =>
                                        setData('event_id', value)
                                    }
                                    disabled={!data.meet_id}
                                >
                                    <SelectTrigger id="result-event">
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
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label>Placements</Label>
                        <div className="space-y-2">
                            {data.placements.map((row, i) => (
                                <div
                                    key={i}
                                    className="flex flex-wrap items-center gap-2"
                                >
                                    <Input
                                        type="number"
                                        min={1}
                                        className="w-16"
                                        aria-label={`Rank ${i + 1}`}
                                        value={row.rank}
                                        onChange={(e) =>
                                            setRow(i, { rank: e.target.value })
                                        }
                                    />
                                    <Select
                                        value={row.entry_id}
                                        onValueChange={(value) =>
                                            setRow(i, { entry_id: value })
                                        }
                                        disabled={!data.event_id}
                                    >
                                        <SelectTrigger
                                            className="w-64"
                                            aria-label={`Entry for rank ${i + 1}`}
                                        >
                                            <SelectValue placeholder="Select entry" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableEntries.map((option) => (
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
                                        className="w-32"
                                        placeholder="Score / time"
                                        aria-label={`Mark for rank ${i + 1}`}
                                        value={row.mark}
                                        onChange={(e) =>
                                            setRow(i, { mark: e.target.value })
                                        }
                                    />
                                    <label className="flex items-center gap-1.5 text-sm">
                                        <Checkbox
                                            checked={row.is_tie}
                                            onCheckedChange={(checked) =>
                                                setRow(i, {
                                                    is_tie: checked === true,
                                                })
                                            }
                                        />
                                        Tie
                                    </label>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`Remove placement ${i + 1}`}
                                        onClick={() => removeRow(i)}
                                        disabled={data.placements.length === 1}
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            ))}
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={addRow}
                        >
                            <Plus />
                            Add placement
                        </Button>
                        <InputError message={placementError} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {result ? 'Save changes' : 'Encode result'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function CorrectDialog({
    result,
    open,
    onOpenChange,
}: {
    result: Result;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, patch, processing, errors, reset } = useForm({
        reason: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        patch(correct(result.id).url, {
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
                    <DialogTitle>Correct result — {result.event}</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <p className="text-sm text-muted-foreground">
                        The current standing is preserved in the audit trail,
                        and the result reopens for re-encoding and a fresh
                        validation.
                    </p>
                    <div className="space-y-2">
                        <Label htmlFor="correct-reason">
                            Reason for correction
                        </Label>
                        <Input
                            id="correct-reason"
                            value={data.reason}
                            onChange={(e) => setData('reason', e.target.value)}
                            placeholder="e.g. protest upheld, encoding error…"
                            autoFocus
                        />
                        <InputError message={errors.reason} />
                    </div>
                    <DialogFooter>
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={processing}
                        >
                            Reopen result
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Results({
    results,
    filters,
    meetOptions,
    eventOptionsByMeet,
    activeMeets,
    encodedEventKeys,
    entryOptions,
    canManage,
}: Props) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Result | null>(null);
    const [correcting, setCorrecting] = useState<Result | null>(null);

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (result: Result) => {
        setEditing(result);
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
            <Head title="Results" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Results"
                    description={
                        canManage
                            ? 'Encode standings, then validate them to make them official.'
                            : 'Validated results per meet event.'
                    }
                    actions={
                        canManage &&
                        activeMeets.length > 0 && (
                            <Button onClick={openCreate}>
                                <Plus />
                                Encode result
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

                {results.data.length === 0 ? (
                    <EmptyState
                        icon={Award}
                        title="No results found"
                        description={
                            canManage
                                ? 'Encode an event standing to get started.'
                                : 'Validated event results will appear here.'
                        }
                    />
                ) : (
                    <div className="space-y-4">
                        {results.data.map((result) => (
                            <section
                                key={result.id}
                                className="rounded-xl border"
                            >
                                <div className="flex flex-wrap items-center justify-between gap-3 border-b p-4">
                                    <div>
                                        <p className="font-medium">
                                            {result.event}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {result.meet} · Encoded by{' '}
                                            {result.encoded_by ?? '—'}{' '}
                                            {result.encoded_at}
                                            {result.validated_by && (
                                                <>
                                                    {' '}
                                                    · Validated by{' '}
                                                    {result.validated_by}{' '}
                                                    {result.validated_at}
                                                </>
                                            )}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Badge
                                            variant={
                                                result.status === 'validated'
                                                    ? 'secondary'
                                                    : 'default'
                                            }
                                        >
                                            {result.status_label}
                                        </Badge>
                                        {canManage &&
                                            result.status === 'encoded' && (
                                                <>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            openEdit(result)
                                                        }
                                                    >
                                                        Edit
                                                    </Button>
                                                    <ConfirmDialog
                                                        trigger={
                                                            <Button size="sm">
                                                                Validate
                                                            </Button>
                                                        }
                                                        title="Validate result?"
                                                        description="Validation makes this standing official and locks it. Corrections afterwards require a reason and are audited."
                                                        confirmLabel="Validate"
                                                        onConfirm={() =>
                                                            router.patch(
                                                                validate(
                                                                    result.id,
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
                                                        title="Delete result?"
                                                        description="This removes the encoded standing. Validated results cannot be deleted."
                                                        confirmLabel="Delete"
                                                        destructive
                                                        onConfirm={() =>
                                                            router.delete(
                                                                destroy(
                                                                    result.id,
                                                                ).url,
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    />
                                                </>
                                            )}
                                        {result.status === 'validated' && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={resultSheet(
                                                        result.id,
                                                    )}
                                                >
                                                    <Printer />
                                                    Sheet
                                                </Link>
                                            </Button>
                                        )}
                                        {canManage &&
                                            result.status === 'validated' && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        setCorrecting(result)
                                                    }
                                                >
                                                    Correct
                                                </Button>
                                            )}
                                    </div>
                                </div>
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-16">
                                                    Rank
                                                </TableHead>
                                                <TableHead>Athlete</TableHead>
                                                <TableHead>School</TableHead>
                                                <TableHead>
                                                    Score / mark
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {result.placements.map(
                                                (placement) => (
                                                    <TableRow
                                                        key={placement.entry_id}
                                                    >
                                                        <TableCell className="font-medium">
                                                            {placement.rank}
                                                            {placement.is_tie &&
                                                                ' (tie)'}
                                                        </TableCell>
                                                        <TableCell>
                                                            {placement.athlete}
                                                        </TableCell>
                                                        <TableCell>
                                                            {placement.school}
                                                        </TableCell>
                                                        <TableCell>
                                                            {placement.mark ??
                                                                '—'}
                                                        </TableCell>
                                                    </TableRow>
                                                ),
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </section>
                        ))}
                    </div>
                )}

                <PaginationControls
                    page={results}
                    url={index().url}
                    label="results"
                    params={filterParams}
                />
            </div>

            {canManage && (
                <EncodeDialog
                    key={editing?.id ?? 'create'}
                    result={editing}
                    activeMeets={activeMeets}
                    eventOptionsByMeet={eventOptionsByMeet}
                    encodedEventKeys={encodedEventKeys}
                    entryOptions={entryOptions}
                    open={formOpen}
                    onOpenChange={setFormOpen}
                />
            )}

            {correcting && (
                <CorrectDialog
                    key={correcting.id}
                    result={correcting}
                    open={correcting !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setCorrecting(null);
                        }
                    }}
                />
            )}
        </>
    );
}

Results.layout = {
    breadcrumbs: [
        {
            title: 'Results',
            href: index(),
        },
    ],
};
