import { Head, router, useForm } from '@inertiajs/react';
import { Flag, Plus } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    destroy,
    events as meetEvents,
    index,
    status as meetStatus,
    store,
    update,
} from '@/routes/meets';

type Transition = {
    value: string;
    label: string;
};

type Meet = {
    id: number;
    name: string;
    school_year: string;
    starts_at: string;
    ends_at: string;
    venue: string | null;
    status: string;
    status_label: string;
    event_ids: number[];
    allowed_transitions: Transition[];
};

type EventOption = {
    id: number;
    label: string;
};

type Props = {
    meets: Meet[];
    eventOptions: EventOption[];
    canManage: boolean;
};

const statusVariants: Record<string, 'default' | 'secondary' | 'outline'> = {
    draft: 'outline',
    registration_open: 'default',
    registration_closed: 'secondary',
    active: 'default',
    completed: 'secondary',
};

function MeetFormDialog({
    meet,
    open,
    onOpenChange,
}: {
    meet: Meet | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: meet?.name ?? '',
        school_year: meet?.school_year ?? '',
        starts_at: meet?.starts_at ?? '',
        ends_at: meet?.ends_at ?? '',
        venue: meet?.venue ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        };

        if (meet) {
            put(update(meet.id).url, options);
        } else {
            post(store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {meet ? 'Edit meet' : 'Create meet'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="meet-name">Name</Label>
                        <Input
                            id="meet-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            autoFocus
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="meet-school-year">
                            School year (e.g. 2025-2026)
                        </Label>
                        <Input
                            id="meet-school-year"
                            value={data.school_year}
                            onChange={(e) =>
                                setData('school_year', e.target.value)
                            }
                        />
                        <InputError message={errors.school_year} />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="meet-starts">Starts</Label>
                            <Input
                                id="meet-starts"
                                type="date"
                                value={data.starts_at}
                                onChange={(e) =>
                                    setData('starts_at', e.target.value)
                                }
                            />
                            <InputError message={errors.starts_at} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="meet-ends">Ends</Label>
                            <Input
                                id="meet-ends"
                                type="date"
                                value={data.ends_at}
                                onChange={(e) =>
                                    setData('ends_at', e.target.value)
                                }
                            />
                            <InputError message={errors.ends_at} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="meet-venue">
                            Host venue (optional)
                        </Label>
                        <Input
                            id="meet-venue"
                            value={data.venue}
                            onChange={(e) => setData('venue', e.target.value)}
                        />
                        <InputError message={errors.venue} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {meet ? 'Save changes' : 'Create meet'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function MeetEventsDialog({
    meet,
    eventOptions,
    open,
    onOpenChange,
}: {
    meet: Meet;
    eventOptions: EventOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [selected, setSelected] = useState<number[]>(meet.event_ids);
    const [processing, setProcessing] = useState(false);

    const toggle = (id: number, checked: boolean) => {
        setSelected((current) =>
            checked
                ? [...current, id]
                : current.filter((value) => value !== id),
        );
    };

    const save = () => {
        setProcessing(true);
        router.put(
            meetEvents(meet.id).url,
            { event_ids: selected },
            {
                preserveScroll: true,
                onSuccess: () => onOpenChange(false),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Events in {meet.name}</DialogTitle>
                </DialogHeader>
                {eventOptions.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No active events in the catalog yet. Add events first.
                    </p>
                ) : (
                    <div className="max-h-80 space-y-2 overflow-y-auto pr-2">
                        {eventOptions.map((option) => (
                            <div
                                key={option.id}
                                className="flex items-center gap-2"
                            >
                                <Checkbox
                                    id={`meet-event-${option.id}`}
                                    checked={selected.includes(option.id)}
                                    onCheckedChange={(checked) =>
                                        toggle(option.id, checked === true)
                                    }
                                />
                                <Label
                                    htmlFor={`meet-event-${option.id}`}
                                    className="font-normal"
                                >
                                    {option.label}
                                </Label>
                            </div>
                        ))}
                    </div>
                )}
                <DialogFooter>
                    <Button
                        onClick={save}
                        disabled={processing || eventOptions.length === 0}
                    >
                        Save events
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function Meets({ meets, eventOptions, canManage }: Props) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Meet | null>(null);
    const [eventsFor, setEventsFor] = useState<Meet | null>(null);

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (meet: Meet) => {
        setEditing(meet);
        setFormOpen(true);
    };

    return (
        <>
            <Head title="Meets" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Meets"
                    description="Provincial meets and their lifecycle."
                    actions={
                        canManage && (
                            <Button onClick={openCreate}>
                                <Plus />
                                Create meet
                            </Button>
                        )
                    }
                />

                {meets.length === 0 ? (
                    <EmptyState
                        icon={Flag}
                        title="No meets yet"
                        description="Created meets and their status will appear here."
                        action={
                            canManage && (
                                <Button onClick={openCreate}>
                                    Create meet
                                </Button>
                            )
                        }
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>School year</TableHead>
                                    <TableHead>Schedule</TableHead>
                                    <TableHead>Venue</TableHead>
                                    <TableHead>Events</TableHead>
                                    <TableHead>Status</TableHead>
                                    {canManage && (
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {meets.map((meet) => (
                                    <TableRow key={meet.id}>
                                        <TableCell className="font-medium">
                                            {meet.name}
                                        </TableCell>
                                        <TableCell>
                                            {meet.school_year}
                                        </TableCell>
                                        <TableCell>
                                            {meet.starts_at} → {meet.ends_at}
                                        </TableCell>
                                        <TableCell>
                                            {meet.venue ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {meet.event_ids.length}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariants[
                                                        meet.status
                                                    ] ?? 'outline'
                                                }
                                            >
                                                {meet.status_label}
                                            </Badge>
                                        </TableCell>
                                        {canManage && (
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            openEdit(meet)
                                                        }
                                                    >
                                                        Edit
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            setEventsFor(meet)
                                                        }
                                                    >
                                                        Events
                                                    </Button>
                                                    {meet.allowed_transitions.map(
                                                        (transition) => (
                                                            <ConfirmDialog
                                                                key={
                                                                    transition.value
                                                                }
                                                                trigger={
                                                                    <Button size="sm">
                                                                        {
                                                                            transition.label
                                                                        }
                                                                    </Button>
                                                                }
                                                                title={`${transition.label}?`}
                                                                description={`"${meet.name}" moves to a new status. Registration and entry rules follow the meet status.`}
                                                                confirmLabel={
                                                                    transition.label
                                                                }
                                                                onConfirm={() =>
                                                                    router.patch(
                                                                        meetStatus(
                                                                            meet.id,
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
                                                    {meet.status ===
                                                        'draft' && (
                                                        <ConfirmDialog
                                                            trigger={
                                                                <Button
                                                                    variant="destructive"
                                                                    size="sm"
                                                                >
                                                                    Delete
                                                                </Button>
                                                            }
                                                            title="Delete meet?"
                                                            description="This permanently removes the draft meet."
                                                            confirmLabel="Delete"
                                                            destructive
                                                            onConfirm={() =>
                                                                router.delete(
                                                                    destroy(
                                                                        meet.id,
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
            </div>

            <MeetFormDialog
                key={editing?.id ?? 'create'}
                meet={editing}
                open={formOpen}
                onOpenChange={setFormOpen}
            />

            {eventsFor && (
                <MeetEventsDialog
                    key={eventsFor.id}
                    meet={eventsFor}
                    eventOptions={eventOptions}
                    open={eventsFor !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setEventsFor(null);
                        }
                    }}
                />
            )}
        </>
    );
}

Meets.layout = {
    breadcrumbs: [
        {
            title: 'Meets',
            href: index(),
        },
    ],
};
