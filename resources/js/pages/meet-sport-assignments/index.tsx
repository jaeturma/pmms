import { Head, router, useForm } from '@inertiajs/react';
import { ClipboardList, Plus, Search, Trash2 } from 'lucide-react';
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
import {
    destroy,
    index,
    status as updateStatus,
    store,
} from '@/routes/meet-sport-assignments';

type Assignment = {
    id: number;
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

type SportOption = { id: number; label: string };
type SportCategoryOption = { id: number; sport_id: number; label: string };
type ValueLabel = { value: string; label: string };
type UserOption = {
    id: number;
    name: string;
    identity: string;
    role: string;
};

type Props = {
    assignments: Paginated<Assignment>;
    filters: { search: string };
    sportOptions: SportOption[];
    sportCategoryOptions: SportCategoryOption[];
    roleOptions: ValueLabel[];
    statusOptions: ValueLabel[];
    userOptions: UserOption[];
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
    sportOptions,
    sportCategoryOptions,
    roleOptions,
    userOptions,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    sportOptions: SportOption[];
    sportCategoryOptions: SportCategoryOption[];
    roleOptions: ValueLabel[];
    userOptions: UserOption[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        sport_id: string;
        sport_category_id: string;
        user_id: string;
        role: string;
        is_lead: boolean;
        start_date: string;
        end_date: string;
    }>({
        sport_id: '',
        sport_category_id: '',
        user_id: '',
        role: '',
        is_lead: false,
        start_date: '',
        end_date: '',
    });
    const selectedSport = sportOptions.find(
        (option) => String(option.id) === data.sport_id,
    );
    const availableCategories = sportCategoryOptions.filter(
        (option) => option.sport_id === selectedSport?.id,
    );

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
        <Dialog
            open={open}
            onOpenChange={(next) => {
                if (!next) {
                    reset();
                }

                onOpenChange(next);
            }}
        >
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Add tournament assignment</DialogTitle>
                    <p className="text-sm text-muted-foreground">
                        Select an enabled user account and define their scope
                        for the current meet.
                    </p>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-5">
                    <div className="space-y-2">
                        <Label htmlFor="assignment-user">Person *</Label>
                        <Select
                            value={data.user_id}
                            onValueChange={(value) => setData('user_id', value)}
                        >
                            <SelectTrigger
                                id="assignment-user"
                                className="h-11"
                            >
                                <SelectValue placeholder="Select a user account" />
                            </SelectTrigger>
                            <SelectContent className="max-h-72 [&_[data-slot=select-viewport]]:max-h-64 [&_[data-slot=select-viewport]]:overflow-y-scroll">
                                {userOptions.map((user) => (
                                    <SelectItem
                                        key={user.id}
                                        value={String(user.id)}
                                        className="py-2.5"
                                    >
                                        <span className="flex min-w-0 flex-col items-start">
                                            <span className="font-medium">
                                                {user.name}
                                            </span>
                                            <span className="max-w-96 truncate text-xs text-muted-foreground">
                                                {user.identity} · {user.role}
                                            </span>
                                        </span>
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <p className="text-xs text-muted-foreground">
                            All enabled accounts, including newly created users,
                            are available.
                        </p>
                        <InputError message={errors.user_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="assignment-sport">Sport *</Label>
                        <Select
                            value={data.sport_id}
                            onValueChange={(value) =>
                                setData((current) => ({
                                    ...current,
                                    sport_id: value,
                                    sport_category_id: '',
                                }))
                            }
                        >
                            <SelectTrigger id="assignment-sport">
                                <SelectValue
                                    placeholder={
                                        sportOptions.length === 0
                                            ? 'No sports available'
                                            : 'Select a sport'
                                    }
                                />
                            </SelectTrigger>
                            <SelectContent>
                                {sportOptions.map((option) => (
                                    <SelectItem
                                        key={option.id}
                                        value={String(option.id)}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.sport_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="assignment-category">
                            Category / event scope (optional)
                        </Label>
                        <Select
                            value={data.sport_category_id || 'all'}
                            onValueChange={(value) =>
                                setData(
                                    'sport_category_id',
                                    value === 'all' ? '' : value,
                                )
                            }
                            disabled={!selectedSport}
                        >
                            <SelectTrigger id="assignment-category">
                                <SelectValue placeholder="All categories" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All categories / events
                                </SelectItem>
                                {availableCategories.map((option) => (
                                    <SelectItem
                                        key={option.id}
                                        value={String(option.id)}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.sport_category_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="assignment-role">Role *</Label>
                        <Select
                            value={data.role}
                            onValueChange={(value) => setData('role', value)}
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
                        <Label
                            htmlFor="assignment-lead"
                            className="font-normal"
                        >
                            This is the lead for the role
                        </Label>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancel
                        </Button>
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
    sportOptions,
    sportCategoryOptions,
    roleOptions,
    statusOptions,
    userOptions,
    canManage,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [search, setSearch] = useState(filters.search);
    const submitSearch = (event: FormEvent) => {
        event.preventDefault();
        router.get(index().url, { search }, { preserveState: true });
    };

    return (
        <>
            <Head title="Tournament Assignments" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Tournament Assignments"
                    description="Tournament Manager, Secretary, ICT, and Technical Official assignments per sport."
                    actions={
                        canManage && (
                            <Button onClick={() => setCreateOpen(true)}>
                                <Plus aria-hidden="true" />
                                Add assignment
                            </Button>
                        )
                    }
                />

                <form onSubmit={submitSearch} className="flex max-w-xl gap-2">
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search person, sport, role, category, or status"
                        aria-label="Search tournament assignments"
                    />
                    <Button type="submit" variant="outline">
                        <Search className="size-4" />
                        Search
                    </Button>
                </form>

                {assignments.data.length === 0 ? (
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
                                {assignments.data.map((assignment) => (
                                    <TableRow key={assignment.id}>
                                        <TableCell className="font-medium">
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
                                                    description={`${assignment.user} — ${assignment.role_label} for ${assignment.sport}`}
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
                <PaginationControls
                    page={assignments}
                    url={index().url}
                    label="assignments"
                    params={{ search: filters.search }}
                />
            </div>

            <CreateDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
                sportOptions={sportOptions}
                sportCategoryOptions={sportCategoryOptions}
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
