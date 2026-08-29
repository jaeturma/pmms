import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { AthletePhotoInput } from '@/components/athlete-photo-input';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Athlete = {
    id: number;
    delegation_id: number;
    school_id: number;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    name_extension: string | null;
    sex: string;
    birthdate: string;
    lrn: string;
    grade_level: number;
    meet_sport_ids: number[];
    event_ids: number[];
    photo_url: string | null;
    sports_photo_url: string | null;
};
type Props = {
    athlete: Athlete;
    delegations: Array<{ id: number; meet_id: number; label: string }>;
    schools: Array<{
        id: number;
        name: string;
        district: string;
        district_id: number | null;
    }>;
    sports: Array<{ id: number; name: string }>;
    events: Array<{ id: number; sport_id: number; name: string }>;
};

export default function EditAthlete({
    athlete,
    delegations,
    schools,
    sports,
    events,
}: Props) {
    const form = useForm({
        delegation_id: String(athlete.delegation_id),
        school_id: String(athlete.school_id),
        first_name: athlete.first_name,
        middle_name: athlete.middle_name ?? '',
        last_name: athlete.last_name,
        name_extension: athlete.name_extension ?? 'None',
        sex: athlete.sex,
        birthdate: athlete.birthdate,
        lrn: athlete.lrn,
        grade_level: String(athlete.grade_level),
        meet_sport_ids: athlete.meet_sport_ids,
        event_ids: athlete.event_ids,
        photo: null as File | null,
        sports_photo: null as File | null,
        athlete_history: null as File | null,
        form_10: null as File | null,
        school_id_document: null as File | null,
        report_card: null as File | null,
        birth_certificate: null as File | null,
        parental_consent: null as File | null,
        medical_certificate: null as File | null,
        _method: 'put',
    });
    const school = schools.find(
        (item) => item.id === Number(form.data.school_id),
    );
    const toggle = (
        field: 'meet_sport_ids' | 'event_ids',
        id: number,
        checked: boolean,
    ) =>
        form.setData(
            field,
            checked
                ? [...new Set([...form.data[field], id])]
                : form.data[field].filter((value) => value !== id),
        );
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`/athletes/${athlete.id}`, { forceFormData: true });
    };

    return (
        <>
            <Head title={`Edit ${athlete.first_name} ${athlete.last_name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Edit athlete"
                    description="Update identity, school, competition assignments, and attached photos."
                    actions={
                        <Button variant="outline" asChild>
                            <Link href="/athletes">
                                <ArrowLeft />
                                Athletes
                            </Link>
                        </Button>
                    }
                />
                <form
                    onSubmit={submit}
                    className="grid gap-4 xl:grid-cols-[1.1fr_1fr_1fr]"
                >
                    <section className="grid content-start gap-3 rounded-xl border p-4 sm:grid-cols-2">
                        <h2 className="font-semibold sm:col-span-2">
                            Student information
                        </h2>
                        <Field label="LRN" error={form.errors.lrn}>
                            <Input
                                value={form.data.lrn}
                                maxLength={12}
                                onChange={(e) =>
                                    form.setData('lrn', e.target.value)
                                }
                            />
                        </Field>
                        <Field label="Grade" error={form.errors.grade_level}>
                            <Select
                                value={form.data.grade_level}
                                onValueChange={(value) =>
                                    form.setData('grade_level', value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {Array.from(
                                        { length: 12 },
                                        (_, index) => index + 1,
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
                        </Field>
                        <Field label="Last name" error={form.errors.last_name}>
                            <Input
                                value={form.data.last_name}
                                onChange={(e) =>
                                    form.setData('last_name', e.target.value)
                                }
                            />
                        </Field>
                        <Field
                            label="First name"
                            error={form.errors.first_name}
                        >
                            <Input
                                value={form.data.first_name}
                                onChange={(e) =>
                                    form.setData('first_name', e.target.value)
                                }
                            />
                        </Field>
                        <Field
                            label="Middle name"
                            error={form.errors.middle_name}
                        >
                            <Input
                                value={form.data.middle_name}
                                onChange={(e) =>
                                    form.setData('middle_name', e.target.value)
                                }
                            />
                        </Field>
                        <Field
                            label="Extension"
                            error={form.errors.name_extension}
                        >
                            <Select
                                value={form.data.name_extension}
                                onValueChange={(value) =>
                                    form.setData('name_extension', value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {['None', 'Jr.', 'Sr.', 'II', 'III'].map(
                                        (value) => (
                                            <SelectItem
                                                key={value}
                                                value={value}
                                            >
                                                {value}
                                            </SelectItem>
                                        ),
                                    )}
                                </SelectContent>
                            </Select>
                        </Field>
                        <Field label="Sex" error={form.errors.sex}>
                            <Select
                                value={form.data.sex}
                                onValueChange={(value) =>
                                    form.setData('sex', value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="male">Male</SelectItem>
                                    <SelectItem value="female">
                                        Female
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </Field>
                        <Field label="Birthdate" error={form.errors.birthdate}>
                            <Input
                                type="date"
                                value={form.data.birthdate}
                                onChange={(e) =>
                                    form.setData('birthdate', e.target.value)
                                }
                            />
                        </Field>
                    </section>

                    <section className="content-start space-y-3 rounded-xl border p-4">
                        <h2 className="font-semibold">School and delegation</h2>
                        <Field
                            label="Delegation"
                            error={form.errors.delegation_id}
                        >
                            <Select
                                value={form.data.delegation_id}
                                onValueChange={(value) =>
                                    form.setData('delegation_id', value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {delegations.map((item) => (
                                        <SelectItem
                                            key={item.id}
                                            value={String(item.id)}
                                        >
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                        <Field label="School" error={form.errors.school_id}>
                            <Select
                                value={form.data.school_id}
                                onValueChange={(value) =>
                                    form.setData('school_id', value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {schools.map((item) => (
                                        <SelectItem
                                            key={item.id}
                                            value={String(item.id)}
                                        >
                                            {item.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                        <Field label="District">
                            <Input
                                value={school?.district ?? 'Not assigned'}
                                disabled
                            />
                        </Field>
                        <h2 className="pt-2 font-semibold">Applied sports</h2>
                        <div className="grid grid-cols-2 gap-2">
                            {sports.map((sport) => (
                                <Check
                                    key={sport.id}
                                    label={sport.name}
                                    checked={form.data.meet_sport_ids.includes(
                                        sport.id,
                                    )}
                                    onChange={(checked) =>
                                        toggle(
                                            'meet_sport_ids',
                                            sport.id,
                                            checked,
                                        )
                                    }
                                />
                            ))}
                        </div>
                        <InputError message={form.errors.meet_sport_ids} />
                    </section>

                    <section className="content-start space-y-3 rounded-xl border p-4">
                        <h2 className="font-semibold">Events</h2>
                        <div className="grid max-h-48 gap-2 overflow-y-auto pr-1">
                            {events.map((event) => (
                                <Check
                                    key={event.id}
                                    label={event.name}
                                    checked={form.data.event_ids.includes(
                                        event.id,
                                    )}
                                    onChange={(checked) =>
                                        toggle('event_ids', event.id, checked)
                                    }
                                />
                            ))}
                        </div>
                        <InputError message={form.errors.event_ids} />
                        <h2 className="pt-2 font-semibold">Photos</h2>
                        <div className="grid grid-cols-2 gap-3">
                            <AthletePhotoInput
                                id="athlete-photo"
                                label="Profile photo"
                                guidance="Replace the current profile photo."
                                onChange={(file) => form.setData('photo', file)}
                            />
                            <AthletePhotoInput
                                id="athlete-sports-photo"
                                label="Sports photo"
                                guidance="Replace the current sports photo."
                                onChange={(file) =>
                                    form.setData('sports_photo', file)
                                }
                            />
                        </div>
                        <InputError
                            message={
                                form.errors.photo ?? form.errors.sports_photo
                            }
                        />
                    </section>

                    <section className="rounded-xl border p-4 xl:col-span-3">
                        <div className="flex flex-wrap items-end justify-between gap-4">
                            <div className="grid flex-1 gap-3 sm:grid-cols-3 xl:grid-cols-5">
                                {(
                                    [
                                        ['athlete_history', 'Athlete History'],
                                        ['form_10', 'School Form 10'],
                                        ['school_id_document', 'School ID'],
                                        [
                                            'birth_certificate',
                                            'Birth Certificate',
                                        ],
                                        ['report_card', 'Report Card'],
                                        ['parental_consent', 'Parent Consent'],
                                        [
                                            'medical_certificate',
                                            'Medical Certificate',
                                        ],
                                    ] as const
                                ).map(([field, label]) => (
                                    <AthletePhotoInput
                                        key={field}
                                        id={`athlete-${field}`}
                                        label={label}
                                        guidance="Upload only to replace."
                                        accept="image/jpeg,image/png"
                                        onChange={(file) =>
                                            form.setData(field, file)
                                        }
                                    />
                                ))}
                            </div>
                            <Button type="submit" disabled={form.processing}>
                                <Save />
                                Save athlete
                            </Button>
                        </div>
                    </section>
                </form>
            </div>
        </>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="space-y-1.5">
            <Label>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}
function Check({
    label,
    checked,
    onChange,
}: {
    label: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
}) {
    return (
        <label className="flex cursor-pointer items-start gap-2 rounded-md border p-2 text-sm">
            <Checkbox
                checked={checked}
                onCheckedChange={(value) => onChange(value === true)}
            />
            <span>{label}</span>
        </label>
    );
}

EditAthlete.layout = {
    breadcrumbs: [
        { title: 'Athletes', href: '/athletes' },
        { title: 'Edit athlete', href: '#' },
    ],
};
