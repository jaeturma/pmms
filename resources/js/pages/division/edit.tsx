import { Head, useForm } from '@inertiajs/react';
import { Info } from 'lucide-react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { edit, update } from '@/routes/division';

const MAX_LOGO_SIZE_BYTES = 2 * 1024 * 1024;

type DivisionType = 'city' | 'province';

type Props = {
    division: {
        type: DivisionType;
        name: string;
        areaLabel: string;
        logo_url: string | null;
        hero_icon_url: string | null;
    };
    typeLocked: boolean;
};

export default function DivisionEdit({ division, typeLocked }: Props) {
    const { data, setData, post, processing, errors, setError, clearErrors } = useForm<{
        _method: string;
        name: string;
        type: DivisionType;
        logo: File | null;
        remove_logo: boolean;
        hero_icon: File | null;
        remove_hero_icon: boolean;
    }>({
        _method: 'patch',
        name: division.name,
        type: division.type,
        logo: null,
        remove_logo: false,
        hero_icon: null,
        remove_hero_icon: false,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(update().url, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Division settings" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Division settings"
                    description="The division's type determines who registers and competes: schools in a City division, municipalities in a Province division."
                />

                <div className="max-w-lg space-y-6">
                    {typeLocked && (
                        <Alert>
                            <Info />
                            <AlertTitle>Division type is locked</AlertTitle>
                            <AlertDescription>
                                The type can no longer be changed because
                                delegations have already been registered under
                                it. Changing it now would orphan existing
                                registrations.
                            </AlertDescription>
                        </Alert>
                    )}

                    <form onSubmit={submit} className="space-y-6">
                        <div className="space-y-2">
                            <Label htmlFor="division-name">Division name</Label>
                            <Input
                                id="division-name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="division-type">Division type</Label>
                            <Select
                                value={data.type}
                                onValueChange={(value) =>
                                    setData('type', value as DivisionType)
                                }
                                disabled={typeLocked}
                            >
                                <SelectTrigger
                                    id="division-type"
                                    className="w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="city">
                                        City — schools or districts compete
                                    </SelectItem>
                                    <SelectItem value="province">
                                        Province — municipalities compete
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.type} />
                            <p className="text-sm text-muted-foreground">
                                Current area label:{' '}
                                <span className="font-medium">
                                    {division.areaLabel}
                                </span>
                                . Individual schools remain visible in standings
                                either way.
                            </p>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="division-logo">
                                Site logo (optional, max 2MB)
                            </Label>
                            {division.logo_url && !data.logo && !data.remove_logo && (
                                <div className="flex items-center gap-3">
                                    <img
                                        src={division.logo_url}
                                        alt=""
                                        className="size-12 rounded-full border object-cover"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            setData('remove_logo', true)
                                        }
                                    >
                                        Remove
                                    </Button>
                                </div>
                            )}
                            {data.remove_logo && (
                                <p className="text-sm text-muted-foreground">
                                    The current logo will be removed on save.{' '}
                                    <button
                                        type="button"
                                        className="underline"
                                        onClick={() =>
                                            setData('remove_logo', false)
                                        }
                                    >
                                        Undo
                                    </button>
                                </p>
                            )}
                            <Input
                                id="division-logo"
                                type="file"
                                accept="image/*"
                                onChange={(e) => {
                                    const file = e.target.files?.[0] ?? null;

                                    if (file && file.size > MAX_LOGO_SIZE_BYTES) {
                                        setError('logo', 'The logo must not be larger than 2MB.');
                                        e.target.value = '';

                                        return;
                                    }

                                    clearErrors('logo');
                                    setData((current) => ({
                                        ...current,
                                        logo: file,
                                        remove_logo: false,
                                    }));
                                }}
                            />
                            <InputError message={errors.logo} />
                            <p className="text-sm text-muted-foreground">
                                Shown in the public portal header in place of
                                the default mark.
                            </p>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="division-hero-icon">
                                Public landing hero icon (optional, max 2MB)
                            </Label>
                            {division.hero_icon_url && !data.hero_icon && !data.remove_hero_icon && (
                                <div className="flex items-center gap-3">
                                    <img
                                        src={division.hero_icon_url}
                                        alt=""
                                        className="size-12 rounded-full border object-cover"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            setData('remove_hero_icon', true)
                                        }
                                    >
                                        Remove
                                    </Button>
                                </div>
                            )}
                            {data.remove_hero_icon && (
                                <p className="text-sm text-muted-foreground">
                                    The current hero icon will be removed on
                                    save.{' '}
                                    <button
                                        type="button"
                                        className="underline"
                                        onClick={() =>
                                            setData('remove_hero_icon', false)
                                        }
                                    >
                                        Undo
                                    </button>
                                </p>
                            )}
                            <Input
                                id="division-hero-icon"
                                type="file"
                                accept="image/*"
                                onChange={(e) => {
                                    const file = e.target.files?.[0] ?? null;

                                    if (file && file.size > MAX_LOGO_SIZE_BYTES) {
                                        setError('hero_icon', 'The hero icon must not be larger than 2MB.');
                                        e.target.value = '';

                                        return;
                                    }

                                    clearErrors('hero_icon');
                                    setData((current) => ({
                                        ...current,
                                        hero_icon: file,
                                        remove_hero_icon: false,
                                    }));
                                }}
                            />
                            <InputError message={errors.hero_icon} />
                            <p className="text-sm text-muted-foreground">
                                Shown on the public landing page's hero
                                section in place of the default torch mark.
                            </p>
                        </div>

                        <Button type="submit" disabled={processing}>
                            Save changes
                        </Button>
                    </form>
                </div>

                <Heading
                    variant="small"
                    title="What this affects"
                    description="City divisions register delegations by school (or, in a future release, by district). Province divisions register delegations by municipality, pooling multiple schools' athletes under one delegation — school-level medal standings still show which individual school stood out."
                />
            </div>
        </>
    );
}

DivisionEdit.layout = {
    breadcrumbs: [
        {
            title: 'Division settings',
            href: edit(),
        },
    ],
};
