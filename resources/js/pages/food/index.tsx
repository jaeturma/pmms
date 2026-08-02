import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Trash2, Utensils } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Textarea } from '@/components/ui/textarea';
import { index } from '@/routes/food';
import {
    destroy as destroyAnnouncement,
    store as storeAnnouncement,
} from '@/routes/meal-announcements';
import {
    destroy as destroySchedule,
    store as storeSchedule,
} from '@/routes/meal-schedules';

type Schedule = {
    id: number;
    meet_id: number;
    meet: string;
    meal_type: string;
    meal_type_label: string;
    date: string;
    starts_at: string | null;
    ends_at: string | null;
    venue: string | null;
    notes: string | null;
};

type Announcement = {
    id: number;
    meet_id: number;
    meet: string;
    title: string;
    message: string;
    posted_by: string;
    posted_at: string;
};

type Option = { id: number; label: string };
type ValueLabel = { value: string; label: string };

type Props = {
    schedules: Schedule[];
    announcements: Announcement[];
    filters: { meet_id: number | null };
    meetOptions: Option[];
    venueOptions: Option[];
    mealTypeOptions: ValueLabel[];
};

function CreateScheduleDialog({
    open,
    onOpenChange,
    meetOptions,
    venueOptions,
    mealTypeOptions,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    meetOptions: Option[];
    venueOptions: Option[];
    mealTypeOptions: ValueLabel[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        meet_id: string;
        meal_type: string;
        date: string;
        starts_at: string;
        ends_at: string;
        venue_id: string;
        notes: string;
    }>({
        meet_id: '',
        meal_type: '',
        date: '',
        starts_at: '',
        ends_at: '',
        venue_id: '',
        notes: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeSchedule().url, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                if (!next) {
reset();
}

                onOpenChange(next);
            }}
        >
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add meal schedule</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="schedule-meet">Meet</Label>
                        <Select
                            value={data.meet_id}
                            onValueChange={(value) => setData('meet_id', value)}
                        >
                            <SelectTrigger id="schedule-meet">
                                <SelectValue placeholder="Select a meet" />
                            </SelectTrigger>
                            <SelectContent>
                                {meetOptions.map((meet) => (
                                    <SelectItem
                                        key={meet.id}
                                        value={String(meet.id)}
                                    >
                                        {meet.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.meet_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="schedule-meal-type">Meal</Label>
                        <Select
                            value={data.meal_type}
                            onValueChange={(value) =>
                                setData('meal_type', value)
                            }
                        >
                            <SelectTrigger id="schedule-meal-type">
                                <SelectValue placeholder="Select a meal" />
                            </SelectTrigger>
                            <SelectContent>
                                {mealTypeOptions.map((type) => (
                                    <SelectItem
                                        key={type.value}
                                        value={type.value}
                                    >
                                        {type.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.meal_type} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="schedule-date">Date</Label>
                        <Input
                            id="schedule-date"
                            type="date"
                            value={data.date}
                            onChange={(e) => setData('date', e.target.value)}
                        />
                        <InputError message={errors.date} />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="schedule-starts">
                                Starts (optional)
                            </Label>
                            <Input
                                id="schedule-starts"
                                type="time"
                                value={data.starts_at}
                                onChange={(e) =>
                                    setData('starts_at', e.target.value)
                                }
                            />
                            <InputError message={errors.starts_at} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="schedule-ends">
                                Ends (optional)
                            </Label>
                            <Input
                                id="schedule-ends"
                                type="time"
                                value={data.ends_at}
                                onChange={(e) =>
                                    setData('ends_at', e.target.value)
                                }
                            />
                            <InputError message={errors.ends_at} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="schedule-venue">Venue (optional)</Label>
                        <Select
                            value={data.venue_id}
                            onValueChange={(value) =>
                                setData('venue_id', value)
                            }
                        >
                            <SelectTrigger id="schedule-venue">
                                <SelectValue placeholder="Select a venue" />
                            </SelectTrigger>
                            <SelectContent>
                                {venueOptions.map((venue) => (
                                    <SelectItem
                                        key={venue.id}
                                        value={String(venue.id)}
                                    >
                                        {venue.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.venue_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="schedule-notes">Notes (optional)</Label>
                        <Textarea
                            id="schedule-notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                        />
                        <InputError message={errors.notes} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Add schedule
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function CreateAnnouncementDialog({
    open,
    onOpenChange,
    meetOptions,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    meetOptions: Option[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        meet_id: string;
        title: string;
        message: string;
    }>({
        meet_id: '',
        title: '',
        message: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeAnnouncement().url, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                if (!next) {
reset();
}

                onOpenChange(next);
            }}
        >
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Post announcement</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="announcement-meet">Meet</Label>
                        <Select
                            value={data.meet_id}
                            onValueChange={(value) => setData('meet_id', value)}
                        >
                            <SelectTrigger id="announcement-meet">
                                <SelectValue placeholder="Select a meet" />
                            </SelectTrigger>
                            <SelectContent>
                                {meetOptions.map((meet) => (
                                    <SelectItem
                                        key={meet.id}
                                        value={String(meet.id)}
                                    >
                                        {meet.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.meet_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="announcement-title">Title</Label>
                        <Input
                            id="announcement-title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                        />
                        <InputError message={errors.title} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="announcement-message">Message</Label>
                        <Textarea
                            id="announcement-message"
                            value={data.message}
                            onChange={(e) => setData('message', e.target.value)}
                        />
                        <InputError message={errors.message} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Post
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Food({
    schedules,
    announcements,
    filters,
    meetOptions,
    venueOptions,
    mealTypeOptions,
}: Props) {
    const [createScheduleOpen, setCreateScheduleOpen] = useState(false);
    const [createAnnouncementOpen, setCreateAnnouncementOpen] = useState(false);

    const applyMeetFilter = (value: string) => {
        router.get(index().url, value === 'all' ? {} : { meet_id: value }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Food" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Food"
                    description="Meal schedule and announcements per meet."
                    actions={
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                onClick={() => setCreateAnnouncementOpen(true)}
                            >
                                <Plus aria-hidden="true" />
                                Post announcement
                            </Button>
                            <Button onClick={() => setCreateScheduleOpen(true)}>
                                <Plus aria-hidden="true" />
                                Add schedule
                            </Button>
                        </div>
                    }
                />

                <Select
                    value={filters.meet_id ? String(filters.meet_id) : 'all'}
                    onValueChange={applyMeetFilter}
                >
                    <SelectTrigger className="w-64" aria-label="Filter by meet">
                        <SelectValue placeholder="All meets" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All meets</SelectItem>
                        {meetOptions.map((meet) => (
                            <SelectItem key={meet.id} value={String(meet.id)}>
                                {meet.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                <Card>
                    <CardHeader>
                        <CardTitle>Meal schedule</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {schedules.length === 0 ? (
                            <EmptyState
                                icon={Utensils}
                                title="No meal schedule yet"
                                description="Add a schedule entry (breakfast, lunch, dinner, or snack) for a meet."
                            />
                        ) : (
                            <ul className="space-y-2">
                                {schedules.map((schedule) => (
                                    <li
                                        key={schedule.id}
                                        className="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3 text-sm"
                                    >
                                        <div>
                                            <span className="font-medium">
                                                {schedule.meal_type_label}
                                            </span>{' '}
                                            <span className="text-muted-foreground">
                                                — {schedule.date}
                                                {schedule.starts_at &&
                                                    ` (${schedule.starts_at.slice(0, 5)}${schedule.ends_at ? `–${schedule.ends_at.slice(0, 5)}` : ''})`}
                                                {schedule.venue &&
                                                    ` at ${schedule.venue}`}
                                                {' — '}
                                                {schedule.meet}
                                            </span>
                                            {schedule.notes && (
                                                <p className="text-muted-foreground">
                                                    {schedule.notes}
                                                </p>
                                            )}
                                        </div>
                                        <ConfirmDialog
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label="Remove schedule entry"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            }
                                            title="Remove schedule entry?"
                                            description={`${schedule.meal_type_label} on ${schedule.date} will be removed.`}
                                            confirmLabel="Remove"
                                            destructive
                                            onConfirm={() =>
                                                router.delete(
                                                    destroySchedule(schedule.id)
                                                        .url,
                                                    {
                                                        preserveScroll: true,
                                                    },
                                                )
                                            }
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Announcements</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {announcements.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No announcements yet.
                            </p>
                        ) : (
                            <ul className="space-y-2">
                                {announcements.map((announcement) => (
                                    <li
                                        key={announcement.id}
                                        className="flex flex-wrap items-start justify-between gap-2 rounded-lg border p-3 text-sm"
                                    >
                                        <div>
                                            <span className="font-medium">
                                                {announcement.title}
                                            </span>{' '}
                                            <Badge variant="outline">
                                                {announcement.meet}
                                            </Badge>
                                            <p className="text-muted-foreground">
                                                {announcement.message}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {announcement.posted_by} —{' '}
                                                {announcement.posted_at}
                                            </p>
                                        </div>
                                        <ConfirmDialog
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label="Remove announcement"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            }
                                            title="Remove announcement?"
                                            description={`"${announcement.title}" will be removed.`}
                                            confirmLabel="Remove"
                                            destructive
                                            onConfirm={() =>
                                                router.delete(
                                                    destroyAnnouncement(
                                                        announcement.id,
                                                    ).url,
                                                    { preserveScroll: true },
                                                )
                                            }
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>

            <CreateScheduleDialog
                open={createScheduleOpen}
                onOpenChange={setCreateScheduleOpen}
                meetOptions={meetOptions}
                venueOptions={venueOptions}
                mealTypeOptions={mealTypeOptions}
            />
            <CreateAnnouncementDialog
                open={createAnnouncementOpen}
                onOpenChange={setCreateAnnouncementOpen}
                meetOptions={meetOptions}
            />
        </>
    );
}

Food.layout = {
    breadcrumbs: [
        {
            title: 'Food',
            href: index(),
        },
    ],
};
