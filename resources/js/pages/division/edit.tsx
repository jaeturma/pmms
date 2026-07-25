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

type DivisionType = 'city' | 'province';

type Props = {
    division: {
        type: DivisionType;
        name: string;
        areaLabel: string;
    };
    typeLocked: boolean;
};

export default function DivisionEdit({ division, typeLocked }: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        name: division.name,
        type: division.type,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        patch(update().url, { preserveScroll: true });
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
