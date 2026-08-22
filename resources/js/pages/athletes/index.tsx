import { Head, Link, router, useForm } from '@inertiajs/react';
import { Contact, Pencil, Plus, RotateCcw, Save, UserPlus } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { PaginationControls } from '@/components/pagination-controls';
import type { Paginated } from '@/components/pagination-controls';
import { SearchBar } from '@/components/search-bar';
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
import { destroy, index, show, store, update } from '@/routes/athletes';

type AthleteRow = {
    id: number;
    name: string;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    name_extension: string | null;
    sex: string;
    birthdate: string;
    lrn: string;
    sex_label: string;
    age: number;
    grade_level: number;
    school: string;
    district: string;
    delegation: string;
    sports: string;
    accreditation_status: string;
    can_update: boolean;
    can_delete: boolean;
};

type DelegationOption = {
    id: number;
    label: string;
};

type SchoolOption = {
    id: number;
    name: string;
    district: string;
    school_district_id: number | null;
    school_district: string;
};

type Props = {
    athletes: Paginated<AthleteRow>;
    filters: { search: string };
    delegationOptions: DelegationOption[];
    schoolOptionsByDelegation: Record<number, SchoolOption[]>;
    fixedDelegationId: number | null;
};

function AthleteFormDialog({
    delegationOptions,
    schoolOptionsByDelegation,
    fixedDelegationId,
    open,
    onOpenChange,
}: {
    delegationOptions: DelegationOption[];
    schoolOptionsByDelegation: Record<number, SchoolOption[]>;
    fixedDelegationId: number | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        delegation_id: string;
        school_id: string;
        first_name: string;
        middle_name: string;
        last_name: string;
        name_extension: string;
        sex: string;
        birthdate: string;
        lrn: string;
        grade_level: string;
        photo: File | null;
        sports_photo: File | null;
        school_id_document: File | null;
        birth_certificate: File | null;
        report_card: File | null;
    }>({
        delegation_id:
            fixedDelegationId === null ? '' : String(fixedDelegationId),
        school_id: '',
        first_name: '',
        middle_name: '',
        last_name: '',
        name_extension: '',
        sex: '',
        birthdate: '',
        lrn: '',
        grade_level: '',
        photo: null,
        sports_photo: null,
        school_id_document: null,
        birth_certificate: null,
        report_card: null,
    });
    const [selectedDistrict, setSelectedDistrict] = useState('');

    const allSchoolOptions = data.delegation_id
        ? (schoolOptionsByDelegation[Number(data.delegation_id)] ?? [])
        : [];
    const districtOptions = Array.from(
        new Map(
            allSchoolOptions.map((school) => [
                school.school_district_id === null
                    ? `municipality:${school.district}`
                    : String(school.school_district_id),
                school.school_district,
            ]),
        ),
    );
    const schoolOptions = selectedDistrict
        ? allSchoolOptions.filter((school) =>
              selectedDistrict.startsWith('municipality:')
                  ? school.school_district_id === null
                  : String(school.school_district_id) === selectedDistrict,
          )
        : [];

    const selectDelegation = (value: string) => {
        const options = schoolOptionsByDelegation[Number(value)] ?? [];
        const districts = new Set(
            options.map((school) =>
                school.school_district_id === null
                    ? `municipality:${school.district}`
                    : String(school.school_district_id),
            ),
        );
        const district = districts.size === 1 ? [...districts][0] : '';
        setSelectedDistrict(district);
        setData((current) => ({
            ...current,
            delegation_id: value,
            school_id: options.length === 1 ? String(options[0].id) : '',
        }));
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(store().url, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setSelectedDistrict('');
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[96vh] overflow-y-auto sm:max-w-6xl">
                <DialogHeader>
                    <DialogTitle>Register athlete</DialogTitle>
                </DialogHeader>
                {delegationOptions.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No delegation is currently open for athlete
                        registration.
                    </p>
                ) : (
                    <form
                        onSubmit={submit}
                        className="grid gap-4 lg:grid-cols-3"
                    >
                        {fixedDelegationId === null && (
                            <div className="space-y-2">
                                <Label htmlFor="athlete-delegation">
                                    Delegation
                                </Label>
                                <Select
                                    value={data.delegation_id}
                                    onValueChange={selectDelegation}
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
                        )}
                        {data.delegation_id && (
                            <div className="space-y-2 lg:col-start-1">
                                <Label htmlFor="athlete-district">
                                    District
                                </Label>
                                <Select
                                    value={selectedDistrict}
                                    onValueChange={(value) => {
                                        setSelectedDistrict(value);
                                        setData('school_id', '');
                                    }}
                                >
                                    <SelectTrigger id="athlete-district">
                                        <SelectValue placeholder="Select a district" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {districtOptions.map(
                                            ([value, label]) => (
                                                <SelectItem
                                                    key={value}
                                                    value={value}
                                                >
                                                    {label}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
                        {data.delegation_id && (
                            <div className="space-y-2">
                                <Label htmlFor="athlete-school">
                                    Home school
                                </Label>
                                <Select
                                    value={data.school_id}
                                    onValueChange={(value) =>
                                        setData('school_id', value)
                                    }
                                    disabled={!selectedDistrict}
                                >
                                    <SelectTrigger id="athlete-school">
                                        <SelectValue
                                            placeholder={
                                                selectedDistrict
                                                    ? 'Select a school'
                                                    : 'Select a district first'
                                            }
                                        />
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
                        )}
                        <div className="space-y-2">
                            <Label htmlFor="athlete-lrn">LRN (12 digits)</Label>
                            <Input
                                id="athlete-lrn"
                                value={data.lrn}
                                inputMode="numeric"
                                maxLength={12}
                                onChange={(e) => setData('lrn', e.target.value)}
                            />
                            <InputError message={errors.lrn} />
                        </div>
                        <div className="grid gap-4 lg:col-span-3 lg:grid-cols-3">
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
                            <div className="grid grid-cols-[1fr_7rem] gap-3">
                                <div className="space-y-2">
                                    <Label htmlFor="athlete-middle">
                                        Middle name (optional)
                                    </Label>
                                    <Input
                                        id="athlete-middle"
                                        value={data.middle_name}
                                        onChange={(e) =>
                                            setData(
                                                'middle_name',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={errors.middle_name} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="athlete-extension">
                                        Extension
                                    </Label>
                                    <Select
                                        value={data.name_extension}
                                        onValueChange={(value) =>
                                            setData('name_extension', value)
                                        }
                                    >
                                        <SelectTrigger id="athlete-extension">
                                            <SelectValue placeholder="None" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {['Jr.', 'Sr.', 'II', 'III'].map(
                                                (suffix) => (
                                                    <SelectItem
                                                        key={suffix}
                                                        value={suffix}
                                                    >
                                                        {suffix}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.name_extension}
                                    />
                                </div>
                            </div>
                        </div>
                        <div className="space-y-3 rounded-lg border p-4 lg:col-span-3">
                            <div>
                                <p className="text-sm font-medium">
                                    Accreditation documents
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    Attach PDF, JPG, or PNG files (up to 10 MB
                                    each). They will be submitted for
                                    eligibility review.
                                </p>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-3">
                                {(
                                    [
                                        ['school_id_document', 'School ID'],
                                        [
                                            'birth_certificate',
                                            'Birth certificate',
                                        ],
                                        [
                                            'report_card',
                                            'Report card / grade proof',
                                        ],
                                    ] as const
                                ).map(([field, label]) => (
                                    <div key={field} className="space-y-2">
                                        <Label htmlFor={`athlete-${field}`}>
                                            {label}
                                        </Label>
                                        <Input
                                            id={`athlete-${field}`}
                                            type="file"
                                            accept=".pdf,image/jpeg,image/png"
                                            onChange={(e) =>
                                                setData(
                                                    field,
                                                    e.target.files?.[0] ?? null,
                                                )
                                            }
                                        />
                                        <InputError message={errors[field]} />
                                    </div>
                                ))}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="athlete-grade">Grade level</Label>
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
                        <div className="space-y-2">
                            <Label htmlFor="athlete-sex">Sex</Label>
                            <Select
                                value={data.sex}
                                onValueChange={(value) => setData('sex', value)}
                            >
                                <SelectTrigger id="athlete-sex">
                                    <SelectValue placeholder="Select" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="male">Male</SelectItem>
                                    <SelectItem value="female">
                                        Female
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.sex} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="athlete-birthdate">Birthdate</Label>
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
                        <div className="grid grid-cols-2 gap-4 lg:col-span-3">
                            <div className="space-y-2">
                                <Label htmlFor="athlete-photo">
                                    Profile photo (optional)
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
                            <div className="space-y-2">
                                <Label htmlFor="athlete-sports-photo">
                                    Sports photo (optional)
                                </Label>
                                <Input
                                    id="athlete-sports-photo"
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) =>
                                        setData(
                                            'sports_photo',
                                            e.target.files?.[0] ?? null,
                                        )
                                    }
                                />
                                <InputError message={errors.sports_photo} />
                            </div>
                        </div>
                        <DialogFooter className="lg:col-span-3">
                            <Button
                                type="reset"
                                variant="secondary"
                                onClick={() => {
                                    reset();
                                    setSelectedDistrict('');
                                }}
                                disabled={processing}
                            >
                                <RotateCcw className="size-4" />
                                Reset
                            </Button>
                            <Button type="submit" disabled={processing}>
                                <UserPlus className="size-4" />
                                Register athlete
                            </Button>
                        </DialogFooter>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}

function EditAthleteDialog({
    athlete,
    onClose,
}: {
    athlete: AthleteRow | null;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        first_name: athlete?.first_name ?? '',
        middle_name: athlete?.middle_name ?? '',
        last_name: athlete?.last_name ?? '',
        name_extension: athlete?.name_extension ?? '',
        sex: athlete?.sex ?? '',
        birthdate: athlete?.birthdate ?? '',
        lrn: athlete?.lrn ?? '',
        grade_level: String(athlete?.grade_level ?? ''),
        photo: null as File | null,
        sports_photo: null as File | null,
        school_id_document: null as File | null,
        birth_certificate: null as File | null,
        report_card: null as File | null,
        _method: 'put',
    });

    if (!athlete) return null;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(update(athlete.id).url, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[96vh] overflow-y-auto sm:max-w-6xl">
                <DialogHeader>
                    <DialogTitle>Edit athlete</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-4 lg:grid-cols-3">
                    <div className="space-y-2">
                        <Label>Delegation</Label>
                        <Input value={athlete.delegation} disabled />
                    </div>
                    <div className="space-y-2">
                        <Label>District</Label>
                        <Input value={athlete.district} disabled />
                    </div>
                    <div className="space-y-2">
                        <Label>Home school</Label>
                        <Input value={athlete.school} disabled />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="edit-athlete-lrn">
                            LRN (12 digits)
                        </Label>
                        <Input
                            id="edit-athlete-lrn"
                            value={data.lrn}
                            inputMode="numeric"
                            maxLength={12}
                            onChange={(e) => setData('lrn', e.target.value)}
                        />
                        <InputError message={errors.lrn} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="edit-athlete-last">Last name</Label>
                        <Input
                            id="edit-athlete-last"
                            value={data.last_name}
                            onChange={(e) =>
                                setData('last_name', e.target.value)
                            }
                        />
                        <InputError message={errors.last_name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="edit-athlete-first">First name</Label>
                        <Input
                            id="edit-athlete-first"
                            value={data.first_name}
                            onChange={(e) =>
                                setData('first_name', e.target.value)
                            }
                        />
                        <InputError message={errors.first_name} />
                    </div>
                    <div className="grid grid-cols-[1fr_7rem] gap-3">
                        <div className="space-y-2">
                            <Label htmlFor="edit-athlete-middle">
                                Middle name (optional)
                            </Label>
                            <Input
                                id="edit-athlete-middle"
                                value={data.middle_name}
                                onChange={(e) =>
                                    setData('middle_name', e.target.value)
                                }
                            />
                            <InputError message={errors.middle_name} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="edit-athlete-extension">
                                Extension
                            </Label>
                            <Select
                                value={data.name_extension}
                                onValueChange={(value) =>
                                    setData('name_extension', value)
                                }
                            >
                                <SelectTrigger id="edit-athlete-extension">
                                    <SelectValue placeholder="None" />
                                </SelectTrigger>
                                <SelectContent>
                                    {['Jr.', 'Sr.', 'II', 'III'].map(
                                        (suffix) => (
                                            <SelectItem
                                                key={suffix}
                                                value={suffix}
                                            >
                                                {suffix}
                                            </SelectItem>
                                        ),
                                    )}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.name_extension} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="edit-athlete-grade">Grade level</Label>
                        <Select
                            value={data.grade_level}
                            onValueChange={(value) =>
                                setData('grade_level', value)
                            }
                        >
                            <SelectTrigger id="edit-athlete-grade">
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
                    <div className="space-y-2">
                        <Label htmlFor="edit-athlete-sex">Sex</Label>
                        <Select
                            value={data.sex}
                            onValueChange={(value) => setData('sex', value)}
                        >
                            <SelectTrigger id="edit-athlete-sex">
                                <SelectValue placeholder="Select" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="male">Male</SelectItem>
                                <SelectItem value="female">Female</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.sex} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="edit-athlete-birthdate">
                            Birthdate
                        </Label>
                        <Input
                            id="edit-athlete-birthdate"
                            type="date"
                            value={data.birthdate}
                            onChange={(e) =>
                                setData('birthdate', e.target.value)
                            }
                        />
                        <InputError message={errors.birthdate} />
                    </div>
                    <div className="space-y-3 rounded-[5px] border p-4 lg:col-span-3">
                        <div>
                            <p className="text-sm font-medium">
                                Accreditation documents
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Upload a file only when replacing or adding a
                                document.
                            </p>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-3">
                            {(
                                [
                                    ['school_id_document', 'School ID'],
                                    ['birth_certificate', 'Birth certificate'],
                                    [
                                        'report_card',
                                        'Report card / grade proof',
                                    ],
                                ] as const
                            ).map(([field, label]) => (
                                <div key={field} className="space-y-2">
                                    <Label htmlFor={`edit-athlete-${field}`}>
                                        {label}
                                    </Label>
                                    <Input
                                        id={`edit-athlete-${field}`}
                                        type="file"
                                        accept=".pdf,image/jpeg,image/png"
                                        onChange={(e) =>
                                            setData(
                                                field,
                                                e.target.files?.[0] ?? null,
                                            )
                                        }
                                    />
                                    <InputError message={errors[field]} />
                                </div>
                            ))}
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4 lg:col-span-3">
                        <div className="space-y-2">
                            <Label htmlFor="edit-athlete-photo">
                                Replace profile photo (optional)
                            </Label>
                            <Input
                                id="edit-athlete-photo"
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
                        <div className="space-y-2">
                            <Label htmlFor="edit-athlete-sports-photo">
                                Replace sports photo (optional)
                            </Label>
                            <Input
                                id="edit-athlete-sports-photo"
                                type="file"
                                accept="image/*"
                                onChange={(e) =>
                                    setData(
                                        'sports_photo',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            <InputError message={errors.sports_photo} />
                        </div>
                    </div>
                    <DialogFooter className="lg:col-span-3">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => reset()}
                            disabled={processing}
                        >
                            <RotateCcw className="size-4" />
                            Reset
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="size-4" />
                            Save changes
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Athletes({
    athletes,
    filters,
    delegationOptions,
    schoolOptionsByDelegation,
    fixedDelegationId,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editingAthlete, setEditingAthlete] = useState<AthleteRow | null>(
        null,
    );

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

                <SearchBar
                    initial={filters.search}
                    placeholder="Search name or LRN"
                    url={index().url}
                />

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
                                        <TableHead>Sport</TableHead>
                                        <TableHead>Accreditation</TableHead>
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
                                                {athlete.sports || '—'}
                                            </TableCell>
                                            <TableCell>
                                                {athlete.accreditation_status}
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
                                                    {athlete.can_update && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                setEditingAthlete(
                                                                    athlete,
                                                                )
                                                            }
                                                        >
                                                            <Pencil />
                                                            Edit
                                                        </Button>
                                                    )}
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
                                                            description="This removes the athlete from the active roster while retaining the record for audit and recovery."
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

                        <PaginationControls
                            page={athletes}
                            url={index().url}
                            label="athletes"
                            params={
                                filters.search ? { search: filters.search } : {}
                            }
                        />
                    </>
                )}
            </div>

            <AthleteFormDialog
                delegationOptions={delegationOptions}
                schoolOptionsByDelegation={schoolOptionsByDelegation}
                fixedDelegationId={fixedDelegationId}
                open={createOpen}
                onOpenChange={setCreateOpen}
            />
            {editingAthlete && (
                <EditAthleteDialog
                    athlete={editingAthlete}
                    onClose={() => setEditingAthlete(null)}
                />
            )}
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
