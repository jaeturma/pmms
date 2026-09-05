import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Award, FileUp, Plus, Printer, Send, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { PaginationControls } from '@/components/pagination-controls';
import type { Paginated } from '@/components/pagination-controls';
import type { Attribution } from '@/components/result-attribution';
import {
    AttributionFields,
    emptyAttribution,
} from '@/components/result-attribution';
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
import { correct, destroy, index, store, update } from '@/routes/results';

type Placement = {
    id: number;
    attribution: Attribution;
    can_attribute: boolean;
    entry_id: number | null;
    team_entry_id: number | null;
    delegation_id: number | null;
    rank: number;
    athlete: string;
    school: string;
    mark: string | null;
    tally_quantity: number | null;
    is_tie: boolean;
};

type Result = {
    result_type: 'medal' | 'versus' | null;
    measurement_type: string | null;
    is_team_event: boolean;
    result_source: string;
    can_reopen: boolean;
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
    encoded_at: string | null;
    submitted_by: string | null;
    submitted_at: string | null;
    validated_by: string | null;
    validated_at: string | null;
    returned_by: string | null;
    returned_at: string | null;
    return_reason: string | null;
    operational_remarks: string | null;
    data_issues: string[];
    can_defer_issues: boolean;
    official_by: string | null;
    official_at: string | null;
    competition_context: string;
    version: number;
    reference: string;
    can_form: boolean;
    can_upload_photo: boolean;
    can_review: boolean;
    can_cancel: boolean;
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
    signed_form: { id: number; name: string; type: string } | null;
    result_photo: { id: number; name: string; url: string } | null;
    /** Superset of the page-level `canManage` prop — also true for a
     * Tournament Manager on their own sport's result (Phase 13). Gates the
     * per-row Validate/Delete/Correct actions instead of the page-level
     * prop, since a TM shares this list with every other sport's already-
     * visible validated results. */
    can_manage: boolean;
    awards_medals: boolean;
    medal_tally: {
        gold: number;
        silver: number;
        bronze: number;
        total: number;
    };
    placements: Placement[];
};

type Option = { id: number; label: string };

type EventOption = Option & { meet_id: number; is_team_event: boolean; default_result_type: 'medal' | 'versus' };
type ScheduleOption = Option & { meet_id: number; event_id: number };

type EntryOption = Option & { meet_id: number; event_id: number };
type CompetitionOption = Option & {
    meet_id: number;
    event_id: number;
    context: string;
    entries: Array<Option & { participant_type: 'entry' | 'team' }>;
};

type PlacementRow = {
    entry_id: string;
    team_entry_id: string;
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
    teamEntryOptions: EntryOption[];
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
    canDirectResult: boolean;
    delegationOptions: Option[];
};

function PlacementAttribution({
    result,
    placement,
}: {
    result: Result;
    placement: Placement;
}) {
    const [editing, setEditing] = useState(false);
    const { data, setData, patch, processing, errors } = useForm<Attribution>(
        placement.attribution,
    );

    return (
        <div className="rounded border p-3">
            <p className="text-sm font-medium">
                {
                    (result.result_type === 'versus' ? ['Winner', 'Loser'] : ['Gold / 1st', 'Silver / 2nd', 'Bronze / 3rd'])[
                        placement.rank - 1
                    ]
                }{' '}
                · {placement.school}
            </p>
            <p className="text-sm">
                {result.is_team_event
                    ? `${placement.attribution.athlete_ids.length} athletes linked · ${placement.attribution.coaches.length} coaches linked`
                    : placement.attribution.athlete_id
                      ? 'Athlete linked: Complete'
                      : 'Athlete linked: Missing'}
            </p>
            {((result.is_team_event &&
                (!placement.attribution.athlete_ids.length ||
                    !placement.attribution.coaches.length)) ||
                (!result.is_team_event &&
                    !placement.attribution.athlete_id)) && (
                <Badge variant="outline">Reporting data incomplete</Badge>
            )}
            {placement.attribution.players?.length ? (
                <p className="text-sm">
                    Players: {placement.attribution.players.join(', ')}
                </p>
            ) : null}
            {placement.attribution.coaches.map((c) => (
                <p className="text-sm" key={c.user_id}>
                    {c.role}: {c.name}
                </p>
            ))}
            {placement.can_attribute && (
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => setEditing(!editing)}
                >
                    Manage reporting attribution
                </Button>
            )}
            {editing && (
                <div className="space-y-2">
                    <AttributionFields
                        eventId={result.event_id}
                        delegationId={placement.delegation_id!}
                        team={result.is_team_event}
                        value={data}
                        onChange={(value) => setData(value)}
                    />
                    {Object.values(errors).map((error, i) => (
                        <p role="alert" key={i}>
                            {error}
                        </p>
                    ))}
                    <Button
                        disabled={processing}
                        onClick={() =>
                            patch(
                                `/results/${result.id}/placements/${placement.id}/attribution`,
                                {
                                    preserveScroll: true,
                                    onSuccess: () => setEditing(false),
                                },
                            )
                        }
                    >
                        Save attribution
                    </Button>
                </div>
            )}
        </div>
    );
}

function DirectResultForm({
    onOpenChange,
    events,
    delegations,
    result,
}: {
    result?: Result | null;
    onOpenChange: (open: boolean) => void;
    events: EventOption[];
    delegations: Option[];
}) {
    const { data, setData, post, processing, errors, reset, transform } =
        useForm({
            result_type: result?.result_type ?? (result?.placements.some((p) => (p.tally_quantity ?? 0) > 0) ? 'medal' : 'versus'),
            measurement_type: result?.measurement_type ?? '',
            event_id: result ? String(result.event_id) : '',
            gold_delegation_id: String(
                result?.placements.find((p) => p.rank === 1)?.delegation_id ??
                    '',
            ),
            silver_delegation_id: String(
                result?.placements.find((p) => p.rank === 2)?.delegation_id ??
                    '',
            ),
            bronze_delegation_id: String(
                result?.placements.find((p) => p.rank === 3)?.delegation_id ??
                    '',
            ),
            evidence: null as File | null,
            gold_attribution:
                result?.placements.find((p) => p.rank === 1)?.attribution ??
                emptyAttribution(),
            silver_attribution:
                result?.placements.find((p) => p.rank === 2)?.attribution ??
                emptyAttribution(),
            bronze_attribution:
                result?.placements.find((p) => p.rank === 3)?.attribution ??
                emptyAttribution(),
            gold_mark: result?.placements.find((p) => p.rank === 1)?.mark ?? '',
            silver_mark:
                result?.placements.find((p) => p.rank === 2)?.mark ?? '',
            bronze_mark:
                result?.placements.find((p) => p.rank === 3)?.mark ?? '',
            gold_count: String(
                result?.placements.find((p) => p.rank === 1)?.tally_quantity ??
                    0,
            ),
            silver_count: String(
                result?.placements.find((p) => p.rank === 2)?.tally_quantity ??
                    0,
            ),
            bronze_count: String(
                result?.placements.find((p) => p.rank === 3)?.tally_quantity ??
                    0,
            ),
        });
    const withMedals = data.result_type === 'medal';
    const [preview, setPreview] = useState<string | null>(null);

    useEffect(
        () => () => {
            if (preview) {
                URL.revokeObjectURL(preview);
            }
        },
        [preview],
    );

    transform((current) => !withMedals ? {
        event_id: current.event_id, result_type: 'versus', measurement_type: current.measurement_type,
        winner_delegation_id: current.gold_delegation_id, loser_delegation_id: current.silver_delegation_id,
        winner_value: current.gold_mark, loser_value: current.silver_mark,
        winner_attribution: current.gold_attribution, loser_attribution: current.silver_attribution, evidence: current.evidence,
    } : ({
        ...current,
        gold_count:
            withMedals && current.gold_delegation_id ? current.gold_count : '0',
        silver_count:
            withMedals && current.silver_delegation_id
                ? current.silver_count
                : '0',
        bronze_count:
            withMedals && current.bronze_delegation_id
                ? current.bronze_count
                : '0',
    }));

    const selectResultType = (mode: string) => {
        const enabled = mode === 'medal';
        setData((current) => ({
            ...current,
            result_type: enabled ? 'medal' : 'versus',
            gold_count: enabled ? '1' : '0',
            silver_count: enabled ? '1' : '0',
            bronze_count: enabled ? '1' : '0',
        }));
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(result ? `/results/${result.id}/direct` : '/results/direct', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setPreview(null);
                onOpenChange(false);
            },
        });
    };

    return (
        <div className="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
            <Head title="Submit Event Result" />
            <PageHeader
                title="Submit Event Result"
                description="Choose an event, record the placements, and attach the result document."
            />
            <div className="flex flex-wrap items-center gap-3">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => onOpenChange(false)}
                    disabled={processing}
                >
                    Back to Results
                </Button>
                <label>Result Type<select aria-label="Result Type" className="ml-2 rounded border p-2" value={data.result_type} onChange={(e) => selectResultType(e.target.value)} disabled={processing}><option value="medal">Medal Result</option><option value="versus">Versus / Non-Medal</option></select></label>
            </div>
            <form onSubmit={submit} className="space-y-6">
                <section className="space-y-3 rounded-xl border bg-card p-4 sm:p-5">
                    <Label
                        htmlFor="result-event"
                        className="text-base font-semibold"
                    >
                        Sports Event
                    </Label>
                    <Select
                        value={data.event_id}
                        onValueChange={(value) =>
                            setData((current) => ({
                                ...current,
                                event_id: value,
                                result_type: events.find((event) => event.id === Number(value))?.default_result_type ?? 'versus',
                                gold_count: '1', silver_count: '1', bronze_count: '1',
                                gold_attribution: emptyAttribution(),
                                silver_attribution: emptyAttribution(),
                                bronze_attribution: emptyAttribution(),
                            }))
                        }
                        disabled={processing || !!result}
                    >
                        <SelectTrigger
                            id="result-event"
                            className="h-auto min-h-11 w-full [&>span]:text-left [&>span]:whitespace-normal"
                        >
                            <SelectValue placeholder="Select a sports event" />
                        </SelectTrigger>
                        <SelectContent>
                            {events.map((option) => (
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
                    {!withMedals && <label className="block">Measurement Type<select aria-label="Measurement Type" required className="ml-2 rounded border p-2" value={data.measurement_type} onChange={(e) => setData('measurement_type', e.target.value)}><option value="">Select measurement type</option><option value="score">Score</option><option value="points">Points</option><option value="time">Time</option><option value="distance">Distance</option></select></label>}
                    {Object.entries(errors)
                        .filter(([key]) => key !== 'event_id')
                        .map(([key, error]) => (
                            <InputError key={key} message={error} />
                        ))}
                </section>
                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
                    <section
                        id="result-participants"
                        className="min-w-0 overflow-hidden rounded-xl border bg-card"
                    >
                        <div className="space-y-1 border-b p-4 sm:p-5">
                            <h2 className="font-semibold">
                                Participants and Results
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {withMedals ? 'Gold is required. Silver and Bronze are optional.' : 'Select a distinct Winner and Loser. Attribution is optional.'}
                            </p>
                        </div>
                        <div className="divide-y">
                            {(
                                [
                                    {
                                        key: 'gold',
                                        place: '1st place',
                                        label: 'Gold',
                                        optional: false,
                                    },
                                    {
                                        key: 'silver',
                                        place: '2nd place',
                                        label: 'Silver',
                                        optional: true,
                                    },
                                    {
                                        key: 'bronze',
                                        place: '3rd place',
                                        label: 'Bronze',
                                        optional: true,
                                    },
                                ] as const
                            ).filter(({ key }) => withMedals || key !== 'bronze').map(({ key, label, optional }) => {
                                const delegationField =
                                    `${key}_delegation_id` as const;
                                const markField = `${key}_mark` as const;
                                const countField = `${key}_count` as const;

                                return (
                                    <div
                                        key={key}
                                        className="space-y-3 p-4 sm:p-5"
                                    >
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h3 className="text-sm font-semibold">
                                                {withMedals ? label : key === 'gold' ? 'Winner' : 'Loser'}
                                            </h3>
                                            {optional && withMedals && (
                                                <span className="text-xs text-muted-foreground">
                                                    Optional
                                                </span>
                                            )}
                                            {withMedals && (
                                                <Badge variant="outline">
                                                    <Award className="mr-1 size-3 text-orange-600" />
                                                    {label}
                                                </Badge>
                                            )}
                                        </div>
                                        <div
                                            className={`grid gap-4 ${withMedals ? 'md:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_7rem]' : 'md:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]'}`}
                                        >
                                            <div className="min-w-0 space-y-2">
                                                <Label
                                                    htmlFor={`${key}-delegation`}
                                                >
                                                    Delegation / Team
                                                </Label>
                                                <Select
                                                    value={
                                                        data[delegationField] ||
                                                        'none'
                                                    }
                                                    onValueChange={(value) =>
                                                        setData((current) => ({
                                                            ...current,
                                                            [delegationField]:
                                                                value === 'none'
                                                                    ? ''
                                                                    : value,
                                                            [`${key}_attribution`]:
                                                                emptyAttribution(),
                                                        }))
                                                    }
                                                    disabled={processing}
                                                >
                                                    <SelectTrigger
                                                        id={`${key}-delegation`}
                                                        className="h-auto min-h-10 w-full [&>span]:text-left [&>span]:whitespace-normal"
                                                    >
                                                        <SelectValue placeholder="Select a delegation" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="none">
                                                            {optional && withMedals
                                                                ? 'No participant'
                                                                : 'Select a delegation'}
                                                        </SelectItem>
                                                        {delegations.map(
                                                            (option) => (
                                                                <SelectItem
                                                                    key={
                                                                        option.id
                                                                    }
                                                                    value={String(
                                                                        option.id,
                                                                    )}
                                                                >
                                                                    {
                                                                        option.label
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                                <InputError
                                                    message={
                                                        errors[delegationField]
                                                    }
                                                />
                                                <AttributionFields
                                                    key={`${data.event_id}-${data[delegationField]}`}
                                                    eventId={Number(
                                                        data.event_id,
                                                    )}
                                                    delegationId={Number(
                                                        data[delegationField],
                                                    )}
                                                    team={
                                                        events.find(
                                                            (e) =>
                                                                e.id ===
                                                                Number(
                                                                    data.event_id,
                                                                ),
                                                        )?.is_team_event ??
                                                        false
                                                    }
                                                    value={
                                                        data[
                                                            `${key}_attribution`
                                                        ]
                                                    }
                                                    onChange={(value) =>
                                                        setData(
                                                            `${key}_attribution`,
                                                            value,
                                                        )
                                                    }
                                                />
                                            </div>
                                            <div className="min-w-0 space-y-2">
                                                <Label htmlFor={`${key}-mark`}>
                                                    {withMedals ? 'Score / Points / Time' : 'Result Value'}
                                                </Label>
                                                <Input
                                                    id={`${key}-mark`}
                                                    className="h-10"
                                                    maxLength={60}
                                                    required={!withMedals}
                                                    inputMode={!withMedals ? 'decimal' : 'text'}
                                                    value={data[markField]}
                                                    placeholder="e.g. 98 points or 12.45 s"
                                                    disabled={processing}
                                                    onChange={(event) =>
                                                        setData(
                                                            markField,
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={errors[markField]}
                                                />
                                            </div>
                                            {withMedals && (
                                                <div className="space-y-2">
                                                    <Label
                                                        htmlFor={`${key}-count`}
                                                    >
                                                        Medal count
                                                    </Label>
                                                    <Input
                                                        id={`${key}-count`}
                                                        aria-label={`${label} medal count`}
                                                        className="h-10"
                                                        type="number"
                                                        min={0}
                                                        max={65535}
                                                        step={1}
                                                        required={
                                                            !!data[
                                                                delegationField
                                                            ]
                                                        }
                                                        disabled={
                                                            processing ||
                                                            !data[
                                                                delegationField
                                                            ]
                                                        }
                                                        value={data[countField]}
                                                        onChange={(event) =>
                                                            setData(
                                                                countField,
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            errors[countField]
                                                        }
                                                    />
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </section>
                    <aside className="min-w-0 space-y-4 rounded-xl border bg-card p-4 sm:p-5">
                        <div className="space-y-1">
                            <h2 className="font-semibold">Result Document</h2>
                            <p className="text-sm text-muted-foreground">
                                Attach a photo or PDF of the recorded result.
                            </p>
                        </div>
                        {result && (
                            <p className="text-sm text-muted-foreground">
                                Your existing document is retained unless you
                                replace it.
                            </p>
                        )}
                        <div className="space-y-2 rounded-lg border border-dashed p-3">
                            <Label htmlFor="direct-result-evidence">
                                Document or photo
                            </Label>
                            <Input
                                id="direct-result-evidence"
                                className="h-auto min-h-10 min-w-0 text-sm"
                                type="file"
                                accept="image/jpeg,image/png,image/webp,application/pdf"
                                capture="environment"
                                disabled={processing}
                                onChange={(event) => {
                                    const file =
                                        event.target.files?.[0] ?? null;
                                    setData('evidence', file);
                                    setPreview(
                                        file?.type.startsWith('image/')
                                            ? URL.createObjectURL(file)
                                            : null,
                                    );
                                }}
                            />
                            <p className="text-xs text-muted-foreground">
                                PDF, JPG, PNG or WebP
                            </p>
                        </div>
                        {preview && (
                            <img
                                src={preview}
                                alt="Result evidence preview"
                                className="max-h-64 w-full rounded-lg border object-contain"
                            />
                        )}
                        {data.evidence && (
                            <p className="text-sm break-all text-muted-foreground">
                                {data.evidence.name}
                            </p>
                        )}
                        <InputError message={errors.evidence} />
                    </aside>
                </div>
                <div className="flex flex-col gap-3 border-t pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-sm text-muted-foreground">
                        Review the event, placements and document before
                        submitting.
                    </p>
                    <Button
                        type="submit"
                        size="lg"
                        disabled={processing}
                        className="w-full sm:w-auto"
                    >
                        <Send className="size-4" />
                        {processing ? 'Submitting?' : 'Submit Result'}
                    </Button>
                </div>
            </form>
        </div>
    );
}

function EncodeForm({
    result,
    entryOptions,
    teamEntryOptions,
    competitionOptions,
    activeMeets,
    eventOptions,
    scheduleOptions,
    onOpenChange,
}: {
    result: Result | null;
    entryOptions: EntryOption[];
    teamEntryOptions: EntryOption[];
    competitionOptions: CompetitionOption[];
    activeMeets: Option[];
    eventOptions: EventOption[];
    scheduleOptions: ScheduleOption[];
    onOpenChange: (open: boolean) => void;
}) {
    const [scope, setScope] = useState<'match' | 'event'>(
        result?.result_scope ?? 'match',
    );
    const { data, setData, post, put, processing, errors, reset, transform } =
        useForm({
            meet_id: result ? String(result.meet_id) : '',
            event_id: result ? String(result.event_id) : '',
            event_schedule_id: 'none',
            match_id: result?.match_id ? String(result.match_id) : '',
            placements: (result
                ? result.placements.map((placement) => ({
                      entry_id: placement.entry_id
                          ? String(placement.entry_id)
                          : '',
                      team_entry_id: placement.team_entry_id
                          ? String(placement.team_entry_id)
                          : '',
                      rank: String(placement.rank),
                      mark: placement.mark ?? '',
                      is_tie: placement.is_tie,
                  }))
                : [
                      {
                          entry_id: '',
                          team_entry_id: '',
                          rank: '1',
                          mark: '',
                          is_tie: false,
                      },
                  ]) as PlacementRow[],
        });

    transform((current) => ({
        ...current,
        event_schedule_id:
            current.event_schedule_id === 'none'
                ? null
                : current.event_schedule_id,
        placements: current.placements.map((row) => ({
            entry_id: row.entry_id,
            team_entry_id: row.team_entry_id,
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
            ? [
                  ...entryOptions.map((option) => ({
                      ...option,
                      participant_type: 'entry' as const,
                  })),
                  ...teamEntryOptions.map((option) => ({
                      ...option,
                      participant_type: 'team' as const,
                  })),
              ].filter(
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
                team_entry_id: '',
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
        <div className="mx-auto w-full max-w-4xl space-y-4 p-4">
            <Button variant="outline" onClick={() => onOpenChange(false)}>
                Back to Results
            </Button>
            <div>
                <div>
                    <h1 className="text-xl font-semibold">
                        {result
                            ? `Edit result — ${result.event}`
                            : 'Encode result'}
                    </h1>
                </div>
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
                                            Match result — scheduled or
                                            non-scheduled
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
                                                setData(
                                                    'event_schedule_id',
                                                    'none',
                                                );
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
                                                setData(
                                                    'event_schedule_id',
                                                    'none',
                                                );
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
                                                setData(
                                                    'event_schedule_id',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select the actual scheduled competition" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">
                                                    Unscheduled event
                                                </SelectItem>
                                                {scheduleOptions
                                                    .filter(
                                                        (option) =>
                                                            String(
                                                                option.meet_id,
                                                            ) ===
                                                                data.meet_id &&
                                                            String(
                                                                option.event_id,
                                                            ) === data.event_id,
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
                                        <InputError
                                            message={errors.event_schedule_id}
                                        />
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
                                                    team_entry_id: '',
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
                                        value={
                                            row.team_entry_id
                                                ? `team:${row.team_entry_id}`
                                                : row.entry_id
                                                  ? `entry:${row.entry_id}`
                                                  : ''
                                        }
                                        onValueChange={(value) => {
                                            const [type, id] = value.split(':');
                                            setRow(i, {
                                                entry_id:
                                                    type === 'entry' ? id : '',
                                                team_entry_id:
                                                    type === 'team' ? id : '',
                                            });
                                        }}
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
                                                    value={`${option.participant_type}:${option.id}`}
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

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {result ? 'Save changes' : 'Encode result'}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
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
    teamEntryOptions,
    competitionOptions,
    canEncode,
    canDirectResult,
    delegationOptions,
}: Props) {
    const [formOpen, setFormOpen] = useState(false);
    const pageUrl = usePage().url;
    const [directOpen, setDirectOpen] = useState(
        pageUrl.split('?')[0] === '/results/submit',
    );
    const [directEditing, setDirectEditing] = useState<Result | null>(null);
    const [editing, setEditing] = useState<Result | null>(null);
    const [correcting, setCorrecting] = useState<Result | null>(null);
    const isTournamentScoped =
        usePage().props.auth.user?.is_tournament_scoped ?? false;

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

    const uploadResultPhoto = (result: Result, photo: File) => {
        router.post(
            `/results/${result.id}/photo`,
            { photo },
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

    if (canEncode && formOpen) {
        return (
            <EncodeForm
                key={editing?.id ?? 'create'}
                result={editing}
                entryOptions={entryOptions}
                teamEntryOptions={teamEntryOptions}
                competitionOptions={competitionOptions}
                activeMeets={activeMeets}
                eventOptions={eventOptionsByMeet}
                scheduleOptions={scheduleOptions}
                onOpenChange={setFormOpen}
            />
        );
    }

    if (canDirectResult && directOpen) {
        return (
            <DirectResultForm
                key={
                    directEditing
                        ? `${directEditing.id}-${directEditing.version}`
                        : 'new'
                }
                result={directEditing}
                onOpenChange={() => {
                    setDirectOpen(false);
                    router.get('/results');
                }}
                events={eventOptionsByMeet.filter(
                    (option) => option.meet_id === filters.meet_id,
                )}
                delegations={delegationOptions}
            />
        );
    }

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
                        canDirectResult &&
                        activeMeets.length > 0 && (
                            <Button
                                onClick={() => {
                                    setDirectEditing(null);
                                    router.get('/results/submit');
                                }}
                            >
                                <Plus />
                                Submit Event Result
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
                                            {result.encoded_at ??
                                                'Date unavailable'}
                                            {result.validated_by && (
                                                <>
                                                    {' '}
                                                    · Validated by{' '}
                                                    {result.validated_by}{' '}
                                                    {result.validated_at}
                                                </>
                                            )}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {result.competition_context}
                                            {result.submitted_by && (
                                                <>
                                                    {' '}
                                                    · Submitted by{' '}
                                                    {result.submitted_by}{' '}
                                                    {result.submitted_at}
                                                </>
                                            )}
                                            {result.official_by && (
                                                <>
                                                    {' '}
                                                    · Officialized by{' '}
                                                    {result.official_by}{' '}
                                                    {result.official_at}
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
                                        {result.awards_medals && (
                                            <Badge variant="outline">
                                                <Award className="mr-1 size-3" />
                                                Medal tally: G{' '}
                                                {result.medal_tally.gold} · S{' '}
                                                {result.medal_tally.silver} · B{' '}
                                                {result.medal_tally.bronze} ·{' '}
                                                Total {result.medal_tally.total}
                                            </Badge>
                                        )}
                                        {result.tm_confirmed ? (
                                            <Badge variant="secondary">
                                                TM confirmed
                                            </Badge>
                                        ) : result.match_id ? (
                                            <Badge variant="outline">
                                                Awaiting TM confirmation
                                            </Badge>
                                        ) : null}
                                        {result.data_issues.length > 0 && (
                                            <div className="w-full rounded-md border border-amber-500/40 bg-amber-500/10 p-3 text-sm">
                                                <div className="font-medium text-amber-700 dark:text-amber-300">
                                                    Submitted with issues —
                                                    resolve later
                                                </div>
                                                <ul className="mt-1 list-disc pl-5 text-muted-foreground">
                                                    {result.data_issues.map(
                                                        (issue) => (
                                                            <li key={issue}>
                                                                {issue}
                                                            </li>
                                                        ),
                                                    )}
                                                </ul>
                                            </div>
                                        )}
                                        {result.operational_remarks && (
                                            <div className="w-full rounded-md border border-amber-500/40 bg-amber-500/10 p-3 text-sm">
                                                <div className="font-medium text-amber-700 dark:text-amber-300">
                                                    Operational remarks
                                                </div>
                                                <div className="mt-1 whitespace-pre-line text-muted-foreground">
                                                    {result.operational_remarks}
                                                </div>
                                            </div>
                                        )}
                                        {result.cancellation_request && (
                                            <div className="w-full rounded-md border border-destructive/40 bg-destructive/5 p-3 text-sm">
                                                <div className="font-medium text-destructive">
                                                    Cancellation requested
                                                </div>
                                                <div className="mt-1">
                                                    {
                                                        result
                                                            .cancellation_request
                                                            .reason
                                                    }
                                                </div>
                                                <div className="mt-1 text-xs text-muted-foreground">
                                                    Requested by{' '}
                                                    {result.cancellation_request
                                                        .requested_by ??
                                                        'ICT'}{' '}
                                                    ·{' '}
                                                    {
                                                        result
                                                            .cancellation_request
                                                            .requested_at
                                                    }
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
                                                result.tm_confirmed ||
                                                result.can_defer_issues) &&
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
                                                    {result.signed_form.type ===
                                                    'direct_result_evidence'
                                                        ? 'Result evidence'
                                                        : 'Signed form'}
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
                                                    const reason = window
                                                        .prompt(
                                                            'Describe the problem requiring cancellation or correction',
                                                        )
                                                        ?.trim();

                                                    if (reason) {
                                                        router.post(
                                                            `/results/${result.id}/request-cancellation`,
                                                            { reason },
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        );
                                                    }
                                                }}
                                            >
                                                Request cancellation
                                            </Button>
                                        )}
                                        {result.can_cancel && (
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                onClick={() => {
                                                    const reason = window
                                                        .prompt(
                                                            'Reason for cancelling this result',
                                                        )
                                                        ?.trim();

                                                    if (reason) {
                                                        router.post(
                                                            `/results/${result.id}/cancel`,
                                                            { reason },
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        );
                                                    }
                                                }}
                                            >
                                                Cancel result
                                            </Button>
                                        )}
                                        {result.can_officialize && (
                                            <ConfirmDialog
                                                trigger={
                                                    <Button size="sm">
                                                        Accept
                                                    </Button>
                                                }
                                                title="Accept this Event Result?"
                                                description="This posts the accepted result and its medals to the official public tally."
                                                confirmLabel="Accept result"
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
                                        {result.result_source === 'direct' &&
                                            canDirectResult &&
                                            [
                                                'encoded',
                                                'submitted',
                                                'returned',
                                                'reopened',
                                            ].includes(result.status) && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => {
                                                        setDirectEditing(
                                                            result,
                                                        );
                                                        setDirectOpen(true);
                                                    }}
                                                >
                                                    Edit Result
                                                </Button>
                                            )}
                                        {result.result_source === 'direct' &&
                                            result.can_reopen && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => {
                                                        const reason = window
                                                            .prompt(
                                                                'Reason for reopening this accepted Result',
                                                            )
                                                            ?.trim();

                                                        if (reason) {
                                                            router.post(
                                                                `/results/${result.id}/reopen`,
                                                                { reason },
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            );
                                                        }
                                                    }}
                                                >
                                                    Reopen for correction
                                                </Button>
                                            )}
                                        {canEncode &&
                                            result.result_source !== 'direct' &&
                                            result.status === 'encoded' && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        openEdit(result)
                                                    }
                                                >
                                                    Edit
                                                </Button>
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
                                        {result.can_manage &&
                                            result.status === 'encoded' && (
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
                                                            destroy(result.id)
                                                                .url,
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
                                    {result.result_source === 'direct' && (
                                        <div className="space-y-3 p-4">
                                            {result.placements.map(
                                                (placement) => (
                                                    <PlacementAttribution
                                                        key={placement.id}
                                                        result={result}
                                                        placement={placement}
                                                    />
                                                ),
                                            )}
                                        </div>
                                    )}
                                    {result.result_source === 'direct' &&
                                        result.placements.every(
                                            (placement) =>
                                                (placement.tally_quantity ??
                                                    0) === 0,
                                        ) && (
                                            <h3 className="border-b p-4 font-semibold">
                                                Team Standing
                                            </h3>
                                        )}
                                    {result.result_photo && (
                                        <div
                                            className={
                                                result.awards_medals
                                                    ? 'border-b bg-amber-50/40 p-4 dark:bg-amber-950/10'
                                                    : 'border-b p-4'
                                            }
                                        >
                                            <p className="mb-2 text-sm font-medium">
                                                Written game result photo
                                                {result.awards_medals &&
                                                    ' · Medal event'}
                                            </p>
                                            <a
                                                href={result.result_photo.url}
                                                target="_blank"
                                                rel="noreferrer"
                                            >
                                                <img
                                                    src={
                                                        result.result_photo.url
                                                    }
                                                    alt={`Written result for ${result.event}`}
                                                    className="max-h-96 w-auto rounded-lg border object-contain"
                                                />
                                            </a>
                                        </div>
                                    )}
                                    {result.can_upload_photo && (
                                        <div className="border-b p-3">
                                            <label className="inline-flex h-8 cursor-pointer items-center gap-2 rounded-md border px-3 text-sm font-medium hover:bg-accent">
                                                <FileUp className="size-4" />
                                                {result.result_photo
                                                    ? 'Replace result photo'
                                                    : 'Attach written result photo'}
                                                <input
                                                    className="sr-only"
                                                    type="file"
                                                    accept="image/jpeg,image/png,image/webp"
                                                    onChange={(event) => {
                                                        const photo =
                                                            event.target
                                                                .files?.[0];

                                                        if (photo) {
                                                            uploadResultPhoto(
                                                                result,
                                                                photo,
                                                            );
                                                        }

                                                        event.target.value = '';
                                                    }}
                                                />
                                            </label>
                                        </div>
                                    )}
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-16">
                                                    {result.result_type === 'versus' ? 'Outcome' : 'Rank'}
                                                </TableHead>
                                                <TableHead>
                                                    {result.result_source ===
                                                    'direct'
                                                        ? 'Delegation / Team'
                                                        : 'Athlete'}
                                                </TableHead>
                                                <TableHead>School</TableHead>
                                                <TableHead>
                                                    {result.measurement_type ?? 'Score / Points / Time'}
                                                </TableHead>
                                                {result.result_source ===
                                                    'direct' && result.result_type !== 'versus' && (
                                                    <TableHead>Medal</TableHead>
                                                )}
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {result.placements.map(
                                                (placement) => (
                                                    <TableRow
                                                        key={`${placement.rank}-${placement.entry_id ?? placement.team_entry_id ?? placement.delegation_id}`}
                                                    >
                                                        <TableCell className="font-medium">
                                                            {result.result_type === 'versus' ? (placement.rank === 1 ? 'Winner' : 'Loser') : placement.rank}
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
                                                        {result.result_source ===
                                                            'direct' && result.result_type !== 'versus' && (
                                                            <TableCell>
                                                                {(placement.tally_quantity ??
                                                                    0) > 0
                                                                    ? `${['Gold', 'Silver', 'Bronze'][placement.rank - 1]} x ${placement.tally_quantity}`
                                                                    : 'No medal'}
                                                            </TableCell>
                                                        )}
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
