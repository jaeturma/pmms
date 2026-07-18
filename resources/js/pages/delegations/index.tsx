import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus, Printer, UsersRound } from 'lucide-react';
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
    approve,
    destroy,
    index,
    officers as officersRoute,
    returnMethod,
    store,
    submit,
    update,
} from '@/routes/delegations';
import { roster } from '@/routes/reports';

type Officer = {
    id: number;
    name: string;
};

type Delegation = {
    id: number;
    school: string;
    meet: string;
    head_name: string;
    head_phone: string | null;
    head_email: string | null;
    status: string;
    status_label: string;
    officers: Officer[];
    can_view_roster: boolean;
    can_update: boolean;
    can_submit: boolean;
    can_approve: boolean;
    can_delete: boolean;
    can_assign: boolean;
};

type Option = {
    id: number;
    name: string;
};

type OfficerOption = {
    id: number;
    name: string;
    email: string;
};

type Props = {
    delegations: Paginated<Delegation>;
    filters: { search: string };
    meetOptions: Option[];
    schoolOptions: Option[];
    officerOptions: OfficerOption[];
    canManage: boolean;
};

const statusVariants: Record<string, 'default' | 'secondary' | 'outline'> = {
    draft: 'outline',
    submitted: 'default',
    approved: 'secondary',
};

function CreateDelegationDialog({
    meetOptions,
    schoolOptions,
    open,
    onOpenChange,
}: {
    meetOptions: Option[];
    schoolOptions: Option[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        meet_id: '',
        school_id: '',
        head_name: '',
        head_phone: '',
        head_email: '',
    });

    const submitForm = (e: FormEvent) => {
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
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Register delegation</DialogTitle>
                </DialogHeader>
                {meetOptions.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No meet currently has open registration. Open a meet's
                        registration first.
                    </p>
                ) : (
                    <form onSubmit={submitForm} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="delegation-meet">Meet</Label>
                            <Select
                                value={data.meet_id}
                                onValueChange={(value) =>
                                    setData('meet_id', value)
                                }
                            >
                                <SelectTrigger id="delegation-meet">
                                    <SelectValue placeholder="Select a meet" />
                                </SelectTrigger>
                                <SelectContent>
                                    {meetOptions.map((meet) => (
                                        <SelectItem
                                            key={meet.id}
                                            value={String(meet.id)}
                                        >
                                            {meet.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.meet_id} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="delegation-school">School</Label>
                            <Select
                                value={data.school_id}
                                onValueChange={(value) =>
                                    setData('school_id', value)
                                }
                            >
                                <SelectTrigger id="delegation-school">
                                    <SelectValue placeholder="Select a school" />
                                </SelectTrigger>
                                <SelectContent>
                                    {schoolOptions.map((school) => (
                                        <SelectItem
                                            key={school.id}
                                            value={String(school.id)}
                                        >
                                            {school.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.school_id} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="delegation-head">
                                Head of delegation
                            </Label>
                            <Input
                                id="delegation-head"
                                value={data.head_name}
                                onChange={(e) =>
                                    setData('head_name', e.target.value)
                                }
                            />
                            <InputError message={errors.head_name} />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="delegation-phone">
                                    Phone (optional)
                                </Label>
                                <Input
                                    id="delegation-phone"
                                    value={data.head_phone}
                                    onChange={(e) =>
                                        setData('head_phone', e.target.value)
                                    }
                                />
                                <InputError message={errors.head_phone} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="delegation-email">
                                    Email (optional)
                                </Label>
                                <Input
                                    id="delegation-email"
                                    value={data.head_email}
                                    onChange={(e) =>
                                        setData('head_email', e.target.value)
                                    }
                                />
                                <InputError message={errors.head_email} />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="submit" disabled={processing}>
                                Register delegation
                            </Button>
                        </DialogFooter>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}

function EditDelegationDialog({
    delegation,
    open,
    onOpenChange,
}: {
    delegation: Delegation;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, put, processing, errors } = useForm({
        head_name: delegation.head_name,
        head_phone: delegation.head_phone ?? '',
        head_email: delegation.head_email ?? '',
    });

    const submitForm = (e: FormEvent) => {
        e.preventDefault();
        put(update(delegation.id).url, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {delegation.school} — head of delegation
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submitForm} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="edit-head">Head of delegation</Label>
                        <Input
                            id="edit-head"
                            value={data.head_name}
                            onChange={(e) =>
                                setData('head_name', e.target.value)
                            }
                        />
                        <InputError message={errors.head_name} />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="edit-phone">Phone (optional)</Label>
                            <Input
                                id="edit-phone"
                                value={data.head_phone}
                                onChange={(e) =>
                                    setData('head_phone', e.target.value)
                                }
                            />
                            <InputError message={errors.head_phone} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="edit-email">Email (optional)</Label>
                            <Input
                                id="edit-email"
                                value={data.head_email}
                                onChange={(e) =>
                                    setData('head_email', e.target.value)
                                }
                            />
                            <InputError message={errors.head_email} />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Save changes
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function OfficersDialog({
    delegation,
    officerOptions,
    open,
    onOpenChange,
}: {
    delegation: Delegation;
    officerOptions: OfficerOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [selected, setSelected] = useState<number[]>(
        delegation.officers.map((officer) => officer.id),
    );
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
            officersRoute(delegation.id).url,
            { user_ids: selected },
            {
                preserveScroll: true,
                onSuccess: () => onOpenChange(false),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Officers for {delegation.school}</DialogTitle>
                </DialogHeader>
                {officerOptions.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No user accounts have the delegation officer role yet.
                    </p>
                ) : (
                    <div className="max-h-72 space-y-2 overflow-y-auto pr-2">
                        {officerOptions.map((option) => (
                            <div
                                key={option.id}
                                className="flex items-center gap-2"
                            >
                                <Checkbox
                                    id={`officer-${option.id}`}
                                    checked={selected.includes(option.id)}
                                    onCheckedChange={(checked) =>
                                        toggle(option.id, checked === true)
                                    }
                                />
                                <Label
                                    htmlFor={`officer-${option.id}`}
                                    className="font-normal"
                                >
                                    {option.name}
                                    <span className="text-muted-foreground">
                                        {' '}
                                        ({option.email})
                                    </span>
                                </Label>
                            </div>
                        ))}
                    </div>
                )}
                <DialogFooter>
                    <Button
                        onClick={save}
                        disabled={processing || officerOptions.length === 0}
                    >
                        Save officers
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function Delegations({
    delegations,
    filters,
    meetOptions,
    schoolOptions,
    officerOptions,
    canManage,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editing, setEditing] = useState<Delegation | null>(null);
    const [assigning, setAssigning] = useState<Delegation | null>(null);

    return (
        <>
            <Head title="Delegations" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Delegations"
                    description="School delegations registered per meet."
                    actions={
                        canManage && (
                            <Button onClick={() => setCreateOpen(true)}>
                                <Plus />
                                Register delegation
                            </Button>
                        )
                    }
                />

                <SearchBar
                    initial={filters.search}
                    placeholder="Search delegations"
                    url={index().url}
                />

                {delegations.data.length === 0 ? (
                    <EmptyState
                        icon={UsersRound}
                        title="No delegations yet"
                        description="Registered school delegations will appear here."
                        action={
                            canManage && (
                                <Button onClick={() => setCreateOpen(true)}>
                                    Register delegation
                                </Button>
                            )
                        }
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>School</TableHead>
                                    <TableHead>Meet</TableHead>
                                    <TableHead>Head</TableHead>
                                    <TableHead>Officers</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {delegations.data.map((delegation) => (
                                    <TableRow key={delegation.id}>
                                        <TableCell className="font-medium">
                                            {delegation.school}
                                        </TableCell>
                                        <TableCell>{delegation.meet}</TableCell>
                                        <TableCell>
                                            {delegation.head_name}
                                        </TableCell>
                                        <TableCell>
                                            {delegation.officers.length === 0
                                                ? '—'
                                                : delegation.officers
                                                      .map(
                                                          (officer) =>
                                                              officer.name,
                                                      )
                                                      .join(', ')}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariants[
                                                        delegation.status
                                                    ] ?? 'outline'
                                                }
                                            >
                                                {delegation.status_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                {delegation.can_view_roster && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={roster(
                                                                delegation.id,
                                                            )}
                                                        >
                                                            <Printer />
                                                            Roster
                                                        </Link>
                                                    </Button>
                                                )}
                                                {delegation.can_update && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            setEditing(
                                                                delegation,
                                                            )
                                                        }
                                                    >
                                                        Edit
                                                    </Button>
                                                )}
                                                {delegation.can_assign && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            setAssigning(
                                                                delegation,
                                                            )
                                                        }
                                                    >
                                                        Officers
                                                    </Button>
                                                )}
                                                {delegation.can_submit && (
                                                    <ConfirmDialog
                                                        trigger={
                                                            <Button size="sm">
                                                                Submit
                                                            </Button>
                                                        }
                                                        title="Submit delegation?"
                                                        description="Submitted delegations await organizer approval and can no longer be edited by officers."
                                                        confirmLabel="Submit"
                                                        onConfirm={() =>
                                                            router.patch(
                                                                submit(
                                                                    delegation.id,
                                                                ).url,
                                                                {},
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    />
                                                )}
                                                {delegation.can_approve && (
                                                    <>
                                                        <ConfirmDialog
                                                            trigger={
                                                                <Button size="sm">
                                                                    Approve
                                                                </Button>
                                                            }
                                                            title="Approve delegation?"
                                                            description="The delegation becomes part of the meet."
                                                            confirmLabel="Approve"
                                                            onConfirm={() =>
                                                                router.patch(
                                                                    approve(
                                                                        delegation.id,
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
                                                                    variant="outline"
                                                                    size="sm"
                                                                >
                                                                    Return
                                                                </Button>
                                                            }
                                                            title="Return delegation?"
                                                            description="The delegation goes back to draft so officers can correct it."
                                                            confirmLabel="Return to draft"
                                                            onConfirm={() =>
                                                                router.patch(
                                                                    returnMethod(
                                                                        delegation.id,
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
                                                {delegation.can_delete && (
                                                    <ConfirmDialog
                                                        trigger={
                                                            <Button
                                                                variant="destructive"
                                                                size="sm"
                                                            >
                                                                Delete
                                                            </Button>
                                                        }
                                                        title="Delete delegation?"
                                                        description="This permanently removes the draft delegation."
                                                        confirmLabel="Delete"
                                                        destructive
                                                        onConfirm={() =>
                                                            router.delete(
                                                                destroy(
                                                                    delegation.id,
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
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <PaginationControls
                    page={delegations}
                    url={index().url}
                    label="delegations"
                    params={filters.search ? { search: filters.search } : {}}
                />
            </div>

            <CreateDelegationDialog
                meetOptions={meetOptions}
                schoolOptions={schoolOptions}
                open={createOpen}
                onOpenChange={setCreateOpen}
            />

            {editing && (
                <EditDelegationDialog
                    key={editing.id}
                    delegation={editing}
                    open={editing !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setEditing(null);
                        }
                    }}
                />
            )}

            {assigning && (
                <OfficersDialog
                    key={assigning.id}
                    delegation={assigning}
                    officerOptions={officerOptions}
                    open={assigning !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setAssigning(null);
                        }
                    }}
                />
            )}
        </>
    );
}

Delegations.layout = {
    breadcrumbs: [
        {
            title: 'Delegations',
            href: index(),
        },
    ],
};
