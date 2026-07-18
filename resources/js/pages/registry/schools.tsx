import { Head, router, useForm } from '@inertiajs/react';
import { Plus, School as SchoolIcon } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
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
import {
    archive,
    destroy,
    index,
    restore,
    store,
    update,
} from '@/routes/schools';

type School = {
    id: number;
    district_id: number;
    name: string;
    school_id_code: string;
    level: string;
    address: string | null;
    active: boolean;
    district: { id: number; name: string };
};

type DistrictOption = {
    id: number;
    name: string;
};

type Props = {
    schools: School[];
    districts: DistrictOption[];
    canManage: boolean;
};

const levelLabels: Record<string, string> = {
    elementary: 'Elementary',
    secondary: 'Secondary',
    integrated: 'Integrated',
};

function SchoolFormDialog({
    school,
    districts,
    open,
    onOpenChange,
}: {
    school: School | null;
    districts: DistrictOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        district_id: school ? String(school.district_id) : '',
        name: school?.name ?? '',
        school_id_code: school?.school_id_code ?? '',
        level: school?.level ?? '',
        address: school?.address ?? '',
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

        if (school) {
            put(update(school.id).url, options);
        } else {
            post(store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {school ? 'Edit school' : 'Add school'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="school-district">District</Label>
                        <Select
                            value={data.district_id}
                            onValueChange={(value) =>
                                setData('district_id', value)
                            }
                        >
                            <SelectTrigger id="school-district">
                                <SelectValue placeholder="Select a district" />
                            </SelectTrigger>
                            <SelectContent>
                                {districts.map((district) => (
                                    <SelectItem
                                        key={district.id}
                                        value={String(district.id)}
                                    >
                                        {district.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.district_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="school-name">Name</Label>
                        <Input
                            id="school-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="school-code">School ID</Label>
                        <Input
                            id="school-code"
                            value={data.school_id_code}
                            onChange={(e) =>
                                setData('school_id_code', e.target.value)
                            }
                        />
                        <InputError message={errors.school_id_code} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="school-level">Level</Label>
                        <Select
                            value={data.level}
                            onValueChange={(value) => setData('level', value)}
                        >
                            <SelectTrigger id="school-level">
                                <SelectValue placeholder="Select a level" />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(levelLabels).map(
                                    ([value, label]) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ),
                                )}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.level} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="school-address">
                            Address (optional)
                        </Label>
                        <Input
                            id="school-address"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                        />
                        <InputError message={errors.address} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {school ? 'Save changes' : 'Create school'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Schools({ schools, districts, canManage }: Props) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<School | null>(null);

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (school: School) => {
        setEditing(school);
        setFormOpen(true);
    };

    return (
        <>
            <Head title="Schools" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Schools"
                    description="Schools registered under the division's districts."
                    actions={
                        canManage && (
                            <Button onClick={openCreate}>
                                <Plus />
                                Add school
                            </Button>
                        )
                    }
                />

                {schools.length === 0 ? (
                    <EmptyState
                        icon={SchoolIcon}
                        title="No schools yet"
                        description="Schools registered for the division will appear here."
                        action={
                            canManage && (
                                <Button onClick={openCreate}>Add school</Button>
                            )
                        }
                    />
                ) : (
                    <div className="rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>School ID</TableHead>
                                    <TableHead>District</TableHead>
                                    <TableHead>Level</TableHead>
                                    <TableHead>Status</TableHead>
                                    {canManage && (
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {schools.map((school) => (
                                    <TableRow key={school.id}>
                                        <TableCell className="font-medium">
                                            {school.name}
                                        </TableCell>
                                        <TableCell>
                                            {school.school_id_code}
                                        </TableCell>
                                        <TableCell>
                                            {school.district.name}
                                        </TableCell>
                                        <TableCell>
                                            {levelLabels[school.level] ??
                                                school.level}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    school.active
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {school.active
                                                    ? 'Active'
                                                    : 'Archived'}
                                            </Badge>
                                        </TableCell>
                                        {canManage && (
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            openEdit(school)
                                                        }
                                                    >
                                                        Edit
                                                    </Button>
                                                    <ConfirmDialog
                                                        trigger={
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                            >
                                                                {school.active
                                                                    ? 'Archive'
                                                                    : 'Restore'}
                                                            </Button>
                                                        }
                                                        title={
                                                            school.active
                                                                ? 'Archive school?'
                                                                : 'Restore school?'
                                                        }
                                                        description={
                                                            school.active
                                                                ? 'Archived schools stay in records but are hidden from new registrations.'
                                                                : 'The school becomes available for registrations again.'
                                                        }
                                                        confirmLabel={
                                                            school.active
                                                                ? 'Archive'
                                                                : 'Restore'
                                                        }
                                                        onConfirm={() =>
                                                            router.patch(
                                                                school.active
                                                                    ? archive(
                                                                          school.id,
                                                                      ).url
                                                                    : restore(
                                                                          school.id,
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
                                                                variant="destructive"
                                                                size="sm"
                                                            >
                                                                Delete
                                                            </Button>
                                                        }
                                                        title="Delete school?"
                                                        description="This permanently removes the school from the registry."
                                                        confirmLabel="Delete"
                                                        destructive
                                                        onConfirm={() =>
                                                            router.delete(
                                                                destroy(
                                                                    school.id,
                                                                ).url,
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    />
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

            <SchoolFormDialog
                key={editing?.id ?? 'create'}
                school={editing}
                districts={districts}
                open={formOpen}
                onOpenChange={setFormOpen}
            />
        </>
    );
}

Schools.layout = {
    breadcrumbs: [
        {
            title: 'Schools',
            href: index(),
        },
    ],
};
