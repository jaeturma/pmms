import { Head, router, useForm } from '@inertiajs/react';
import { ClipboardList, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
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
    destroy,
    index,
    status as updateStatus,
    store,
} from '@/routes/meet-sport-assignments';

type Assignment = {
    id: number;
    meet: string;
    sport: string;
    category: string | null;
    user: string;
    user_email: string;
    role: string;
    role_label: string;
    is_lead: boolean;
    start_date: string | null;
    end_date: string | null;
    status: string;
    status_label: string;
};

type Option = { id: number; label: string };
type MeetSportOption = { id: number; meet_id: number; label: string };
type ValueLabel = { value: string; label: string };

type Props = {
    assignments: Assignment[];
    filters: { meet_id: number | null };
    meetOptions: Option[];
    meetSportOptions: MeetSportOption[];
    roleOptions: ValueLabel[];
    statusOptions: ValueLabel[];
    userOptions: Array<{ id: number; label: string }>;
    canManage: boolean;
};

const statusVariant: Record<string, 'default' | 'outline' | 'destructive'> = {
    pending: 'outline',
    active: 'default',
    declined: 'destructive',
    ended: 'outline',
};

function CreateDialog({
    open,
    onOpenChange,
    meetOptions,
    meetSportOptions,
    roleOptions,
    userOptions,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    meetOptions: Option[];
    meetSportOptions: MeetSportOption[];
    roleOptions: ValueLabel[];
    userOptions: Array<{ id: number; label: string }>;
}) {
    const [meetId, setMeetId] = useState('');
    const { data, setData, post, processing, errors, reset } = useForm<{
        meet_sport_id: string;
        user_id: string;
        role: string;
        is_lead: boolean;
        start_date: string;
        end_date: string;
    }>({
        meet_sport_id: '',
        user_id: '',
        role: '',
        is_lead: false,
        start_date: '',
        end_date: '',
    });

    const sportsForMeet = useMemo(
        () =>
            meetSportOptions.filter(
                (option) => String(option.meet_id) === meetId,
            ),
        [meetSportOptions, meetId],
    );

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(store().url, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setMeetId('');
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
                    setMeetId('');
                }

                onOpenChange(next);
            }}
        >
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add assignment</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="assignment-meet">Meet</Label>
                        <Select
                            value={meetId}
                            onValueChange={(value) => {
                                setMeetId(value);
                                setData('meet_sport_id', '');
                            }}
                        >
                            <SelectTrigger id="assignment-meet">
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
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="assignment-sport">Sport</Label>
                        <Select
                            value={data.meet_sport_id}
                            onValueChange={(value) =>
                                setData('meet_sport_id', value)
                            }
                            disabled={meetId === ''}
                        >
                            <SelectTrigger id="assignment-sport">
                                <SelectValue
                                    placeholder={
                                        meetId === ''
                                            ? 'Select a meet first'
                                            : sportsForMeet.length === 0
                                              ? 'No sports on this meet yet'
                                              : 'Select a sport'
                                    }
                                />
                            </SelectTrigger>
                            <SelectContent>
                                {sportsForMeet.map((option) => (
                                    <SelectItem
                                        key={option.id}
                                        value={String(option.id)}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.meet_sport_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="assignment-user">Person</Label>
                        <Select
                            value={data.user_id}
                            onValueChange={(value) =>
                                setData('user_id', value)
                            }
                        >
                            <SelectTrigger id="assignment-user">
                                <SelectValue placeholder="Select a person" />
                            </SelectTrigger>
                            <SelectContent>
                                {userOptions.map((user) => (
                                    <SelectItem
                                        key={user.id}
                                        value={String(user.id)}
                                    >
                                        {user.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.user_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="assignment-role">Role</Label>
                        <Select
                            value={data.role}
                            onValueChange={(value) =>
                                setData('role', value)
                            }
                        >
                            <SelectTrigger id="assignment-role">
                                <SelectValue placeholder="Select a role" />
                            </SelectTrigger>
                            <SelectContent>
                                {roleOptions.map((role) => (
                                    <SelectItem
                                        key={role.value}
                                        value={role.value}
                                    >
                                        {role.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.role} />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="assignment-start">
                                Start date (optional)
                            </Label>
                            <Input
                                id="assignment-start"
                                type="date"
                                value={data.start_date}
                                onChange={(e) =>
                                    setData('start_date', e.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="assignment-end">
                                End date (optional)
                            </Label>
                            <Input
                                id="assignment-end"
                                type="date"
                                value={data.end_date}
                                onChange={(e) =>
                                    setData('end_date', e.target.value)
                                }
                            />
                            <InputError message={errors.end_date} />
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="assignment-lead"
                            checked={data.is_lead}
                            onCheckedChange={(checked) =>
                                setData('is_lead', checked === true)
                            }
                        />
                        <Label htmlFor="assignment-lead" className="font-normal">
                            This is the lead for the role
                        </Label>
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Add assignment
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function MeetSportAssignments({
    assignments,
    filters,
    meetOptions,
    meetSportOptions,
    roleOptions,
    statusOptions,
    userOptions,
    canManage,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);

    const applyMeetFilter = (value: string) => {
        router.get(
            index().url,
            value === 'all' ? {} : { meet_id: value },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Tournament Assignments" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Tournament Assignments"
                    description="Tournament Manager, Secretary, ICT, and Technical Official assignments per meet and sport."
                    actions={
                        canManage && (
                            <Button onClick={() => setCreateOpen(true)}>
                                <Plus aria-hidden="true" />
                                Add assignment
                            </Button>
                        )
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

                {assignments.length === 0 ? (
                    <EmptyState
                        icon={ClipboardList}
                        title="No assignments yet"
                        description="Assign a Tournament Manager, Secretary, ICT, or Technical Official to a meet's sport."
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Meet</TableHead>
                                    <TableHead>Sport</TableHead>
                                    <TableHead>Person</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Status</TableHead>
                                    {canManage && (
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {assignments.map((assignment) => (
                                    <TableRow key={assignment.id}>
                                        <TableCell className="font-medium">
                                            {assignment.meet}
                                        </TableCell>
                                        <TableCell>
                                            {assignment.sport}
                                            {assignment.category && (
                                                <span className="block text-xs text-muted-foreground">
                                                    {assignment.category}
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {assignment.user}
                                            <span className="block text-xs text-muted-foreground">
                                                {assignment.user_email}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            {assignment.role_label}
                                            {assignment.is_lead && (
                                                <Badge
                                                    variant="outline"
                                                    className="ml-2"
                                                >
                                                    Lead
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {canManage ? (
                                                <Select
                                                    value={assignment.status}
                                                    onValueChange={(value) =>
                                                        router.patch(
                                                            updateStatus(
                                                                assignment.id,
                                                            ).url,
                                                            { status: value },
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger
                                                        className="w-32"
                                                        aria-label={`Status for ${assignment.user}`}
                                                    >
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {statusOptions.map(
                                                            (status) => (
                                                                <SelectItem
                                                                    key={
                                                                        status.value
                                                                    }
                                                                    value={
                                                                        status.value
                                                                    }
                                                                >
                                                                    {
                                                                        status.label
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            ) : (
                                                <Badge
                                                    variant={
                                                        statusVariant[
                                                            assignment.status
                                                        ] ?? 'outline'
                                                    }
                                                >
                                                    {assignment.status_label}
                                                </Badge>
                                            )}
                                        </TableCell>
                                        {canManage && (
                                            <TableCell className="text-right">
                                                <ConfirmDialog
                                                    trigger={
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label={`Remove ${assignment.user}'s assignment`}
                                                        >
                                                            <Trash2 className="size-4" />
                                                        </Button>
                                                    }
                                                    title="Remove assignment?"
                                                    description={`${assignment.user} — ${assignment.role_label} for ${assignment.sport} (${assignment.meet})`}
                                                    confirmLabel="Remove"
                                                    destructive
                                                    onConfirm={() =>
                                                        router.delete(
                                                            destroy(
                                                                assignment.id,
                                                            ).url,
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                />
                                            </TableCell>
                                        )}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>

            <CreateDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
                meetOptions={meetOptions}
                meetSportOptions={meetSportOptions}
                roleOptions={roleOptions}
                userOptions={userOptions}
            />
        </>
    );
}

MeetSportAssignments.layout = {
    breadcrumbs: [
        {
            title: 'Tournament Assignments',
            href: index(),
        },
    ],
};
