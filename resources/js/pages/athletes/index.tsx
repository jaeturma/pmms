import { Head, Link, router, useForm } from '@inertiajs/react';
import { Contact, Pencil, Plus, RotateCcw, Save, UserPlus } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { AthletePhotoInput } from '@/components/athlete-photo-input';
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
import { destroy, index, show, store, update } from '@/routes/athletes';

const athleteDivisions = [
    ['elementary', 'Elementary'],
    ['secondary', 'Secondary'],
    ['mixed', 'Mixed'],
    [
        'paragames_intellectual_disability',
        'Paragames Division Intellectual Disability',
    ],
    [
        'paragames_intellectual_disability_youth_15_below',
        'Intellectual Disability - Youth 15 below',
    ],
    [
        'paragames_intellectual_disability_junior_16_up',
        'Intellectual Disability - Junior 16 up',
    ],
    ['paragames_visually_impaired', 'Visually Impaired'],
    ['paragames_ortho', 'Ortho'],
    ['paragames_others', 'Others'],
] as const;

function DivisionSelect({
    id,
    value,
    onChange,
}: {
    id: string;
    value: string;
    onChange: (value: string) => void;
}) {
    return (
        <Select value={value} onValueChange={onChange}>
            <SelectTrigger id={id}>
                <SelectValue placeholder="Select division" />
            </SelectTrigger>
            <SelectContent>
                {athleteDivisions.map(([division, label]) => (
                    <SelectItem key={division} value={division}>
                        {label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

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
    age_division: string;
    school: string;
    district: string;
    delegation: string;
    coach: string | null;
    photo_url: string | null;
    sports: string;
    events: string;
    accreditation_status: string;
    eligibility_status: string;
    eligibility_state: 'eligible' | 'not_eligible' | 'under_review';
    can_update: boolean;
    can_delete: boolean;
    deletion_pending: boolean;
    can_confirm_deletion: boolean;
    can_cancel_deletion: boolean;
    deleted: boolean;
    deleted_at: string | null;
};

type DelegationOption = {
    id: number;
    label: string;
};

type SchoolOption = {
    id: number;
    name: string;
    school_id_code: string;
    district_id: number | null;
    district: string;
    school_district_id: number | null;
    school_district: string;
};

type CoachEventOption = {
    id: number;
    label: string;
    category: string;
    gender: string;
    grade_level: string;
};

type Props = {
    athletes: Paginated<AthleteRow>;
    filters: {
        search: string;
        municipality_id: number | null;
        school_district_id: number | null;
        school_id: number | null;
        sport_id: number | null;
        sex: string;
        accreditation: string;
        deleted: boolean;
        unassigned: boolean;
    };
    canViewDeleted: boolean;
    canViewUnassigned: boolean;
    delegationOptions: DelegationOption[];
    schoolOptionsByDelegation: Record<number, SchoolOption[]>;
    fixedDelegationId: number | null;
    fixedMunicipalityId: number | null;
    coachEventOptionsByDelegation: Record<number, CoachEventOption[]>;
    municipalities: Array<{ id: number; name: string }>;
    schoolDistricts: Array<{ id: number; district_id: number; name: string }>;
    filterSchools: Array<{
        id: number;
        district_id: number | null;
        school_district_id: number | null;
        name: string;
    }>;
    sports: Array<{ id: number; name: string }>;
};

function AthleteFormDialog({
    delegationOptions,
    schoolOptionsByDelegation,
    fixedDelegationId,
    fixedMunicipalityId,
    coachEventOptionsByDelegation,
    municipalities,
    schoolDistricts,
    open,
    onOpenChange,
}: {
    delegationOptions: DelegationOption[];
    schoolOptionsByDelegation: Record<number, SchoolOption[]>;
    fixedDelegationId: number | null;
    fixedMunicipalityId: number | null;
    coachEventOptionsByDelegation: Record<number, CoachEventOption[]>;
    municipalities: Array<{ id: number; name: string }>;
    schoolDistricts: Array<{ id: number; district_id: number; name: string }>;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, hasErrors, reset } =
        useForm<{
            delegation_id: string;
            school_id: string;
            district_id: string;
            school_district_id: string;
            event_id: string;
            first_name: string;
            middle_name: string;
            last_name: string;
            name_extension: string;
            sex: string;
            birthdate: string;
            lrn: string;
            grade_level: string;
            age_division: string;
            photo: File | null;
            sports_photo: File | null;
            athlete_history: File | null;
            form_10: File | null;
            form_10_page_2: File | null;
            birth_certificate: File | null;
            birth_certificate_page_2: File | null;
            parental_consent: File | null;
            medical_certificate: File | null;
        }>({
            delegation_id:
                fixedDelegationId === null ? '' : String(fixedDelegationId),
            school_id: '',
            district_id:
                fixedMunicipalityId === null ? '' : String(fixedMunicipalityId),
            school_district_id: '',
            event_id: '',
            first_name: '',
            middle_name: '',
            last_name: '',
            name_extension: 'None',
            sex: '',
            birthdate: '',
            lrn: '',
            grade_level: '',
            age_division: '',
            photo: null,
            sports_photo: null,
            athlete_history: null,
            form_10: null,
            form_10_page_2: null,
            birth_certificate: null,
            birth_certificate_page_2: null,
            parental_consent: null,
            medical_certificate: null,
        });
    const allSchoolOptions = data.delegation_id
        ? (schoolOptionsByDelegation[Number(data.delegation_id)] ?? [])
        : [];
    const availableSchoolDistricts = schoolDistricts.filter(
        (district) => district.district_id === Number(data.district_id),
    );

    const selectDelegation = (value: string) => {
        const options = schoolOptionsByDelegation[Number(value)] ?? [];
        setData((current) => ({
            ...current,
            delegation_id: value,
            school_id: options.length === 1 ? String(options[0].id) : '',
            event_id: '',
        }));
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(store().url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
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
                        {hasErrors && (
                            <div
                                role="alert"
                                className="rounded-md border border-destructive/40 bg-destructive/10 px-4 py-3 text-sm text-destructive lg:col-span-3"
                            >
                                <p className="font-medium">
                                    Athlete registration could not be submitted.
                                </p>
                                <ul className="mt-1 list-disc space-y-1 pl-5">
                                    {[...new Set(Object.values(errors))].map(
                                        (message) => (
                                            <li key={message}>{message}</li>
                                        ),
                                    )}
                                </ul>
                            </div>
                        )}
                        {fixedDelegationId === null && (
                            <div className="space-y-2 lg:col-span-3">
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
                        <div className="grid gap-4 sm:grid-cols-2 lg:col-span-3 lg:grid-cols-3">
                            <div className="space-y-2">
                                <Label htmlFor="athlete-lrn">
                                    LRN (12 digits) *
                                </Label>
                                <Input
                                    id="athlete-lrn"
                                    value={data.lrn}
                                    inputMode="numeric"
                                    maxLength={12}
                                    onChange={(e) =>
                                        setData('lrn', e.target.value)
                                    }
                                />
                                <InputError message={errors.lrn} />
                            </div>
                            {fixedDelegationId !== null && (
                                <div className="space-y-2 lg:col-span-3">
                                    <Label htmlFor="athlete-event">
                                        Coach sport and event *
                                    </Label>
                                    <Select
                                        value={data.event_id}
                                        onValueChange={(value) =>
                                            setData('event_id', value)
                                        }
                                    >
                                        <SelectTrigger id="athlete-event">
                                            <SelectValue placeholder="Select from your approved sports" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {(
                                                coachEventOptionsByDelegation[
                                                    Number(data.delegation_id)
                                                ] ?? []
                                            ).map((event) => (
                                                <SelectItem
                                                    key={event.id}
                                                    value={String(event.id)}
                                                >
                                                    <span className="flex flex-col py-1">
                                                        <span className="font-medium">
                                                            {event.label}
                                                        </span>
                                                        <span className="text-xs text-muted-foreground">
                                                            {event.category} ·{' '}
                                                            {event.gender} ·{' '}
                                                            {event.grade_level}
                                                        </span>
                                                    </span>
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.event_id} />
                                </div>
                            )}
                            {data.delegation_id && (
                                <>
                                    {fixedDelegationId !== null && (
                                        <div className="space-y-2">
                                            <Label>Municipality *</Label>
                                            <Select
                                                value={data.district_id}
                                                disabled
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Municipality" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {municipalities.map(
                                                        (municipality) => (
                                                            <SelectItem
                                                                key={
                                                                    municipality.id
                                                                }
                                                                value={String(
                                                                    municipality.id,
                                                                )}
                                                            >
                                                                {
                                                                    municipality.name
                                                                }
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={errors.district_id}
                                            />
                                        </div>
                                    )}
                                    {fixedDelegationId !== null && (
                                        <div className="space-y-2">
                                            <Label>School district *</Label>
                                            <Select
                                                value={data.school_district_id}
                                                onValueChange={(value) =>
                                                    setData(
                                                        'school_district_id',
                                                        value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select school district" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {availableSchoolDistricts.map(
                                                        (district) => (
                                                            <SelectItem
                                                                key={
                                                                    district.id
                                                                }
                                                                value={String(
                                                                    district.id,
                                                                )}
                                                            >
                                                                {district.name}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    errors.school_district_id
                                                }
                                            />
                                        </div>
                                    )}
                                    <div className="space-y-2">
                                        <Label htmlFor="athlete-school">
                                            School *
                                        </Label>
                                        <Select
                                            value={data.school_id}
                                            onValueChange={(value) =>
                                                setData('school_id', value)
                                            }
                                        >
                                            <SelectTrigger id="athlete-school">
                                                <SelectValue placeholder="Select a school" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {allSchoolOptions.map(
                                                    (school) => (
                                                        <SelectItem
                                                            key={school.id}
                                                            value={String(
                                                                school.id,
                                                            )}
                                                        >
                                                            {school.name} -{' '}
                                                            {
                                                                school.school_id_code
                                                            }
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.school_id}
                                        />
                                    </div>
                                </>
                            )}
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
                                        Middle name / N/A *
                                    </Label>
                                    <Input
                                        id="athlete-middle"
                                        value={data.middle_name}
                                        required
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
                                        Extension *
                                    </Label>
                                    <Select
                                        value={data.name_extension}
                                        onValueChange={(value) =>
                                            setData('name_extension', value)
                                        }
                                    >
                                        <SelectTrigger id="athlete-extension">
                                            <SelectValue placeholder="Select extension" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="None">
                                                None
                                            </SelectItem>
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
                                    Attach JPG or PNG files only. They will be
                                    submitted for eligibility review.
                                </p>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {(
                                    [
                                        ['athlete_history', 'Athlete Record'],
                                        ['form_10', 'School Form 10 - Page 1'],
                                        ['form_10_page_2', 'School Form 10 - Page 2'],
                                        [
                                            'birth_certificate',
                                            'PSA / Birth Certificate - Page 1',
                                        ],
                                        ['birth_certificate_page_2', 'PSA / Birth Certificate - Page 2'],
                                        ['parental_consent', 'Parents Consent'],
                                        [
                                            'medical_certificate',
                                            'Medical Certificate',
                                        ],
                                    ] as const
                                ).map(([field, label]) => (
                                    <div key={field} className="space-y-2">
                                        <AthletePhotoInput
                                            id={`athlete-${field}`}
                                            label={label}
                                            guidance="Crop or rotate the image before upload. It will be reduced automatically."
                                            accept="image/jpeg,image/png"
                                            document
                                            onChange={(file) =>
                                                setData(field, file)
                                            }
                                        />
                                        <InputError message={errors[field]} />
                                    </div>
                                ))}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="athlete-grade">Grade level *</Label>
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
                                    <SelectItem value="0">
                                        Non-Graded
                                    </SelectItem>
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
                            <Label htmlFor="athlete-division">Division *</Label>
                            <DivisionSelect
                                id="athlete-division"
                                value={data.age_division}
                                onChange={(value) =>
                                    setData('age_division', value)
                                }
                            />
                            <InputError message={errors.age_division} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="athlete-sex">Sex *</Label>
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
                            <Label htmlFor="athlete-birthdate">
                                Birthdate *
                            </Label>
                            <Input
                                id="athlete-birthdate"
                                type="date"
                                required
                                value={data.birthdate}
                                onChange={(e) =>
                                    setData('birthdate', e.target.value)
                                }
                            />
                            <InputError message={errors.birthdate} />
                        </div>
                        <div className="grid grid-cols-2 gap-4 lg:col-span-3">
                            <div className="space-y-2">
                                <AthletePhotoInput
                                    id="athlete-photo"
                                    label="Passport / Profile Photo"
                                    guidance="Upload a clear front-facing identification photo."
                                    onChange={(file) => setData('photo', file)}
                                />
                                <InputError message={errors.photo} />
                            </div>
                            <div className="space-y-2">
                                <AthletePhotoInput
                                    id="athlete-sports-photo"
                                    label="Sports Photo"
                                    guidance="Upload a clear half-body or full-body sports photo."
                                    onChange={(file) =>
                                        setData('sports_photo', file)
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
        age_division: athlete?.age_division ?? '',
        photo: null as File | null,
        sports_photo: null as File | null,
        athlete_history: null as File | null,
        form_10: null as File | null,
        form_10_page_2: null as File | null,
        birth_certificate: null as File | null,
        birth_certificate_page_2: null as File | null,
        parental_consent: null as File | null,
        medical_certificate: null as File | null,
        _method: 'put',
    });

    if (!athlete) return null;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(update(athlete.id).url, {
            forceFormData: true,
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
                                <SelectItem value="0">Non-Graded</SelectItem>
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
                        <Label htmlFor="edit-athlete-division">Division</Label>
                        <DivisionSelect
                            id="edit-athlete-division"
                            value={data.age_division}
                            onChange={(value) =>
                                setData('age_division', value)
                            }
                        />
                        <InputError message={errors.age_division} />
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
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {(
                                [
                                    ['athlete_history', 'Athlete Record'],
                                    ['form_10', 'School Form 10 - Page 1'],
                                    ['form_10_page_2', 'School Form 10 - Page 2'],
                                    [
                                        'birth_certificate',
                                        'PSA / Birth Certificate - Page 1',
                                    ],
                                    ['birth_certificate_page_2', 'PSA / Birth Certificate - Page 2'],
                                    ['parental_consent', 'Parents Consent'],
                                    [
                                        'medical_certificate',
                                        'Medical Certificate',
                                    ],
                                ] as const
                            ).map(([field, label]) => (
                                <div key={field} className="space-y-2">
                                    <AthletePhotoInput
                                        id={`edit-athlete-${field}`}
                                        label={label}
                                        guidance="Crop or rotate the image before upload. It will be reduced automatically."
                                        accept="image/jpeg,image/png,image/webp,application/pdf"
                                        document
                                        onChange={(file) =>
                                            setData(field, file)
                                        }
                                    />
                                    <InputError message={errors[field]} />
                                </div>
                            ))}
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4 lg:col-span-3">
                        <div className="space-y-2">
                            <AthletePhotoInput
                                id="edit-athlete-photo"
                                label="Passport / Profile Photo"
                                guidance="Upload a clear front-facing identification photo."
                                onChange={(file) => setData('photo', file)}
                            />
                            <InputError message={errors.photo} />
                        </div>
                        <div className="space-y-2">
                            <AthletePhotoInput
                                id="edit-athlete-sports-photo"
                                label="Sports Photo"
                                guidance="Upload a clear half-body or full-body sports photo."
                                onChange={(file) =>
                                    setData('sports_photo', file)
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
    fixedMunicipalityId,
    coachEventOptionsByDelegation,
    municipalities,
    schoolDistricts,
    filterSchools,
    sports,
    canViewDeleted,
    canViewUnassigned,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editingAthlete, setEditingAthlete] = useState<AthleteRow | null>(
        null,
    );
    const filterParams = {
        ...(filters.search ? { search: filters.search } : {}),
        ...(filters.municipality_id
            ? { municipality_id: String(filters.municipality_id) }
            : {}),
        ...(filters.school_district_id
            ? { school_district_id: String(filters.school_district_id) }
            : {}),
        ...(filters.school_id ? { school_id: String(filters.school_id) } : {}),
        ...(filters.sport_id ? { sport_id: String(filters.sport_id) } : {}),
        ...(filters.sex ? { sex: filters.sex } : {}),
        ...(filters.accreditation
            ? { accreditation: filters.accreditation }
            : {}),
        ...(filters.deleted ? { deleted: '1' } : {}),
        ...(filters.unassigned ? { unassigned: '1' } : {}),
    };
    const applyFilter = (key: string, value: string) =>
        router.get(
            index().url,
            { ...filterParams, [key]: value === 'all' ? undefined : value },
            { preserveState: true, preserveScroll: true },
        );

    return (
        <>
            <Head title="Athletes" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={filters.unassigned ? 'Non-Listed Athletes' : 'Athletes'}
                    description={
                        filters.unassigned
                            ? 'Registered athletes that need a Sport and Coach assignment.'
                            : 'Registered athletes per delegation. Access is restricted and audited.'
                    }
                    actions={
                        <div className="flex flex-wrap gap-2">
                            {canViewUnassigned && (
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        router.get(
                                            index().url,
                                            filters.unassigned
                                                ? {}
                                                : { unassigned: '1' },
                                        )
                                    }
                                >
                                    {filters.unassigned
                                        ? 'Back to Athletes'
                                        : 'View Non-Listed Athletes'}
                                </Button>
                            )}
                            {!filters.unassigned &&
                                delegationOptions.length > 0 && (
                                    <Button
                                        onClick={() => setCreateOpen(true)}
                                    >
                                        <Plus />
                                        Register athlete
                                    </Button>
                                )}
                        </div>
                    }
                />

                <div className="flex flex-wrap items-center gap-4">
                    <div className="min-w-64 flex-1">
                        <SearchBar
                            initial={filters.search}
                            placeholder="Search name or LRN"
                            url={index().url}
                            extraParams={filterParams}
                        />
                    </div>
                    {canViewDeleted && (
                        <label className="flex cursor-pointer items-center gap-2 text-sm font-medium">
                            <Checkbox
                                checked={filters.deleted}
                                onCheckedChange={(checked) =>
                                    applyFilter(
                                        'deleted',
                                        checked ? '1' : 'all',
                                    )
                                }
                            />
                            View deleted athletes
                        </label>
                    )}
                </div>
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <Select
                        value={String(filters.municipality_id ?? 'all')}
                        onValueChange={(value) =>
                            applyFilter('municipality_id', value)
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Municipality" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                All municipalities
                            </SelectItem>
                            {municipalities.map((item) => (
                                <SelectItem
                                    key={item.id}
                                    value={String(item.id)}
                                >
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={String(filters.school_district_id ?? 'all')}
                        onValueChange={(value) =>
                            applyFilter('school_district_id', value)
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="School district" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                All school districts
                            </SelectItem>
                            {schoolDistricts
                                .filter(
                                    (item) =>
                                        !filters.municipality_id ||
                                        item.district_id ===
                                            filters.municipality_id,
                                )
                                .map((item) => (
                                    <SelectItem
                                        key={item.id}
                                        value={String(item.id)}
                                    >
                                        {item.name}
                                    </SelectItem>
                                ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={String(filters.school_id ?? 'all')}
                        onValueChange={(value) =>
                            applyFilter('school_id', value)
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="School" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All schools</SelectItem>
                            {filterSchools
                                .filter(
                                    (item) =>
                                        (!filters.municipality_id ||
                                            item.district_id ===
                                                filters.municipality_id) &&
                                        (!filters.school_district_id ||
                                            item.school_district_id ===
                                                filters.school_district_id),
                                )
                                .map((item) => (
                                    <SelectItem
                                        key={item.id}
                                        value={String(item.id)}
                                    >
                                        {item.name}
                                    </SelectItem>
                                ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.sex || 'all'}
                        onValueChange={(value) => applyFilter('sex', value)}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Sex" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All sexes</SelectItem>
                            <SelectItem value="male">Male</SelectItem>
                            <SelectItem value="female">Female</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select
                        value={String(filters.sport_id ?? 'all')}
                        onValueChange={(value) =>
                            applyFilter('sport_id', value)
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Sport" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All sports</SelectItem>
                            {sports.map((sport) => (
                                <SelectItem
                                    key={sport.id}
                                    value={String(sport.id)}
                                >
                                    {sport.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.accreditation || 'all'}
                        onValueChange={(value) =>
                            applyFilter('accreditation', value)
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Eligibility" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All eligibility</SelectItem>
                            <SelectItem value="eligible">
                                Eligible (documents validated)
                            </SelectItem>
                            <SelectItem value="accredited">
                                Accredited
                            </SelectItem>
                            <SelectItem value="not_accredited">
                                Not accredited
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

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
                                        <TableHead>Sports</TableHead>
                                        <TableHead>Coach</TableHead>
                                        <TableHead>Delegation</TableHead>
                                        <TableHead>Eligibility</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {athletes.data.map((athlete) => (
                                        <TableRow key={athlete.id}>
                                            <TableCell className="font-medium">
                                                <div className="flex items-center gap-2">
                                                    {athlete.photo_url ? (
                                                        <img
                                                            src={
                                                                athlete.photo_url
                                                            }
                                                            alt=""
                                                            className="size-[25px] shrink-0 rounded-full border object-cover"
                                                            loading="lazy"
                                                        />
                                                    ) : (
                                                        <span className="flex size-[25px] shrink-0 items-center justify-center rounded-full border bg-muted text-[10px] text-muted-foreground">
                                                            {athlete.name.charAt(
                                                                0,
                                                            )}
                                                        </span>
                                                    )}
                                                    <span>{athlete.name}</span>
                                                    {athlete.deletion_pending &&
                                                        !athlete.deleted && (
                                                            <span className="text-xs text-amber-600">
                                                                Deletion pending
                                                            </span>
                                                        )}
                                                    {athlete.deleted && (
                                                        <span className="text-xs text-destructive">
                                                            Deleted
                                                        </span>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="font-medium">
                                                    {athlete.sports ||
                                                        'Not assigned'}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {athlete.events ||
                                                        'No events assigned'}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {athlete.coach ||
                                                    'Not assigned'}
                                            </TableCell>
                                            <TableCell>
                                                {athlete.delegation}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        athlete.eligibility_state ===
                                                        'eligible'
                                                            ? 'border-green-600/30 bg-green-500/15 text-green-700 dark:text-green-400'
                                                            : athlete.eligibility_state ===
                                                                'under_review'
                                                              ? 'border-orange-600/30 bg-orange-500/15 text-orange-700 dark:text-orange-400'
                                                              : 'border-red-600/30 bg-red-500/15 text-red-700 dark:text-red-400'
                                                    }
                                                >
                                                    {athlete.eligibility_status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    {!athlete.deleted && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={
                                                                    show(
                                                                        athlete.id,
                                                                    ).url
                                                                }
                                                            >
                                                                View
                                                            </Link>
                                                        </Button>
                                                    )}
                                                    {athlete.can_update && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/athletes/${athlete.id}/edit`}
                                                            >
                                                                <Pencil />
                                                                {filters.unassigned
                                                                    ? 'Assign Sport & Coach'
                                                                    : 'Edit'}
                                                            </Link>
                                                        </Button>
                                                    )}
                                                    {athlete.can_delete && (
                                                        <ConfirmDialog
                                                            trigger={
                                                                <Button
                                                                    variant="destructive"
                                                                    size="sm"
                                                                >
                                                                    {athlete.can_confirm_deletion
                                                                        ? 'Confirm deletion'
                                                                        : athlete.deletion_pending
                                                                          ? 'Deletion requested'
                                                                          : 'Request deletion'}
                                                                </Button>
                                                            }
                                                            title={
                                                                athlete.can_confirm_deletion
                                                                    ? 'Confirm athlete deletion?'
                                                                    : 'Request athlete deletion?'
                                                            }
                                                            description={
                                                                athlete.can_confirm_deletion
                                                                    ? 'Tournament ICT confirmation will move this athlete to deleted athletes.'
                                                                    : 'The assigned Tournament ICT must confirm before the athlete is deleted.'
                                                            }
                                                            confirmLabel={
                                                                athlete.can_confirm_deletion
                                                                    ? 'Confirm deletion'
                                                                    : 'Send request'
                                                            }
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
                                                    {athlete.can_cancel_deletion && (
                                                        <ConfirmDialog
                                                            trigger={
                                                                <Button variant="outline" size="sm">
                                                                    Cancel deletion
                                                                </Button>
                                                            }
                                                            title="Cancel athlete deletion request?"
                                                            description="The Athlete will remain active and the pending deletion request will be cleared."
                                                            confirmLabel="Cancel request"
                                                            onConfirm={() =>
                                                                router.patch(
                                                                    `/athletes/${athlete.id}/deletion-request/cancel`,
                                                                    {},
                                                                    { preserveScroll: true },
                                                                )
                                                            }
                                                        />
                                                    )}
                                                    {athlete.deleted && canViewDeleted && (
                                                        <ConfirmDialog
                                                            trigger={
                                                                <Button variant="destructive" size="sm">
                                                                    Delete permanently
                                                                </Button>
                                                            }
                                                            title="Permanently delete athlete?"
                                                            description="This cannot be undone. The athlete's LRN will become available so the athlete can be encoded again."
                                                            confirmLabel="Delete permanently"
                                                            destructive
                                                            onConfirm={() =>
                                                                router.delete(
                                                                    `/athletes/${athlete.id}/permanent`,
                                                                    {
                                                                        data: { confirm: true },
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
                            params={filterParams}
                        />
                    </>
                )}
            </div>

            <AthleteFormDialog
                delegationOptions={delegationOptions}
                schoolOptionsByDelegation={schoolOptionsByDelegation}
                fixedDelegationId={fixedDelegationId}
                fixedMunicipalityId={fixedMunicipalityId}
                coachEventOptionsByDelegation={coachEventOptionsByDelegation}
                municipalities={municipalities}
                schoolDistricts={schoolDistricts}
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
