import { Head, router, useForm } from '@inertiajs/react';
import { Plus, UserCog } from 'lucide-react';
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
    destroy,
    index,
    sports as sportsRoute,
    store,
    update,
} from '@/routes/personnel';

type PersonnelRow = {
    id: number;
    first_name: string;
    last_name: string;
    name: string;
    role: string;
    role_label: string;
    coaches: boolean;
    phone: string | null;
    email: string | null;
    sports: string[];
    sport_ids: number[];
    school: string;
    meet: string;
    photo_url: string | null;
    can_update: boolean;
    can_delete: boolean;
};

type Props = {
    personnel: Paginated<PersonnelRow>;
    filters: { search: string };
    delegationOptions: Array<{ id: number; label: string }>;
    sportOptions: Array<{ id: number; name: string }>;
};

const roleOptions: Array<{ value: string; label: string }> = [
    { value: 'coach', label: 'Coach' },
    { value: 'assistant_coach', label: 'Assistant Coach' },
    { value: 'chaperone', label: 'Chaperone' },
];

function PersonnelFormDialog({
    person,
    delegationOptions,
    open,
    onOpenChange,
}: {
    person: PersonnelRow | null;
    delegationOptions: Array<{ id: number; label: string }>;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        _method?: string;
        delegation_id: string;
        first_name: string;
        last_name: string;
        role: string;
        phone: string;
        email: string;
        photo: File | null;
    }>({
        ...(person ? { _method: 'put' } : {}),
        delegation_id: '',
        first_name: person?.first_name ?? '',
        last_name: person?.last_name ?? '',
        role: person?.role ?? '',
        phone: person?.phone ?? '',
        email: person?.email ?? '',
        photo: null,
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

        if (person) {
            post(update(person.id).url, options);
        } else {
            post(store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {person ? 'Edit personnel' : 'Register personnel'}
                    </DialogTitle>
                </DialogHeader>
                {!person && delegationOptions.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No delegation is currently open for personnel
                        registration.
                    </p>
                ) : (
                    <form onSubmit={submit} className="space-y-4">
                        {!person && (
                            <div className="space-y-2">
                                <Label htmlFor="personnel-delegation">
                                    Delegation
                                </Label>
                                <Select
                                    value={data.delegation_id}
                                    onValueChange={(value) =>
                                        setData('delegation_id', value)
                                    }
                                >
                                    <SelectTrigger id="personnel-delegation">
                                        <SelectValue placeholder="Select a delegation" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {delegationOptions.map((option) => (
                                            <SelectItem
                                                key={option.id}
                                                value={String(option.id)}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.delegation_id} />
                            </div>
                        )}
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="personnel-first">
                                    First name
                                </Label>
                                <Input
                                    id="personnel-first"
                                    value={data.first_name}
                                    onChange={(e) =>
                                        setData('first_name', e.target.value)
                                    }
                                />
                                <InputError message={errors.first_name} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="personnel-last">
                                    Last name
                                </Label>
                                <Input
                                    id="personnel-last"
                                    value={data.last_name}
                                    onChange={(e) =>
                                        setData('last_name', e.target.value)
                                    }
                                />
                                <InputError message={errors.last_name} />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="personnel-role">Role</Label>
                            <Select
                                value={data.role}
                                onValueChange={(value) =>
                                    setData('role', value)
                                }
                            >
                                <SelectTrigger id="personnel-role">
                                    <SelectValue placeholder="Select a role" />
                                </SelectTrigger>
                                <SelectContent>
                                    {roleOptions.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.role} />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="personnel-phone">
                                    Phone (optional)
                                </Label>
                                <Input
                                    id="personnel-phone"
                                    value={data.phone}
                                    onChange={(e) =>
                                        setData('phone', e.target.value)
                                    }
                                />
                                <InputError message={errors.phone} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="personnel-email">
                                    Email (optional)
                                </Label>
                                <Input
                                    id="personnel-email"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                />
                                <InputError message={errors.email} />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="personnel-photo">
                                Photo (optional)
                            </Label>
                            <Input
                                id="personnel-photo"
                                type="file"
                                accept="image/*"
                                onChange={(e) =>
                                    setData(
                                        'photo',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            <InputError message={errors.photo} />
                        </div>
                        <DialogFooter>
                            <Button type="submit" disabled={processing}>
                                {person ? 'Save changes' : 'Register'}
                            </Button>
                        </DialogFooter>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}

function SportsDialog({
    person,
    sportOptions,
    open,
    onOpenChange,
}: {
    person: PersonnelRow;
    sportOptions: Array<{ id: number; name: string }>;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [selected, setSelected] = useState<number[]>(person.sport_ids);
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
            sportsRoute(person.id).url,
            { sport_ids: selected },
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
                    <DialogTitle>Sports for {person.name}</DialogTitle>
                </DialogHeader>
                <div className="max-h-72 space-y-2 overflow-y-auto pr-2">
                    {sportOptions.map((sport) => (
                        <div key={sport.id} className="flex items-center gap-2">
                            <Checkbox
                                id={`sport-${sport.id}`}
                                checked={selected.includes(sport.id)}
                                onCheckedChange={(checked) =>
                                    toggle(sport.id, checked === true)
                                }
                            />
                            <Label
                                htmlFor={`sport-${sport.id}`}
                                className="font-normal"
                            >
                                {sport.name}
                            </Label>
                        </div>
                    ))}
                </div>
                <DialogFooter>
                    <Button onClick={save} disabled={processing}>
                        Save sports
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function PersonnelPage({
    personnel,
    filters,
    delegationOptions,
    sportOptions,
}: Props) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<PersonnelRow | null>(null);
    const [assigning, setAssigning] = useState<PersonnelRow | null>(null);

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (person: PersonnelRow) => {
        setEditing(person);
        setFormOpen(true);
    };

    return (
        <>
            <Head title="Personnel" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Personnel"
                    description="Coaches, assistant coaches, and chaperones per delegation."
                    actions={
                        delegationOptions.length > 0 && (
                            <Button onClick={openCreate}>
                                <Plus />
                                Register personnel
                            </Button>
                        )
                    }
                />

                <SearchBar
                    initial={filters.search}
                    placeholder="Search personnel"
                    url={index().url}
                />

                {personnel.data.length === 0 ? (
                    <EmptyState
                        icon={UserCog}
                        title="No personnel found"
                        description={
                            filters.search
                                ? 'No personnel match your search.'
                                : 'Registered coaches and chaperones will appear here.'
                        }
                    />
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-xl border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead>Sports</TableHead>
                                        <TableHead>School</TableHead>
                                        <TableHead>Meet</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {personnel.data.map((person) => (
                                        <TableRow key={person.id}>
                                            <TableCell className="font-medium">
                                                {person.name}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">
                                                    {person.role_label}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {person.coaches
                                                    ? person.sports.length > 0
                                                        ? person.sports.join(
                                                              ', ',
                                                          )
                                                        : '—'
                                                    : 'n/a'}
                                            </TableCell>
                                            <TableCell>
                                                {person.school}
                                            </TableCell>
                                            <TableCell>{person.meet}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    {person.can_update && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                openEdit(person)
                                                            }
                                                        >
                                                            Edit
                                                        </Button>
                                                    )}
                                                    {person.can_update &&
                                                        person.coaches && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    setAssigning(
                                                                        person,
                                                                    )
                                                                }
                                                            >
                                                                Sports
                                                            </Button>
                                                        )}
                                                    {person.can_delete && (
                                                        <ConfirmDialog
                                                            trigger={
                                                                <Button
                                                                    variant="destructive"
                                                                    size="sm"
                                                                >
                                                                    Remove
                                                                </Button>
                                                            }
                                                            title="Remove personnel?"
                                                            description="This permanently removes the record and photo."
                                                            confirmLabel="Remove"
                                                            destructive
                                                            onConfirm={() =>
                                                                router.delete(
                                                                    destroy(
                                                                        person.id,
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

                        <PaginationControls
                            page={personnel}
                            url={index().url}
                            label="personnel"
                            params={
                                filters.search ? { search: filters.search } : {}
                            }
                        />
                    </>
                )}
            </div>

            <PersonnelFormDialog
                key={editing?.id ?? 'create'}
                person={editing}
                delegationOptions={delegationOptions}
                open={formOpen}
                onOpenChange={setFormOpen}
            />

            {assigning && (
                <SportsDialog
                    key={assigning.id}
                    person={assigning}
                    sportOptions={sportOptions}
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

PersonnelPage.layout = {
    breadcrumbs: [
        {
            title: 'Personnel',
            href: index(),
        },
    ],
};
