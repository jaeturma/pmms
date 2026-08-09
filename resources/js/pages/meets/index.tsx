import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    activate,
    deactivate,
    events as meetEvents,
    index,
    publish,
    status as meetStatus,
    unpublish,
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
    is_published: boolean;
    is_active: boolean;
    event_ids: number[];
    allowed_transitions: Transition[];
};

type EventOption = {
    id: number;
    label: string;
};

type Props = {
    meet: Meet;
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

export default function Meets({ meet, eventOptions, canManage }: Props) {
    const [selectedEvents, setSelectedEvents] = useState<number[]>(
        meet.event_ids,
    );
    const [eventsSaving, setEventsSaving] = useState(false);

    const { data, setData, put, processing, errors } = useForm({
        name: meet.name,
        school_year: meet.school_year,
        starts_at: meet.starts_at,
        ends_at: meet.ends_at,
        venue: meet.venue ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(update(meet.id).url, { preserveScroll: true });
    };

    const toggleEvent = (id: number, checked: boolean) => {
        setSelectedEvents((current) =>
            checked
                ? [...current, id]
                : current.filter((value) => value !== id),
        );
    };

    const saveEvents = () => {
        setEventsSaving(true);
        router.put(
            meetEvents(meet.id).url,
            { event_ids: selectedEvents },
            { preserveScroll: true, onFinish: () => setEventsSaving(false) },
        );
    };

    return (
        <>
            <Head title="Meet settings" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Meet settings"
                    description="The one meet this deployment runs — rename and redate this record for next year's meet, or when reusing this system elsewhere, rather than creating a new one."
                />

                <div className="flex flex-wrap gap-1.5">
                    <Badge variant={statusVariants[meet.status] ?? 'outline'}>
                        {meet.status_label}
                    </Badge>
                    {meet.is_published && (
                        <Badge variant="outline">Public</Badge>
                    )}
                    {meet.is_active && (
                        <Badge variant="default">Active on landing page</Badge>
                    )}
                </div>

                <div className="max-w-lg space-y-6">
                    <form onSubmit={submit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="meet-name">Name</Label>
                            <Input
                                id="meet-name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                disabled={!canManage}
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
                                disabled={!canManage}
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
                                    disabled={!canManage}
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
                                    disabled={!canManage}
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
                                onChange={(e) =>
                                    setData('venue', e.target.value)
                                }
                                disabled={!canManage}
                            />
                            <InputError message={errors.venue} />
                        </div>
                        {canManage && (
                            <Button type="submit" disabled={processing}>
                                Save changes
                            </Button>
                        )}
                    </form>
                </div>

                {canManage && (
                    <div className="flex flex-wrap items-center gap-2">
                        {meet.allowed_transitions.map((transition) => (
                            <ConfirmDialog
                                key={transition.value}
                                trigger={
                                    <Button size="sm">
                                        {transition.label}
                                    </Button>
                                }
                                title={`${transition.label}?`}
                                description="The meet moves to a new status. Registration and entry rules follow the meet status."
                                confirmLabel={transition.label}
                                onConfirm={() =>
                                    router.patch(
                                        meetStatus(meet.id).url,
                                        { status: transition.value },
                                        { preserveScroll: true },
                                    )
                                }
                            />
                        ))}
                        {meet.status !== 'draft' && (
                            <ConfirmDialog
                                trigger={
                                    <Button variant="outline" size="sm">
                                        {meet.is_published
                                            ? 'Unpublish'
                                            : 'Publish'}
                                    </Button>
                                }
                                title={
                                    meet.is_published
                                        ? 'Remove from public portal?'
                                        : 'Publish to public portal?'
                                }
                                description={
                                    meet.is_published
                                        ? 'The meet disappears from the public portal immediately.'
                                        : 'The meet, its schedule, validated results, and medal tally become publicly visible.'
                                }
                                confirmLabel={
                                    meet.is_published ? 'Unpublish' : 'Publish'
                                }
                                onConfirm={() =>
                                    router.patch(
                                        meet.is_published
                                            ? unpublish(meet.id).url
                                            : publish(meet.id).url,
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            />
                        )}
                        {meet.is_published && (
                            <ConfirmDialog
                                trigger={
                                    <Button
                                        variant={
                                            meet.is_active
                                                ? 'outline'
                                                : 'secondary'
                                        }
                                        size="sm"
                                    >
                                        {meet.is_active
                                            ? 'Deactivate'
                                            : 'Set active'}
                                    </Button>
                                }
                                title={
                                    meet.is_active
                                        ? 'Remove from landing page?'
                                        : 'Feature this meet on the landing page?'
                                }
                                description={
                                    meet.is_active
                                        ? 'The public landing page no longer features this meet directly.'
                                        : 'This meet becomes the one shown on the public landing page.'
                                }
                                confirmLabel={
                                    meet.is_active ? 'Deactivate' : 'Set active'
                                }
                                onConfirm={() =>
                                    router.patch(
                                        meet.is_active
                                            ? deactivate(meet.id).url
                                            : activate(meet.id).url,
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            />
                        )}
                    </div>
                )}

                <Heading
                    variant="small"
                    title="Events"
                    description="Catalog events that run in this meet."
                />
                {eventOptions.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No active events in the catalog yet. Add events first.
                    </p>
                ) : (
                    <div className="max-w-lg space-y-3">
                        <div className="max-h-80 space-y-2 overflow-y-auto rounded-lg border p-3">
                            {eventOptions.map((option) => (
                                <div
                                    key={option.id}
                                    className="flex items-center gap-2"
                                >
                                    <Checkbox
                                        id={`meet-event-${option.id}`}
                                        checked={selectedEvents.includes(
                                            option.id,
                                        )}
                                        onCheckedChange={(checked) =>
                                            toggleEvent(
                                                option.id,
                                                checked === true,
                                            )
                                        }
                                        disabled={!canManage}
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
                        {canManage && (
                            <Button
                                onClick={saveEvents}
                                disabled={eventsSaving}
                            >
                                Save events
                            </Button>
                        )}
                    </div>
                )}
            </div>
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
