import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    Printer,
    Search,
    Trash2,
    UserPlus,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Textarea } from '@/components/ui/textarea';
import {
    index as davraaIndex,
    options as davraaOptions,
    print as davraaPrint,
    store as davraaStore,
    update as davraaUpdate,
} from '@/routes/davraa-reports';

type Option = { value: string; label: string };
type SportOption = { id: number; name: string; is_team_sport: boolean };
type EventOption = {
    id: number;
    sport_id: number;
    label: string;
    is_team_event: boolean;
};

type Member = {
    designation: string;
    athlete_id: number | null;
    coach_user_id: number | null;
    sort_order: number;
    name: string;
    last_name: string | null;
    given_names: string | null;
    middle_initial: string | null;
    lrn: string | null;
    school: string | null;
    district: string | null;
    incomplete: string[];
};

type Group = {
    id: number;
    name: string;
    sport_id: number;
    division: string;
    level: string;
    notes: string | null;
    status: string;
    status_label: string;
    editable: boolean;
    event_ids: number[];
    members: Member[];
};

type AthleteChoice = {
    id: number;
    name: string;
    lrn: string | null;
    school: string | null;
    district: string | null;
    on_roster: boolean;
    events: string[];
};
type CoachChoice = {
    id: number;
    name: string;
    associated: boolean;
    school: string | null;
};

type Props = {
    group: Group | null;
    sportOptions: SportOption[];
    eventOptions: EventOption[];
    divisionOptions: Option[];
    levelOptions: Option[];
    designationOptions: Option[];
};

function blankMember(designation: string, sort: number): Member {
    return {
        designation,
        athlete_id: null,
        coach_user_id: null,
        sort_order: sort,
        name: '',
        last_name: '',
        given_names: '',
        middle_initial: '',
        lrn: null,
        school: null,
        district: null,
        incomplete: [],
    };
}

export default function DavraaReportForm({
    group,
    sportOptions,
    eventOptions,
    divisionOptions,
    levelOptions,
    designationOptions,
}: Props) {
    const { data, setData, post, put, processing, errors, transform } = useForm(
        {
            name: group?.name ?? '',
            sport_id: group ? String(group.sport_id) : '',
            division: group?.division ?? '',
            level: group?.level ?? '',
            notes: group?.notes ?? '',
            status: group?.status === 'final' ? 'final' : 'draft',
            event_ids: group?.event_ids ?? ([] as number[]),
            members: (group?.members ?? []).map((m, i) => ({
                ...m,
                sort_order: i,
            })) as Member[],
        },
    );

    const readOnly = group !== null && !group.editable;
    const sportId = Number(data.sport_id) || null;
    const sportEvents = eventOptions.filter((e) => e.sport_id === sportId);

    const [athletes, setAthletes] = useState<AthleteChoice[]>([]);
    const [coaches, setCoaches] = useState<CoachChoice[]>([]);
    const [loadingOptions, setLoadingOptions] = useState(false);
    const [search, setSearch] = useState('');
    const [picked, setPicked] = useState<Set<number>>(new Set());

    const loadOptions = useCallback(
        (term = '') => {
            if (!sportId) {
                return;
            }
            setLoadingOptions(true);
            const params = new URLSearchParams({ sport_id: String(sportId) });
            if (data.division) params.set('division', data.division);
            if (data.level) params.set('level', data.level);
            data.event_ids.forEach((id) =>
                params.append('event_ids[]', String(id)),
            );
            if (term) params.set('search', term);

            fetch(`${davraaOptions().url}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            })
                .then((r) => (r.ok ? r.json() : Promise.reject(r)))
                .then((body) => {
                    setAthletes(body.athletes ?? []);
                    setCoaches(body.coaches ?? []);
                })
                .catch(() => {
                    setAthletes([]);
                    setCoaches([]);
                })
                .finally(() => setLoadingOptions(false));
        },
        [sportId, data.division, data.level, data.event_ids],
    );

    useEffect(() => {
        setPicked(new Set());
        if (sportId) {
            loadOptions();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [sportId]);

    transform((current) => ({
        ...current,
        members: current.members.map((m, i) => ({ ...m, sort_order: i })),
    }));

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true };
        if (group) {
            put(davraaUpdate(group.id).url, options);
        } else {
            post(davraaStore().url, options);
        }
    };

    const memberKey = (m: Member) =>
        m.athlete_id
            ? `a-${m.athlete_id}`
            : m.coach_user_id
              ? `c-${m.coach_user_id}-${m.designation}`
              : `x-${m.sort_order}`;

    const usedAthleteIds = new Set(
        data.members.map((m) => m.athlete_id).filter(Boolean),
    );

    const addAthletes = () => {
        const additions = athletes
            .filter((a) => picked.has(a.id) && !usedAthleteIds.has(a.id))
            .map((a, i): Member => ({
                ...blankMember('athlete', data.members.length + i),
                athlete_id: a.id,
                name: a.name,
                last_name: a.name.split(' ').slice(-1)[0] ?? null,
                given_names: a.name.split(' ').slice(0, -1).join(' ') || null,
                lrn: a.lrn,
                school: a.school,
                district: a.district,
                incomplete: [
                    ...(a.lrn ? [] : ['lrn']),
                    ...(a.school ? [] : ['school_name']),
                    ...(a.district ? [] : ['district_name']),
                ],
            }));
        setData('members', [...data.members, ...additions]);
        setPicked(new Set());
    };

    const addCoach = (coach: CoachChoice, designation: string) => {
        if (
            data.members.some(
                (m) =>
                    m.coach_user_id === coach.id &&
                    m.designation === designation,
            )
        ) {
            return;
        }
        setData('members', [
            ...data.members,
            {
                ...blankMember(designation, data.members.length),
                coach_user_id: coach.id,
                name: coach.name,
                school: coach.school,
            },
        ]);
    };

    const addManualChaperone = () =>
        setData('members', [
            ...data.members,
            blankMember('chaperone', data.members.length),
        ]);

    const patchMember = (index: number, patch: Partial<Member>) =>
        setData(
            'members',
            data.members.map((m, i) => (i === index ? { ...m, ...patch } : m)),
        );

    const move = (index: number, delta: number) => {
        const next = [...data.members];
        const target = index + delta;
        if (target < 0 || target >= next.length) {
            return;
        }
        [next[index], next[target]] = [next[target], next[index]];
        setData('members', next);
    };

    const removeMember = (index: number) =>
        setData(
            'members',
            data.members.filter((_, i) => i !== index),
        );

    const toggleEvent = (id: number) =>
        setData(
            'event_ids',
            data.event_ids.includes(id)
                ? data.event_ids.filter((e) => e !== id)
                : [...data.event_ids, id],
        );

    const memberError = (index: number, field: string) =>
        (errors as Record<string, string>)[`members.${index}.${field}`];

    return (
        <>
            <Head
                title={group ? `Edit — ${group.name}` : 'New DAVRAA Report'}
            />
            <form onSubmit={submit} className="flex flex-col gap-6 p-4">
                <PageHeader
                    title={
                        group
                            ? `Edit: ${group.name}`
                            : 'New DAVRAA Report Group'
                    }
                    description="Choose the Sports Events, Coaches and Athletes that belong together for this DAVRAA report. Grouping is your decision, not derived from matches/results."
                    actions={
                        <div className="flex gap-2">
                            <Button variant="outline" asChild>
                                <Link href={davraaIndex().url}>Back</Link>
                            </Button>
                            {group && (
                                <Button variant="outline" asChild>
                                    <a
                                        href={davraaPrint(group.id).url}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        <Printer /> Preview / Print
                                    </a>
                                </Button>
                            )}
                            {!readOnly && (
                                <Button type="submit" disabled={processing}>
                                    Save
                                </Button>
                            )}
                        </div>
                    }
                />

                {readOnly && (
                    <p className="rounded-lg border border-dashed p-3 text-sm text-muted-foreground">
                        This report is archived and read-only. Restore it from
                        the list to make changes.
                    </p>
                )}

                <fieldset
                    disabled={readOnly}
                    className="grid gap-4 rounded-xl border bg-card p-4 sm:grid-cols-2"
                >
                    <div className="space-y-1.5 sm:col-span-2">
                        <Label htmlFor="name">Report Group Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="e.g. Athletics – Secondary Girls"
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="space-y-1.5">
                        <Label>Sport</Label>
                        <Select
                            value={data.sport_id}
                            onValueChange={(v) =>
                                setData((c) => ({
                                    ...c,
                                    sport_id: v,
                                    event_ids: [],
                                }))
                            }
                        >
                            <SelectTrigger aria-label="Sport">
                                <SelectValue placeholder="Select a sport" />
                            </SelectTrigger>
                            <SelectContent>
                                {sportOptions.map((s) => (
                                    <SelectItem key={s.id} value={String(s.id)}>
                                        {s.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.sport_id} />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-1.5">
                            <Label>Division</Label>
                            <Select
                                value={data.division}
                                onValueChange={(v) => setData('division', v)}
                            >
                                <SelectTrigger aria-label="Division">
                                    <SelectValue placeholder="Division" />
                                </SelectTrigger>
                                <SelectContent>
                                    {divisionOptions.map((o) => (
                                        <SelectItem
                                            key={o.value}
                                            value={o.value}
                                        >
                                            {o.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.division} />
                        </div>
                        <div className="space-y-1.5">
                            <Label>Level</Label>
                            <Select
                                value={data.level}
                                onValueChange={(v) => setData('level', v)}
                            >
                                <SelectTrigger aria-label="Level">
                                    <SelectValue placeholder="Level" />
                                </SelectTrigger>
                                <SelectContent>
                                    {levelOptions.map((o) => (
                                        <SelectItem
                                            key={o.value}
                                            value={o.value}
                                        >
                                            {o.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.level} />
                        </div>
                    </div>
                    <div className="space-y-1.5 sm:col-span-2">
                        <Label htmlFor="notes">Notes (optional)</Label>
                        <Textarea
                            id="notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            rows={2}
                        />
                    </div>
                </fieldset>

                <fieldset
                    disabled={readOnly}
                    className="space-y-3 rounded-xl border bg-card p-4"
                >
                    <div>
                        <h2 className="font-semibold">Sports Event(s)</h2>
                        <p className="text-sm text-muted-foreground">
                            One group can cover several events. Selecting events
                            also narrows the athlete suggestions below.
                        </p>
                    </div>
                    {!sportId ? (
                        <p className="text-sm text-muted-foreground">
                            Select a sport first.
                        </p>
                    ) : sportEvents.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No Sports Events configured for this sport in the
                            current meet.
                        </p>
                    ) : (
                        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            {sportEvents.map((event) => (
                                <label
                                    key={event.id}
                                    className="flex items-center gap-2 rounded border p-2 text-sm"
                                >
                                    <Checkbox
                                        checked={data.event_ids.includes(
                                            event.id,
                                        )}
                                        onCheckedChange={() =>
                                            toggleEvent(event.id)
                                        }
                                    />
                                    {event.label}
                                </label>
                            ))}
                        </div>
                    )}
                    <InputError message={errors.event_ids} />
                </fieldset>

                {sportId && !readOnly && (
                    <div className="grid gap-4 lg:grid-cols-2">
                        <fieldset className="space-y-3 rounded-xl border bg-card p-4">
                            <div className="flex items-center justify-between gap-2">
                                <h2 className="font-semibold">Athletes</h2>
                                <span className="text-xs text-muted-foreground">
                                    from the sport roster
                                    {data.event_ids.length > 0
                                        ? ' + selected events'
                                        : ''}
                                </span>
                            </div>
                            <div className="flex gap-2">
                                <Input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) =>
                                        e.key === 'Enter' &&
                                        (e.preventDefault(),
                                        loadOptions(search))
                                    }
                                    placeholder="Search name or LRN…"
                                    className="h-9"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => loadOptions(search)}
                                >
                                    <Search />
                                </Button>
                            </div>
                            <div className="max-h-72 space-y-1 overflow-auto rounded border p-1">
                                {loadingOptions && (
                                    <p className="p-2 text-sm text-muted-foreground">
                                        Loading…
                                    </p>
                                )}
                                {!loadingOptions && athletes.length === 0 && (
                                    <p className="p-2 text-sm text-muted-foreground">
                                        No athletes found. Try a name search —
                                        you can still add a qualifier even if
                                        entry data is incomplete.
                                    </p>
                                )}
                                {athletes.map((athlete) => {
                                    const used = usedAthleteIds.has(athlete.id);

                                    return (
                                        <label
                                            key={athlete.id}
                                            className={`flex items-start gap-2 rounded p-1.5 text-sm ${used ? 'opacity-40' : ''}`}
                                        >
                                            <Checkbox
                                                disabled={used}
                                                checked={picked.has(athlete.id)}
                                                onCheckedChange={(v) =>
                                                    setPicked((prev) => {
                                                        const next = new Set(
                                                            prev,
                                                        );
                                                        v === true
                                                            ? next.add(
                                                                  athlete.id,
                                                              )
                                                            : next.delete(
                                                                  athlete.id,
                                                              );
                                                        return next;
                                                    })
                                                }
                                            />
                                            <span>
                                                <span className="font-medium">
                                                    {athlete.name}
                                                </span>
                                                {!athlete.on_roster && (
                                                    <Badge
                                                        variant="outline"
                                                        className="ml-1"
                                                    >
                                                        off-roster
                                                    </Badge>
                                                )}
                                                <span className="block text-xs text-muted-foreground">
                                                    LRN {athlete.lrn ?? '—'} ·{' '}
                                                    {athlete.school ??
                                                        'school —'}
                                                    {athlete.events.length
                                                        ? ` · ${athlete.events.join(', ')}`
                                                        : ''}
                                                </span>
                                            </span>
                                        </label>
                                    );
                                })}
                            </div>
                            <Button
                                type="button"
                                size="sm"
                                disabled={picked.size === 0}
                                onClick={addAthletes}
                            >
                                <UserPlus /> Add selected ({picked.size})
                            </Button>
                        </fieldset>

                        <fieldset className="space-y-3 rounded-xl border bg-card p-4">
                            <div className="flex items-center justify-between gap-2">
                                <h2 className="font-semibold">Coaches</h2>
                                <span className="text-xs text-muted-foreground">
                                    associated coaches first — pick any manually
                                </span>
                            </div>
                            <div className="max-h-72 space-y-1 overflow-auto rounded border p-1">
                                {coaches.length === 0 && !loadingOptions && (
                                    <p className="p-2 text-sm text-muted-foreground">
                                        No coach accounts found for this meet.
                                    </p>
                                )}
                                {coaches.map((coach) => (
                                    <div
                                        key={coach.id}
                                        className="flex items-center justify-between gap-2 rounded p-1.5 text-sm"
                                    >
                                        <span>
                                            <span className="font-medium">
                                                {coach.name}
                                            </span>
                                            {coach.associated && (
                                                <Badge
                                                    variant="secondary"
                                                    className="ml-1"
                                                >
                                                    sport
                                                </Badge>
                                            )}
                                            <span className="block text-xs text-muted-foreground">
                                                {coach.school ?? 'school —'}
                                            </span>
                                        </span>
                                        <div className="flex gap-1">
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="outline"
                                                onClick={() =>
                                                    addCoach(coach, 'coach')
                                                }
                                            >
                                                Coach
                                            </Button>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                onClick={() =>
                                                    addCoach(
                                                        coach,
                                                        'assistant_coach',
                                                    )
                                                }
                                            >
                                                Asst.
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                onClick={addManualChaperone}
                            >
                                <UserPlus /> Add chaperone row
                            </Button>
                        </fieldset>
                    </div>
                )}

                <fieldset
                    disabled={readOnly}
                    className="space-y-3 rounded-xl border bg-card p-4"
                >
                    <div>
                        <h2 className="font-semibold">
                            Report rows ({data.members.length})
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Drag order with the arrows. Default order: ATHLETES,
                            COACH, ASST. COACH, CHAPERONE — adjust as your sport
                            requires.
                        </p>
                    </div>
                    {data.members.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No people added yet.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-24">
                                            Order
                                        </TableHead>
                                        <TableHead>Designation</TableHead>
                                        <TableHead>Name</TableHead>
                                        <TableHead>LRN</TableHead>
                                        <TableHead>School / District</TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {data.members.map((member, index) => (
                                        <TableRow key={memberKey(member)}>
                                            <TableCell>
                                                <div className="flex gap-1">
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        onClick={() =>
                                                            move(index, -1)
                                                        }
                                                        aria-label="Move up"
                                                    >
                                                        <ArrowUp />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        onClick={() =>
                                                            move(index, 1)
                                                        }
                                                        aria-label="Move down"
                                                    >
                                                        <ArrowDown />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Select
                                                    value={member.designation}
                                                    onValueChange={(v) =>
                                                        patchMember(index, {
                                                            designation: v,
                                                            ...(v === 'athlete'
                                                                ? {}
                                                                : {
                                                                      athlete_id:
                                                                          null,
                                                                  }),
                                                        })
                                                    }
                                                >
                                                    <SelectTrigger
                                                        className="h-8 w-40"
                                                        aria-label={`Designation ${index + 1}`}
                                                    >
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {designationOptions.map(
                                                            (o) => (
                                                                <SelectItem
                                                                    key={
                                                                        o.value
                                                                    }
                                                                    value={
                                                                        o.value
                                                                    }
                                                                >
                                                                    {o.label}
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            </TableCell>
                                            <TableCell>
                                                {member.athlete_id ||
                                                member.coach_user_id ? (
                                                    <>
                                                        <span className="font-medium">
                                                            {member.name}
                                                        </span>
                                                        {member.incomplete
                                                            .length > 0 && (
                                                            <Badge
                                                                variant="outline"
                                                                className="ml-1"
                                                            >
                                                                incomplete:{' '}
                                                                {member.incomplete.join(
                                                                    ', ',
                                                                )}
                                                            </Badge>
                                                        )}
                                                    </>
                                                ) : (
                                                    <div className="flex gap-1">
                                                        <Input
                                                            className="h-8 w-28"
                                                            placeholder="Last name"
                                                            value={
                                                                member.last_name ??
                                                                ''
                                                            }
                                                            onChange={(e) =>
                                                                patchMember(
                                                                    index,
                                                                    {
                                                                        last_name:
                                                                            e
                                                                                .target
                                                                                .value,
                                                                    },
                                                                )
                                                            }
                                                        />
                                                        <Input
                                                            className="h-8 w-28"
                                                            placeholder="Given names"
                                                            value={
                                                                member.given_names ??
                                                                ''
                                                            }
                                                            onChange={(e) =>
                                                                patchMember(
                                                                    index,
                                                                    {
                                                                        given_names:
                                                                            e
                                                                                .target
                                                                                .value,
                                                                    },
                                                                )
                                                            }
                                                        />
                                                        <Input
                                                            className="h-8 w-12"
                                                            placeholder="M.I."
                                                            value={
                                                                member.middle_initial ??
                                                                ''
                                                            }
                                                            onChange={(e) =>
                                                                patchMember(
                                                                    index,
                                                                    {
                                                                        middle_initial:
                                                                            e
                                                                                .target
                                                                                .value,
                                                                    },
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                )}
                                                {(memberError(
                                                    index,
                                                    'athlete_id',
                                                ) ||
                                                    memberError(
                                                        index,
                                                        'coach_user_id',
                                                    ) ||
                                                    memberError(
                                                        index,
                                                        'last_name',
                                                    )) && (
                                                    <InputError
                                                        message={
                                                            memberError(
                                                                index,
                                                                'athlete_id',
                                                            ) ??
                                                            memberError(
                                                                index,
                                                                'coach_user_id',
                                                            ) ??
                                                            memberError(
                                                                index,
                                                                'last_name',
                                                            )
                                                        }
                                                    />
                                                )}
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {member.lrn ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-xs">
                                                {member.school ?? '—'}
                                                <span className="block text-muted-foreground">
                                                    {member.district ?? '—'}
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                <Button
                                                    type="button"
                                                    size="icon"
                                                    variant="ghost"
                                                    onClick={() =>
                                                        removeMember(index)
                                                    }
                                                    aria-label="Remove row"
                                                >
                                                    <Trash2 />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                    <InputError message={errors.members} />
                </fieldset>

                {!readOnly && (
                    <div className="flex flex-wrap items-center justify-between gap-3 border-t pt-4">
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={data.status === 'final'}
                                onCheckedChange={(v) =>
                                    setData(
                                        'status',
                                        v === true ? 'final' : 'draft',
                                    )
                                }
                            />
                            Mark as finalized
                        </label>
                        <Button type="submit" size="lg" disabled={processing}>
                            Save DAVRAA Report Group
                        </Button>
                    </div>
                )}
            </form>
        </>
    );
}

DavraaReportForm.layout = {
    breadcrumbs: [
        { title: 'DAVRAA Report', href: '/davraa-reports' },
        { title: 'Report Group', href: '/davraa-reports' },
    ],
};
