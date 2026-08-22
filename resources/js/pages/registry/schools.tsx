import { Head, Link, router, useForm } from '@inertiajs/react';
import { BarChart3, Plus, School as SchoolIcon } from 'lucide-react';
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
import { participation } from '@/routes/reports';
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
    district_id: number | null;
    school_district_id: number | null;
    name: string;
    school_id_code: string;
    school_type: 'Public' | 'Private' | null;
    level: string | null;
    address: string | null;
    active: boolean;
    district: { id: number; name: string } | null;
    school_district: { id: number; name: string } | null;
};

type DistrictOption = {
    id: number;
    name: string;
};

type SchoolDistrictOption = {
    id: number;
    district_id: number;
    name: string;
};

type Props = {
    schools: Paginated<School>;
    filters: { search: string; setup: string; type: string };
    districts: DistrictOption[];
    schoolDistricts: SchoolDistrictOption[];
    canManage: boolean;
};

const NO_SCHOOL_TYPE = 'none';
const NO_LEVEL = 'none';

const levelLabels: Record<string, string> = {
    elementary: 'Elementary',
    secondary: 'Secondary',
    integrated: 'Integrated',
};

function SchoolFormDialog({
    school,
    districts,
    schoolDistricts,
    open,
    onOpenChange,
}: {
    school: School | null;
    districts: DistrictOption[];
    schoolDistricts: SchoolDistrictOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        district_id: school?.district_id ? String(school.district_id) : '',
        school_district_id: school?.school_district_id
            ? String(school.school_district_id)
            : '',
        name: school?.name ?? '',
        school_id_code: school?.school_id_code ?? '',
        school_type: school?.school_type ?? '',
        level: school?.level ?? '',
        address: school?.address ?? '',
    });

    const availableSchoolDistricts = schoolDistricts.filter(
        (option) => String(option.district_id) === data.district_id,
    );

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
                        <Label htmlFor="school-district">Municipality</Label>
                        <Select
                            value={data.district_id}
                            onValueChange={(value) => {
                                setData((current) => ({
                                    ...current,
                                    district_id: value,
                                    school_district_id: schoolDistricts.some(
                                        (option) =>
                                            String(option.district_id) ===
                                                value &&
                                            String(option.id) ===
                                                current.school_district_id,
                                    )
                                        ? current.school_district_id
                                        : '',
                                }));
                            }}
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
                        <Label htmlFor="school-type">School type</Label>
                        <Select
                            value={data.school_type || NO_SCHOOL_TYPE}
                            onValueChange={(value) =>
                                setData(
                                    'school_type',
                                    value === NO_SCHOOL_TYPE ? '' : value,
                                )
                            }
                        >
                            <SelectTrigger id="school-type">
                                <SelectValue placeholder="Select a type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={NO_SCHOOL_TYPE}>Unclassified</SelectItem>
                                <SelectItem value="Public">Public</SelectItem>
                                <SelectItem value="Private">Private</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.school_type} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="school-school-district">
                            School district
                        </Label>
                        <Select
                            value={data.school_district_id}
                            onValueChange={(value) =>
                                setData('school_district_id', value)
                            }
                            disabled={availableSchoolDistricts.length === 0}
                        >
                            <SelectTrigger id="school-school-district">
                                <SelectValue placeholder="None" />
                            </SelectTrigger>
                            <SelectContent>
                                {availableSchoolDistricts.map((option) => (
                                    <SelectItem
                                        key={option.id}
                                        value={String(option.id)}
                                    >
                                        {option.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.school_district_id} />
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
                            value={data.level || NO_LEVEL}
                            onValueChange={(value) =>
                                setData('level', value === NO_LEVEL ? '' : value)
                            }
                        >
                            <SelectTrigger id="school-level">
                                <SelectValue placeholder="Select a level" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={NO_LEVEL}>Unclassified</SelectItem>
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

export default function Schools({
    schools,
    filters,
    districts,
    schoolDistricts,
    canManage,
}: Props) {
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
                        <>
                            <Button variant="outline" asChild>
                                <Link href={participation()}>
                                    <BarChart3 />
                                    Participation
                                </Link>
                            </Button>
                            {canManage && (
                                <Button onClick={openCreate}>
                                    <Plus />
                                    Add school
                                </Button>
                            )}
                        </>
                    }
                />

                <SearchBar
                    initial={filters.search}
                    placeholder="Search schools"
                    url={index().url}
                    extraParams={{ setup: filters.setup, type: filters.type }}
                />

                <div className="flex flex-wrap gap-3">
                    <Select
                        value={filters.setup || 'all'}
                        onValueChange={(setup) =>
                            router.get(index().url, {
                                ...filters,
                                setup: setup === 'all' ? '' : setup,
                            }, { preserveState: true })
                        }
                    >
                        <SelectTrigger className="w-48"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All assignments</SelectItem>
                            <SelectItem value="assigned">Assigned</SelectItem>
                            <SelectItem value="unassigned">Unassigned</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.type || 'all'}
                        onValueChange={(type) =>
                            router.get(index().url, {
                                ...filters,
                                type: type === 'all' ? '' : type,
                            }, { preserveState: true })
                        }
                    >
                        <SelectTrigger className="w-40"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All types</SelectItem>
                            <SelectItem value="Public">Public</SelectItem>
                            <SelectItem value="Private">Private</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {schools.data.length === 0 ? (
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
                    <div className="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>School ID</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Municipality</TableHead>
                                    <TableHead>School district</TableHead>
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
                                {schools.data.map((school) => (
                                    <TableRow key={school.id}>
                                        <TableCell className="font-medium">
                                            {school.name}
                                        </TableCell>
                                        <TableCell>
                                            {school.school_id_code}
                                        </TableCell>
                                        <TableCell>{school.school_type ?? 'Unclassified'}</TableCell>
                                        <TableCell>
                                            {school.district?.name ?? 'Unassigned'}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {school.school_district?.name ??
                                                '—'}
                                        </TableCell>
                                        <TableCell>
                                            {(school.level && levelLabels[school.level]) ??
                                                school.level ?? 'Unclassified'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    school.active
                                                        ? 'success'
                                                        : 'outline'
                                                }
                                            >
                                                {school.active
                                                    ? 'Active'
                                                    : 'Archived'}
                                            </Badge>
                                            {(school.district_id === null ||
                                                school.school_district_id === null) && (
                                                <p className="mt-1 text-xs text-amber-700 dark:text-amber-400">
                                                    Needs Municipality/District Assignment
                                                </p>
                                            )}
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

                <PaginationControls
                    page={schools}
                    url={index().url}
                    label="schools"
                    params={filters}
                />
            </div>

            <SchoolFormDialog
                key={editing?.id ?? 'create'}
                school={editing}
                districts={districts}
                schoolDistricts={schoolDistricts}
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
