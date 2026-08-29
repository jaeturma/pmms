import { Head, router, useForm } from '@inertiajs/react';
import { KeyRound, Mail, Search, Send } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { PaginationControls } from '@/components/pagination-controls';
import type { Paginated } from '@/components/pagination-controls';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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

type Provision = {
    id: number;
    person: string;
    email: string | null;
    suggested_username: string;
    target_role: string;
    status: string;
    reason: string | null;
    invited_at: string | null;
    activated_at: string | null;
    assignments: string;
    must_change_password: boolean;
    disabled: boolean;
    has_user: boolean;
};

type Option = { value: string; label: string };

function InviteDialog({
    provision,
    roles,
    onClose,
}: {
    provision: Provision | null;
    roles: Option[];
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors } = useForm({
        email: provision?.email ?? '',
        target_role: provision?.target_role ?? '',
    });

    if (!provision) {
        return null;
    }

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(`/account-provisions/${provision.id}/invite`, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Invite {provision.person}</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="provision-email">
                            Real email address
                        </Label>
                        <Input
                            id="provision-email"
                            type="email"
                            value={data.email}
                            onChange={(event) =>
                                setData('email', event.target.value)
                            }
                            required
                            autoFocus
                        />
                        <InputError message={errors.email} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="provision-role">Application role</Label>
                        <Select
                            value={data.target_role}
                            onValueChange={(value) =>
                                setData('target_role', value)
                            }
                        >
                            <SelectTrigger id="provision-role">
                                <SelectValue placeholder="Select a role" />
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
                        <InputError message={errors.target_role} />
                    </div>
                    <p className="text-sm text-muted-foreground">
                        Assigned sports:{' '}
                        {provision.assignments || 'No sport listed'}
                    </p>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={onClose}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Send className="size-4" />
                            {provision.status === 'invited'
                                ? 'Resend invitation'
                                : 'Send invitation'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function AccountProvisions({
    provisions,
    filters,
    roleOptions,
    canResetPasswords,
}: {
    provisions: Paginated<Provision>;
    filters: { search: string; status: string };
    roleOptions: Option[];
    canResetPasswords: boolean;
}) {
    const [selected, setSelected] = useState<Provision | null>(null);
    const [search, setSearch] = useState(filters.search);

    const applyFilters = (event: FormEvent) => {
        event.preventDefault();
        router.get(
            '/account-provisions',
            { search, status: filters.status },
            { preserveState: true },
        );
    };

    return (
        <>
            <Head title="Account provisions" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Account provisions"
                    description="Invite imported DdOPAA personnel to activate their assigned system accounts."
                />
                <div className="flex flex-wrap gap-3">
                    <form
                        onSubmit={applyFilters}
                        className="flex min-w-72 flex-1 gap-2"
                    >
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search person"
                        />
                        <Button type="submit" variant="outline">
                            <Search className="size-4" />
                            Search
                        </Button>
                    </form>
                    <Select
                        value={filters.status || 'all'}
                        onValueChange={(status) =>
                            router.get(
                                '/account-provisions',
                                {
                                    search,
                                    status: status === 'all' ? '' : status,
                                },
                                { preserveState: true },
                            )
                        }
                    >
                        <SelectTrigger className="w-44">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="provisioned">
                                Provisioned
                            </SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="invited">Invited</SelectItem>
                            <SelectItem value="activated">Activated</SelectItem>
                            <SelectItem value="failed">Unresolved</SelectItem>
                            <SelectItem value="disabled">Disabled</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div className="overflow-x-auto rounded-[5px] border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Person</TableHead>
                                <TableHead>Assignment</TableHead>
                                <TableHead>Role</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Action
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {provisions.data.map((provision) => (
                                <TableRow key={provision.id}>
                                    <TableCell>
                                        <p className="font-medium">
                                            {provision.person}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {provision.suggested_username}
                                        </p>
                                    </TableCell>
                                    <TableCell>
                                        {provision.assignments || '—'}
                                    </TableCell>
                                    <TableCell>
                                        {roleOptions.find(
                                            (role) =>
                                                role.value ===
                                                provision.target_role,
                                        )?.label ?? provision.target_role}
                                    </TableCell>
                                    <TableCell>
                                        {provision.email ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                provision.status === 'activated'
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {provision.status}
                                        </Badge>
                                        {provision.must_change_password && (
                                            <p className="mt-1 text-xs text-amber-700 dark:text-amber-400">
                                                Password change required
                                            </p>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            {!provision.has_user &&
                                                provision.status !==
                                                    'activated' && (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() =>
                                                            setSelected(
                                                                provision,
                                                            )
                                                        }
                                                    >
                                                        <Mail className="size-4" />
                                                        {provision.status ===
                                                        'invited'
                                                            ? 'Resend'
                                                            : 'Invite'}
                                                    </Button>
                                                )}
                                            {canResetPasswords &&
                                                provision.has_user && (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => {
                                                            if (
                                                                window.confirm(
                                                                    `Reset ${provision.person}'s password to the configured initial password?`,
                                                                )
                                                            ) {
                                                                router.post(
                                                                    `/account-provisions/${provision.id}/reset-password`,
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
                                                )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
                <PaginationControls
                    page={provisions}
                    url="/account-provisions"
                    label="account provisions"
                    params={{ search: filters.search, status: filters.status }}
                />
            </div>
            <InviteDialog
                key={selected?.id ?? 'none'}
                provision={selected}
                roles={roleOptions}
                onClose={() => setSelected(null)}
            />
        </>
    );
}

AccountProvisions.layout = {
    breadcrumbs: [{ title: 'Account provisions', href: '/account-provisions' }],
};
