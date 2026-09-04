import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Award, FileUp, Plus, Printer, Send, Trash2 } from 'lucide-react';
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
    match_id: number | null;
    result_scope: 'match' | 'event';
    meet: string;
    event: string;
    status: string;
    status_label: string;
    encoded_by: string | null;
    encoded_at: string;
    validated_by: string | null;
    validated_at: string | null;
    version: number;
    reference: string;
    can_form: boolean;
    can_review: boolean;
    can_request_cancellation: boolean;
    cancellation_request: {
        reason: string;
        requested_by: string | null;
        requested_at: string;
    } | null;
    can_officialize: boolean;
    form_generated: boolean;
    tm_confirmed: boolean;
    can_tm_confirm: boolean;
    signed_form: { id: number; name: string } | null;
    /** Superset of the page-level `canManage` prop — also true for a
     * Tournament Manager on their own sport's result (Phase 13). Gates the
     * per-row Validate/Delete/Correct actions instead of the page-level
     * prop, since a TM shares this list with every other sport's already-
     * visible validated results. */
    can_manage: boolean;
    placements: Placement[];
};

type Option = { id: number; label: string };

type EventOption = Option & { meet_id: number };
type ScheduleOption = Option & { meet_id: number; event_id: number };

type EntryOption = Option & { meet_id: number; event_id: number };
type CompetitionOption = Option & {
    meet_id: number;
    event_id: number;
    context: string;
    entries: Option[];
};

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
    scheduleOptions: ScheduleOption[];
    activeMeets: Option[];
    encodedEventKeys: string[];
    entryOptions: EntryOption[];
    competitionOptions: CompetitionOption[];
    /** Admin/Organizer only — kept for parity with other registry pages'
     * props even though this page's own UI now reads the per-row
     * `Result.can_manage` instead (a Tournament Manager's validate/correct/
     * delete access is scoped per result, not page-wide). */
    canManage: boolean;
    /** Superset of `canManage` — also true for a Technical Official
     * viewing/encoding results for their own assigned sport (Phase 16).
     * Governs the encode form and the "Edit" action. Tournament Manager
     * does not gain this — encoding stays a Technical Official job. */
    canEncode: boolean;
};

function EncodeDialog({
    result,
    entryOptions,
    competitionOptions,
    activeMeets,
    eventOptions,
    scheduleOptions,
    open,
    onOpenChange,
}: {
    result: Result | null;
    entryOptions: EntryOption[];
    competitionOptions: CompetitionOption[];
    activeMeets: Option[];
    eventOptions: EventOption[];
    scheduleOptions: ScheduleOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [scope, setScope] = useState<'match' | 'event'>(
        result?.result_scope ?? 'match',
    );
    const { data, setData, post, put, processing, errors, reset, transform } =
        useForm({
            meet_id: result ? String(result.meet_id) : '',
            event_id: result ? String(result.event_id) : '',
            event_schedule_id: '',
            match_id: result?.match_id ? String(result.match_id) : '',
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

    const competition = competitionOptions.find(
        (option) => String(option.id) === data.match_id,
    );
    const availableEntries =
        result || scope === 'event'
            ? entryOptions.filter(
                  (option) =>
                      String(option.meet_id) === data.meet_id &&
                      String(option.event_id) === data.event_id,
              )
            : (competition?.entries ?? []);

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
                        <div className="space-y-2">
                            <div className="space-y-2">
                                <Label>Result type</Label>
                                <Select
                                    value={scope}
                                    onValueChange={(
                                        value: 'match' | 'event',
                                    ) => {
                                        setScope(value);
                                        setData('match_id', '');
                                    }}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="match">
                                            Match result — scheduled or non-scheduled
                                        </SelectItem>
                                        <SelectItem value="event">
                                            Final Sports Event result
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            {scope === 'event' && (
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label>Meet</Label>
                                        <Select
                                            value={data.meet_id}
                                            onValueChange={(value) => {
                                                setData('meet_id', value);
                                                setData('event_id', '');
                                                setData('event_schedule_id', '');
                                            }}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select meet" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {activeMeets.map((option) => (
                                                    <SelectItem
                                                        key={option.id}
                                                        value={String(
                                                            option.id,
                                                        )}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.meet_id} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Sports Event</Label>
                                        <Select
                                            value={data.event_id}
                                            onValueChange={(value) => {
                                                setData('event_id', value);
                                                setData('event_schedule_id', '');
                                            }}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select Sports Event" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {eventOptions
                                                    .filter(
                                                        (option) =>
                                                            String(
                                                                option.meet_id,
                                                            ) === data.meet_id,
                                                    )
                                                    .map((option) => (
                                                        <SelectItem
                                                            key={option.id}
                                                            value={String(
                                                                option.id,
                                                            )}
                                                        >
                                                            {option.label}
                                                        </SelectItem>
                                                    ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.event_id} />
                                    </div>
                                    <div className="space-y-2 sm:col-span-2">
                                        <Label>Competition Schedule</Label>
                                        <Select
                                            value={data.event_schedule_id}
                                            onValueChange={(value) =>
                                                setData('event_schedule_id', value)
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select the actual scheduled competition" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {scheduleOptions
                                                    .filter(
                                                        (option) =>
                                                            String(option.meet_id) === data.meet_id &&
                                                            String(option.event_id) === data.event_id,
                                                    )
                                                    .map((option) => (
                                                        <SelectItem key={option.id} value={String(option.id)}>
                                                            {option.label}
                                                        </SelectItem>
                                                    ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.event_schedule_id} />
                                    </div>
                                </div>
                            )}
                            {scope === 'match' && (
                                <div className="space-y-2">
                                    <Label htmlFor="result-match">
                                        Completed match
                                    </Label>
                                    <Select
                                        value={data.match_id}
                                        onValueChange={(value) => {
                                            const selected =
                                                competitionOptions.find(
                                                    (option) =>
                                                        String(option.id) ===
                                                        value,
                                            );
                                            setData('match_id', value);

                                            if (selected) {
                                                setData(
                                                    'meet_id',
                                                    String(selected.meet_id),
                                                );
                                                setData(
                                                    'event_id',
                                                    String(selected.event_id),
                                                );
                                            }

                                            setData('placements', [
                                                {
                                                    entry_id: '',
                                                    rank: '1',
                                                    mark: '',
                                                    is_tie: false,
                                                },
                                            ]);
                                        }}
                                    >
                                        <SelectTrigger id="result-match">
                                            <SelectValue placeholder="Select a completed competition" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {competitionOptions.map(
                                                (option) => (
                                                    <SelectItem
                                                        key={option.id}
                                                        value={String(
                                                            option.id,
                                                        )}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.match_id} />
                                    {competition && (
                                        <p className="text-sm text-muted-foreground">
                                            {competition.context}
                                        </p>
                                    )}
                                </div>
                            )}
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
                                        disabled={
                                            !result &&
                                            scope === 'match' &&
                                            !data.match_id
                                        }
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
    eventOptionsByMeet,
    scheduleOptions,
    activeMeets,
    entryOptions,
    competitionOptions,
    canEncode,
}: Props) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Result | null>(null);
    const [correcting, setCorrecting] = useState<Result | null>(null);
    const isTournamentScoped =
        usePage().props.auth.user?.is_tournament_scoped ?? false;

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (result: Result) => {
        setEditing(result);
        setFormOpen(true);
    };

    const uploadSignedForm = (result: Result, file: File) => {
        router.post(
            `/results/${result.id}/attachments`,
            { file },
            { forceFormData: true, preserveScroll: true },
        );
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
                        canEncode
                            ? 'Encode standings, then validate them to make them official.'
                            : 'Validated results per meet event.'
                    }
                    actions={
                        canEncode &&
                        activeMeets.length > 0 && (
                            <Button onClick={openCreate}>
                                <Plus />
                                Encode result
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
                )}

                {results.data.length === 0 ? (
                    <EmptyState
                        icon={Award}
                        title="No results found"
                        description={
                            canEncode
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
                                        {result.tm_confirmed ? (
                                            <Badge variant="secondary">
                                                TM confirmed
                                            </Badge>
                                        ) : result.match_id ? (
                                            <Badge variant="outline">
                                                Awaiting TM confirmation
                                            </Badge>
                                        ) : null}
                                        {result.cancellation_request && (
                                            <div className="w-full rounded-md border border-destructive/40 bg-destructive/5 p-3 text-sm">
                                                <div className="font-medium text-destructive">
                                                    Cancellation requested
                                                </div>
                                                <div className="mt-1">
                                                    {result.cancellation_request.reason}
                                                </div>
                                                <div className="mt-1 text-xs text-muted-foreground">
                                                    Requested by{' '}
                                                    {result.cancellation_request.requested_by ??
                                                        'ICT'}{' '}
                                                    ·{' '}
                                                    {result.cancellation_request.requested_at}
                                                </div>
                                            </div>
                                        )}
                                        {result.can_tm_confirm &&
                                            !result.tm_confirmed && (
                                                <Button
                                                    size="sm"
                                                    onClick={() =>
                                                        router.post(
                                                            `/results/${result.id}/tm-confirmation`,
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    Confirm result
                                                </Button>
                                            )}
                                        {result.can_form &&
                                            (result.match_id === null ||
                                                result.tm_confirmed) &&
                                            !['official'].includes(
                                                result.status,
                                            ) && (
                                                <>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <a
                                                            href={`/results/${result.id}/form`}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                        >
                                                            <Printer />
                                                            Result Form
                                                        </a>
                                                    </Button>
                                                    <label className="inline-flex h-8 cursor-pointer items-center gap-2 rounded-md border px-3 text-sm font-medium hover:bg-accent">
                                                        <FileUp className="size-4" />
                                                        Upload signed form
                                                        <input
                                                            className="sr-only"
                                                            type="file"
                                                            accept=".pdf,.jpg,.jpeg,.png"
                                                            onChange={(
                                                                event,
                                                            ) => {
                                                                const file =
                                                                    event.target
                                                                        .files?.[0];

                                                                if (file) {
                                                                    uploadSignedForm(
                                                                        result,
                                                                        file,
                                                                    );
                                                                }

                                                                event.target.value =
                                                                    '';
                                                            }}
                                                        />
                                                    </label>
                                                    <Button
                                                        size="sm"
                                                        disabled={
                                                            !result.signed_form ||
                                                            ![
                                                                'encoded',
                                                                'returned',
                                                                'reopened',
                                                            ].includes(
                                                                result.status,
                                                            )
                                                        }
                                                        onClick={() =>
                                                            router.post(
                                                                `/results/${result.id}/submit`,
                                                                {},
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        <Send />
                                                        Submit
                                                    </Button>
                                                </>
                                            )}
                                        {result.signed_form && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <a
                                                    href={`/results/${result.id}/attachments/${result.signed_form.id}`}
                                                >
                                                    Signed form
                                                </a>
                                            </Button>
                                        )}
                                        {result.can_review &&
                                            result.status === 'submitted' && (
                                                <>
                                                    <Button
                                                        size="sm"
                                                        onClick={() =>
                                                            router.post(
                                                                `/results/${result.id}/event-secretariat-validation`,
                                                                {},
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        Validate
                                                    </Button>
                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                        onClick={() => {
                                                            const reason =
                                                                window.prompt(
                                                                    'Reason for returning this result',
                                                                );

                                                            if (reason) {
                                                                router.post(
                                                                    `/results/${result.id}/return`,
                                                                    { reason },
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                );
                                                            }
                                                        }}
                                                    >
                                                        Return
                                                    </Button>
                                                </>
                                            )}
                                        {result.can_request_cancellation && (
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                onClick={() => {
                                                    const reason = window.prompt(
                                                        'Describe the problem requiring cancellation or correction',
                                                    )?.trim();

                                                    if (reason) {
                                                        router.post(
                                                            `/results/${result.id}/request-cancellation`,
                                                            { reason },
                                                            { preserveScroll: true },
                                                        );
                                                    }
                                                }}
                                            >
                                                Request cancellation
                                            </Button>
                                        )}
                                        {result.can_officialize && (
                                            <ConfirmDialog
                                                trigger={
                                                    <Button size="sm">
                                                        Mark as official
                                                    </Button>
                                                }
                                                title="Mark this Sports Event Result as OFFICIAL?"
                                                description="This will make the result authoritative and may update the official medal tally."
                                                confirmLabel="Mark as official"
                                                onConfirm={() =>
                                                    router.post(
                                                        `/results/${result.id}/official`,
                                                        {},
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            />
                                        )}
                                        {canEncode &&
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
                                                    {result.can_manage && (
                                                        <>
                                                            <ConfirmDialog
                                                                trigger={
                                                                    <Button size="sm">
                                                                        Validate
                                                                    </Button>
                                                                }
                                                                title="Validate result?"
                                                                description="Validation forwards this unofficial standing for Top Management officialization."
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
                                                        </>
                                                    )}
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
                                        {result.can_manage &&
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
                                        {result.can_manage && (
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
                                                description="Admin-only action: this permanently removes the result, its placements, attachments, and all medal-tally awards derived from it."
                                                confirmLabel="Delete result and tally"
                                                destructive
                                                onConfirm={() =>
                                                    router.delete(
                                                        destroy(result.id).url,
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            />
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

            {canEncode && (
                <EncodeDialog
                    key={editing?.id ?? 'create'}
                    result={editing}
                    entryOptions={entryOptions}
                    competitionOptions={competitionOptions}
                    activeMeets={activeMeets}
                    eventOptions={eventOptionsByMeet}
                    scheduleOptions={scheduleOptions}
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
