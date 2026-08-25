import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    KeyRound,
    Network,
    Pencil,
    Search,
    ShieldCheck,
    UserPlus,
    UsersRound,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
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

type UserRow = {
    id: number;
    name: string;
    username: string | null;
    email: string | null;
    role: string;
    role_label: string;
    additional_roles: string[];
    person: string | null;
    roles: string[];
    assignments: Array<{ type: string; scope: string; status: string }>;
    disabled: boolean;
    approval_status: string;
};
type RoleOption = { value: string; label: string; permissions: string[] };

function AdditionalRoles({
    roles,
    primaryRole,
    selected,
    setSelected,
}: {
    roles: RoleOption[];
    primaryRole: string;
    selected: string[];
    setSelected: (roles: string[]) => void;
}) {
    return (
        <div className="grid gap-2">
            <Label>Additional roles</Label>
            <div className="grid gap-2 rounded-md border p-3 sm:grid-cols-2">
                {roles
                    .filter((role) => role.value !== primaryRole)
                    .map((role) => (
                        <label
                            key={role.value}
                            className="flex items-center gap-2 text-sm"
                        >
                            <Checkbox
                                checked={selected.includes(role.value)}
                                onCheckedChange={(checked) =>
                                    setSelected(
                                        checked
                                            ? [...selected, role.value]
                                            : selected.filter(
                                                  (value) =>
                                                      value !== role.value,
                                              ),
                                    )
                                }
                            />
                            {role.label}
                        </label>
                    ))}
            </div>
            <p className="text-xs text-muted-foreground">
                A user can hold multiple roles, such as Technical Official
                and Tournament ICT.
            </p>
        </div>
    );
}

function NewUser({ roles, close }: { roles: RoleOption[]; close: () => void }) {
    const form = useForm({
        name: '',
        username: '',
        email: '',
        role: 'viewer',
        additional_roles: [] as string[],
    });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/system/users', { preserveScroll: true, onSuccess: close });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && close()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create user account</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="new-name">Name</Label>
                        <Input
                            id="new-name"
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                            autoFocus
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="new-username">Username</Label>
                        <Input
                            id="new-username"
                            value={form.data.username}
                            onChange={(e) =>
                                form.setData('username', e.target.value)
                            }
                        />
                        <InputError message={form.errors.username} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="new-email">
                            Email (optional with username)
                        </Label>
                        <Input
                            id="new-email"
                            type="email"
                            value={form.data.email}
                            onChange={(e) =>
                                form.setData('email', e.target.value)
                            }
                        />
                        <InputError message={form.errors.email} />
                    </div>
                    <div className="grid gap-2">
                        <Label>Role</Label>
                        <Select
                            value={form.data.role}
                            onValueChange={(value) =>
                                form.setData('role', value)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {roles.map((role) => (
                                    <SelectItem
                                        key={role.value}
                                        value={role.value}
                                    >
                                        {role.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <AdditionalRoles
                        roles={roles}
                        primaryRole={form.data.role}
                        selected={form.data.additional_roles.filter(
                            (role) => role !== form.data.role,
                        )}
                        setSelected={(additionalRoles) =>
                            form.setData('additional_roles', additionalRoles)
                        }
                    />
                    <InputError message={form.errors.additional_roles} />
                    <p className="text-sm text-muted-foreground">
                        Initial password:{' '}
                        <span className="font-mono text-foreground">
                            DdOPaa2026!
                        </span>
                    </p>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={close}
                        >
                            Cancel
                        </Button>
                        <Button disabled={form.processing}>
                            Create account
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function EditUser({
    user,
    roles,
    close,
}: {
    user: UserRow;
    roles: RoleOption[];
    close: () => void;
}) {
    const form = useForm({
        name: user.name,
        username: user.username ?? '',
        email: user.email ?? '',
        role: user.role,
        additional_roles: user.additional_roles,
        disabled: user.disabled,
    });
    const selectedRole = roles.find((role) => role.value === form.data.role);
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(`/system/users/${user.id}`, {
            preserveScroll: true,
            onSuccess: close,
        });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && close()}>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Edit user account</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="user-name">Name</Label>
                        <Input
                            id="user-name"
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="username">Username</Label>
                            <Input
                                id="username"
                                value={form.data.username}
                                onChange={(e) =>
                                    form.setData('username', e.target.value)
                                }
                            />
                            <InputError message={form.errors.username} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                            />
                            <InputError message={form.errors.email} />
                        </div>
                    </div>
                    <AdditionalRoles
                        roles={roles}
                        primaryRole={form.data.role}
                        selected={form.data.additional_roles.filter(
                            (role) => role !== form.data.role,
                        )}
                        setSelected={(additionalRoles) =>
                            form.setData('additional_roles', additionalRoles)
                        }
                    />
                    <InputError message={form.errors.additional_roles} />
                    <div className="grid gap-2">
                        <Label>Role</Label>
                        <Select
                            value={form.data.role}
                            onValueChange={(value) =>
                                form.setData('role', value)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {roles.map((role) => (
                                    <SelectItem
                                        key={role.value}
                                        value={role.value}
                                    >
                                        {role.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.role} />
                        <div className="rounded-md bg-muted p-3 text-sm text-muted-foreground">
                            <p className="mb-1 font-medium text-foreground">
                                Permissions included
                            </p>
                            <ul className="list-disc space-y-1 pl-5">
                                {selectedRole?.permissions.map((permission) => (
                                    <li key={permission}>{permission}</li>
                                ))}
                            </ul>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="disabled"
                            checked={form.data.disabled}
                            onCheckedChange={(checked) =>
                                form.setData('disabled', checked === true)
                            }
                        />
                        <Label htmlFor="disabled">Disable this account</Label>
                    </div>
                    <InputError message={form.errors.disabled} />
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={close}
                        >
                            Cancel
                        </Button>
                        <Button disabled={form.processing}>Save changes</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Users({
    users,
    filters,
    roles,
}: {
    users: Paginated<UserRow>;
    filters: { search: string };
    roles: RoleOption[];
}) {
    const [search, setSearch] = useState(filters.search);
    const [selected, setSelected] = useState<UserRow | null>(null);
    const [creating, setCreating] = useState(false);
    const submitSearch = (event: FormEvent) => {
        event.preventDefault();
        router.get('/system/users', { search }, { preserveState: true });
    };

    return (
        <>
            <Head title="Users, roles & permissions" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Users, roles & permissions"
                    description="Manage sign-in accounts, assign system roles, review included permissions, and reset passwords."
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="outline">
                                <Link href="/meet-sport-assignments">
                                    <Network className="size-4" />
                                    Sport assignments
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href="/management-teams">
                                    <UsersRound className="size-4" />
                                    Management teams
                                </Link>
                            </Button>
                        </div>
                    }
                />
                <div className="flex flex-wrap justify-between gap-3">
                    <form
                        onSubmit={submitSearch}
                        className="flex w-full max-w-xl gap-2"
                    >
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search name, username or email"
                        />
                        <Button type="submit" variant="outline">
                            <Search className="size-4" />
                            Search
                        </Button>
                    </form>
                    <Button onClick={() => setCreating(true)}>
                        <UserPlus className="size-4" />
                        Create user
                    </Button>
                </div>
                <div className="overflow-x-auto rounded-[5px] border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>User</TableHead>
                                <TableHead>Role</TableHead>
                                <TableHead>Assignments</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.data.map((user) => (
                                <TableRow key={user.id}>
                                    <TableCell>
                                        <p className="font-medium">
                                            {user.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {user.username ??
                                                user.email ??
                                                'No sign-in identifier'}
                                        </p>
                                        {user.person &&
                                            user.person !== user.name && (
                                                <p className="text-xs text-muted-foreground">
                                                    Person: {user.person}
                                                </p>
                                            )}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex max-w-56 flex-wrap gap-1">
                                            {user.roles.map((role) => (
                                                <Badge
                                                    key={role}
                                                    variant="secondary"
                                                >
                                                    <ShieldCheck className="mr-1 size-3" />
                                                    {role}
                                                </Badge>
                                            ))}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div className="min-w-56 space-y-2">
                                            {user.assignments.length === 0 ? (
                                                <span className="text-xs text-muted-foreground">
                                                    No scoped assignment
                                                </span>
                                            ) : (
                                                user.assignments.map(
                                                    (assignment, index) => (
                                                        <div
                                                            key={`${assignment.type}-${assignment.scope}-${index}`}
                                                        >
                                                            <p className="text-xs font-medium">
                                                                {
                                                                    assignment.type
                                                                }{' '}
                                                                ·{' '}
                                                                {
                                                                    assignment.status
                                                                }
                                                            </p>
                                                            <p className="text-xs text-muted-foreground">
                                                                {assignment.scope ||
                                                                    'Organization-wide'}
                                                            </p>
                                                        </div>
                                                    ),
                                                )
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                user.approval_status ===
                                                'pending'
                                                    ? 'secondary'
                                                    : user.disabled
                                                      ? 'destructive'
                                                      : 'outline'
                                            }
                                        >
                                            {user.approval_status === 'pending'
                                                ? 'Pending approval'
                                                : user.disabled
                                                  ? 'Disabled'
                                                  : 'Active'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() =>
                                                    setSelected(user)
                                                }
                                            >
                                                <Pencil className="size-4" />
                                                Edit
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => {
                                                    if (
                                                        window.confirm(
                                                            `Reset ${user.name}'s password to DdOPaa2026!?`,
                                                        )
                                                    ) {
                                                        router.post(
                                                            `/system/users/${user.id}/reset-password`,
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        );
                                                    }
                                                }}
                                            >
                                                <KeyRound className="size-4" />
                                                Reset password
                                            </Button>
                                            {user.approval_status ===
                                                'pending' && (
                                                <Button
                                                    size="sm"
                                                    onClick={() =>
                                                        router.post(
                                                            `/system/users/${user.id}/approve`,
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    Approve
                                                </Button>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
                <PaginationControls
                    page={users}
                    url="/system/users"
                    label="users"
                    params={{ search: filters.search }}
                />
            </div>
            {selected && (
                <EditUser
                    key={selected.id}
                    user={selected}
                    roles={roles}
                    close={() => setSelected(null)}
                />
            )}
            {creating && (
                <NewUser roles={roles} close={() => setCreating(false)} />
            )}
        </>
    );
}

Users.layout = {
    breadcrumbs: [
        { title: 'Users, roles & permissions', href: '/system/users' },
    ],
};
