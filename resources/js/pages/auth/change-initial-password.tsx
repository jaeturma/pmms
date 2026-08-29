import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export default function ChangeInitialPassword() {
    return (
        <>
            <Head title="Change initial password" />
            <Form
                action="/change-password"
                method="put"
                resetOnSuccess={['password', 'password_confirmation']}
                className="grid gap-5 rounded-xl border bg-card p-6 shadow-sm"
            >
                {({ processing, errors }) => (
                    <>
                        <div>
                            <h1 className="text-xl font-semibold">
                                Welcome to PMMS
                            </h1>
                            <p className="mt-2 text-sm text-muted-foreground">
                                For security, you must change your initial
                                password before continuing.
                            </p>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="password">New Password</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                autoFocus
                                autoComplete="new-password"
                            />
                            <InputError message={errors.password} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Confirm New Password
                            </Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                                autoComplete="new-password"
                            />
                        </div>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Change Password
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

ChangeInitialPassword.layout = {
    title: 'Secure your account',
    description: 'Choose a personal password to continue.',
};
