import { Head, Link, router, useForm } from '@inertiajs/react';
import { Contact, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
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
import { destroy, index, show, store } from '@/routes/athletes';

type AthleteRow = {
    id: number;
    name: string;
    sex_label: string;
    age: number;
    grade_level: number;
    school: string;
    meet: string;
    can_update: boolean;
    can_delete: boolean;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
};

type DelegationOption = {
    id: number;
    label: string;
};

type Props = {
    athletes: Paginated<AthleteRow>;
    filters: { search: string };
    delegationOptions: DelegationOption[];
};

function AthleteFormDialog({
    delegationOptions,
    open,
    onOpenChange,
}: {
    delegationOptions: DelegationOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        delegation_id: string;
        first_name: string;
        last_name: string;
        sex: string;
        birthdate: string;
        lrn: string;
        grade_level: string;
        photo: File | null;
    }>({
        delegation_id: '',
        first_name: '',
        last_name: '',
        sex: '',
        birthdate: '',
        lrn: '',
        grade_level: '',
        photo: null,
    });

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
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Register athlete</DialogTitle>
                </DialogHeader>
                {delegationOptions.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No delegation is currently open for athlete
                        registration.
                    </p>
                ) : (
                    <form onSubmit={submit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="athlete-delegation">
                                Delegation
                            </Label>
                            <Select
                                value={data.delegation_id}
                                onValueChange={(value) =>
                                    setData('delegation_id', value)
                                }
                            >
                                <SelectTrigger id="athlete-delegation">
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
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="athlete-first">
                                    First name
                                </Label>
                                <Input
                                    id="athlete-first"
                                    value={data.first_name}
                                    onChange={(e) =>
                                        setData('first_name', e.target.value)
                                    }
                                />
                                <InputError message={errors.first_name} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="athlete-last">Last name</Label>
                                <Input
                                    id="athlete-last"
                                    value={data.last_name}
                                    onChange={(e) =>
                                        setData('last_name', e.target.value)
                                    }
                                />
                                <InputError message={errors.last_name} />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="athlete-sex">Sex</Label>
                                <Select
                                    value={data.sex}
                                    onValueChange={(value) =>
                                        setData('sex', value)
                                    }
                                >
                                    <SelectTrigger id="athlete-sex">
                                        <SelectValue placeholder="Select" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="male">
                                            Male
                                        </SelectItem>
                                        <SelectItem value="female">
                                            Female
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.sex} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="athlete-birthdate">
                                    Birthdate
                                </Label>
                                <Input
                                    id="athlete-birthdate"
                                    type="date"
                                    value={data.birthdate}
                                    onChange={(e) =>
                                        setData('birthdate', e.target.value)
                                    }
                                />
                                <InputError message={errors.birthdate} />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="athlete-lrn">
                                    LRN (12 digits)
                                </Label>
                                <Input
                                    id="athlete-lrn"
                                    value={data.lrn}
                                    onChange={(e) =>
                                        setData('lrn', e.target.value)
                                    }
                                />
                                <InputError message={errors.lrn} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="athlete-grade">
                                    Grade level
                                </Label>
                                <Select
                                    value={data.grade_level}
                                    onValueChange={(value) =>
                                        setData('grade_level', value)
                                    }
                                >
                                    <SelectTrigger id="athlete-grade">
                                        <SelectValue placeholder="Select" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Array.from(
                                            { length: 12 },
                                            (_, i) => i + 1,
                                        ).map((grade) => (
                                            <SelectItem
                                                key={grade}
                                                value={String(grade)}
                                            >
                                                Grade {grade}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.grade_level} />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="athlete-photo">
                                Photo (optional)
                            </Label>
                            <Input
                                id="athlete-photo"
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
                                Register athlete
                            </Button>
                        </DialogFooter>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}

export default function Athletes({
    athletes,
    filters,
    delegationOptions,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [search, setSearch] = useState(filters.search);

    const runSearch = (e: FormEvent) => {
        e.preventDefault();
        router.get(
            index().url,
            { search },
            { preserveState: true, preserveScroll: true },
        );
    };

    const goToPage = (page: number) => {
        router.get(
            index().url,
            { search: filters.search, page },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Athletes" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Athletes"
                    description="Registered athletes per delegation. Access is restricted and audited."
                    actions={
                        delegationOptions.length > 0 && (
                            <Button onClick={() => setCreateOpen(true)}>
                                <Plus />
                                Register athlete
                            </Button>
                        )
                    }
                />

                <form onSubmit={runSearch} className="flex max-w-sm gap-2">
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search name or LRN"
                        aria-label="Search athletes"
                    />
                    <Button type="submit" variant="outline">
                        <Search />
                        Search
                    </Button>
                </form>

                {athletes.data.length === 0 ? (
                    <EmptyState
                        icon={Contact}
                        title="No athletes found"
                        description={
                            filters.search
                                ? 'No athletes match your search.'
                                : 'Registered athletes will appear here.'
                        }
                    />
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-xl border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Sex</TableHead>
                                        <TableHead>Age</TableHead>
                                        <TableHead>Grade</TableHead>
                                        <TableHead>School</TableHead>
                                        <TableHead>Meet</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {athletes.data.map((athlete) => (
                                        <TableRow key={athlete.id}>
                                            <TableCell className="font-medium">
                                                {athlete.name}
                                            </TableCell>
                                            <TableCell>
                                                {athlete.sex_label}
                                            </TableCell>
                                            <TableCell>{athlete.age}</TableCell>
                                            <TableCell>
                                                {athlete.grade_level}
                                            </TableCell>
                                            <TableCell>
                                                {athlete.school}
                                            </TableCell>
                                            <TableCell>
                                                {athlete.meet}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={
                                                                show(athlete.id)
                                                                    .url
                                                            }
                                                        >
                                                            View
                                                        </Link>
                                                    </Button>
                                                    {athlete.can_delete && (
                                                        <ConfirmDialog
                                                            trigger={
                                                                <Button
                                                                    variant="destructive"
                                                                    size="sm"
                                                                >
                                                                    Remove
                                                                </Button>
                                                            }
                                                            title="Remove athlete?"
                                                            description="This permanently removes the athlete's record and photo."
                                                            confirmLabel="Remove"
                                                            destructive
                                                            onConfirm={() =>
                                                                router.delete(
                                                                    destroy(
                                                                        athlete.id,
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

                        {athletes.last_page > 1 && (
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Page {athletes.current_page} of{' '}
                                    {athletes.last_page} ({athletes.total}{' '}
                                    athletes)
                                </p>
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={athletes.current_page === 1}
                                        onClick={() =>
                                            goToPage(athletes.current_page - 1)
                                        }
                                    >
                                        Previous
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={
                                            athletes.current_page ===
                                            athletes.last_page
                                        }
                                        onClick={() =>
                                            goToPage(athletes.current_page + 1)
                                        }
                                    >
                                        Next
                                    </Button>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>

            <AthleteFormDialog
                delegationOptions={delegationOptions}
                open={createOpen}
                onOpenChange={setCreateOpen}
            />
        </>
    );
}

Athletes.layout = {
    breadcrumbs: [
        {
            title: 'Athletes',
            href: index(),
        },
    ],
};
