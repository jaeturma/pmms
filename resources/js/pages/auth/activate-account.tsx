import { Head, useForm } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    token: string;
    person: string;
    email: string;
    role: string;
    passwordRules: string;
};

export default function ActivateAccount({
    token,
    person,
    email,
    role,
    passwordRules,
}: Props) {
    const { data, setData, post, processing, errors } = useForm({
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(`/account-activation/${token}`);
    };

    return (
        <>
            <Head title="Activate account" />
            <form onSubmit={submit} className="grid gap-5">
                <div className="rounded-[5px] border bg-muted/40 p-4 text-sm">
                    <p className="font-semibold">{person}</p>
                    <p className="text-muted-foreground">{role}</p>
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="activation-email">Email</Label>
                    <Input id="activation-email" value={email} readOnly />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="activation-password">Password</Label>
                    <PasswordInput
                        id="activation-password"
                        value={data.password}
                        onChange={(event) =>
                            setData('password', event.target.value)
                        }
                        autoComplete="new-password"
                        passwordrules={passwordRules}
                        autoFocus
                    />
                    <InputError message={errors.password} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="activation-password-confirmation">
                        Confirm password
                    </Label>
                    <PasswordInput
                        id="activation-password-confirmation"
                        value={data.password_confirmation}
                        onChange={(event) =>
                            setData('password_confirmation', event.target.value)
                        }
                        autoComplete="new-password"
                        passwordrules={passwordRules}
                    />
                    <InputError message={errors.password_confirmation} />
                </div>
                <Button type="submit" disabled={processing}>
                    {processing ? (
                        <Spinner />
                    ) : (
                        <CheckCircle2 className="size-4" />
                    )}
                    Activate account
                </Button>
            </form>
        </>
    );
}

ActivateAccount.layout = {
    title: 'Activate your account',
    description: 'Create your password to access the DdOPAA Meet 2026 system.',
};
