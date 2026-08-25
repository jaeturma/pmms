import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { LogIn, RefreshCw, UserPlus } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import RecaptchaWidget from '@/components/recaptcha-widget';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

type Props = {
    passwordRules: string;
    coachOptions: Array<{
        meet_sport_id: number;
        meet: string;
        sport: string;
        delegation_id: number;
        delegation: string;
        district_id: number;
        school_id: number;
        school: string;
    }>;
    codeChallengeImage: string;
    registration: { users_enabled: boolean; coaches_enabled: boolean };
};

export default function Register({
    passwordRules,
    coachOptions,
    codeChallengeImage,
    registration,
}: Props) {
    const { recaptcha } = usePage().props;
    const [isCoach, setIsCoach] = useState(
        !registration.users_enabled && registration.coaches_enabled,
    );
    const [delegationId, setDelegationId] = useState('');
    const [schoolId, setSchoolId] = useState('');
    const delegationOptions = Array.from(
        new Map(coachOptions.map((option) => [option.delegation_id, option])).values(),
    );
    const schoolOptions = coachOptions.filter(
        (option) => String(option.delegation_id) === delegationId,
    ).filter((option, index, all) => all.findIndex((item) => item.school_id === option.school_id) === index);
    const sportOptions = coachOptions.filter(
        (option) => String(option.delegation_id) === delegationId && String(option.school_id) === schoolId,
    ).filter((option, index, all) => all.findIndex((item) => item.meet_sport_id === option.meet_sport_id) === index);

    return (
        <>
            <Head title="Register" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        {errors.registration && (
                            <Alert variant="destructive">
                                <AlertTitle>Registration suspended</AlertTitle>
                                <AlertDescription>
                                    {errors.registration}
                                </AlertDescription>
                            </Alert>
                        )}
                        {!registration.users_enabled &&
                            !registration.coaches_enabled && (
                                <Alert>
                                    <AlertTitle>
                                        Registration is currently suspended
                                    </AlertTitle>
                                    <AlertDescription>
                                        Please contact the administrator if you
                                        need an account.
                                    </AlertDescription>
                                </Alert>
                            )}
                        <div className="grid gap-5 rounded-xl border bg-card p-5 shadow-[0_3px_10px_rgba(0,0,0,0.28)] md:grid-cols-2 md:p-6 dark:shadow-[0_3px_10px_rgba(0,0,0,0.55)]">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="name"
                                    name="name"
                                    maxLength={50}
                                    placeholder="Joel M. Reyes, Jr."
                                    className="h-10"
                                />
                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={2}
                                    autoComplete="email"
                                    name="email"
                                    maxLength={50}
                                    placeholder="Active email"
                                    className="h-10"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    Password (8-20 Characters)
                                </Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={3}
                                    autoComplete="new-password"
                                    name="password"
                                    maxLength={20}
                                    placeholder="Password"
                                    passwordrules={passwordRules}
                                    aria-describedby={
                                        errors.password
                                            ? 'password-requirements'
                                            : undefined
                                    }
                                    className="h-10"
                                />
                                {errors.password && (
                                    <p
                                        id="password-requirements"
                                        className="text-xs text-muted-foreground"
                                    >
                                        Include at least 1 lowercase letter, 1
                                        capital letter, 1 number, and 1 special
                                        character.
                                    </p>
                                )}
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={4}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    maxLength={20}
                                    placeholder="Confirm password"
                                    passwordrules={passwordRules}
                                    className="h-10"
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <div className="rounded-lg border bg-muted/40 p-4 md:col-span-2">
                                <label className="flex items-start gap-3 text-sm">
                                    <Checkbox
                                        name="account_type"
                                        value="coach"
                                        checked={isCoach}
                                        disabled={
                                            !registration.coaches_enabled ||
                                            !registration.users_enabled
                                        }
                                        onCheckedChange={(checked) =>
                                            setIsCoach(checked === true)
                                        }
                                    />
                                    <span>
                                        <span className="block font-medium">
                                            Register as a coach
                                        </span>
                                        <span className="text-muted-foreground">
                                            {registration.coaches_enabled
                                                ? 'Choose your delegation, school, and sport. Specific event/category assignments are provided during approval.'
                                                : 'Coach registration is currently suspended.'}
                                        </span>
                                    </span>
                                </label>
                                <InputError message={errors.account_type} />
                            </div>

                            {isCoach && (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="delegation_id">
                                            Delegation / Municipality *
                                        </Label>
                                        <Select name="delegation_id" required value={delegationId} onValueChange={(value) => { setDelegationId(value); setSchoolId(''); }}>
                                            <SelectTrigger
                                                id="delegation_id"
                                                className="!h-10 w-full"
                                            >
                                                <SelectValue placeholder="Select municipality / team" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {delegationOptions.map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option.delegation_id}
                                                            value={String(
                                                                option.delegation_id,
                                                            )}
                                                        >
                                                            {option.delegation} — {option.meet}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.delegation_id}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="school_id">School *</Label>
                                        <Select name="school_id" required value={schoolId} onValueChange={setSchoolId} disabled={!delegationId}>
                                            <SelectTrigger id="school_id" className="!h-10 w-full"><SelectValue placeholder="Select school" /></SelectTrigger>
                                            <SelectContent>{schoolOptions.map((option) => <SelectItem key={option.school_id} value={String(option.school_id)}>{option.school}</SelectItem>)}</SelectContent>
                                        </Select>
                                        <InputError message={errors.school_id} />
                                    </div>

                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="meet_sport_id">
                                            Sport applied for *
                                        </Label>
                                        <Select name="meet_sport_id" required disabled={!schoolId}>
                                            <SelectTrigger id="meet_sport_id" className="!h-10 w-full"><SelectValue placeholder="Select sport" /></SelectTrigger>
                                            <SelectContent>{sportOptions.map((option) => <SelectItem key={option.meet_sport_id} value={String(option.meet_sport_id)}>{option.sport} — {option.meet}</SelectItem>)}</SelectContent>
                                        </Select>
                                        <p className="text-xs text-muted-foreground">Specific sport event/category assignments will be assigned during Coach approval.</p>
                                        <InputError message={errors.meet_sport_id} />
                                    </div>
                                </>
                            )}

                            <div className="grid gap-2 md:col-span-2">
                                <div className="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 sm:grid-cols-[auto_13rem_auto_1fr]">
                                    <Label
                                        htmlFor="code_challenge"
                                        className="col-span-2 whitespace-nowrap sm:col-span-1"
                                    >
                                        Image verification code *
                                    </Label>
                                    <div className="flex h-10 items-center justify-center overflow-hidden rounded-md border bg-slate-100 p-0.5">
                                        <img
                                            src={codeChallengeImage}
                                            alt="Verification code. Type the characters shown into the field beside it."
                                            className="h-9 w-full object-contain"
                                        />
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        className="h-10 w-10"
                                        title="Generate a new verification code"
                                        aria-label="Generate a new verification code"
                                        onClick={() =>
                                            router.reload({
                                                only: ['codeChallengeImage'],
                                            })
                                        }
                                    >
                                        <RefreshCw className="size-4" />
                                    </Button>
                                    <Input
                                        id="code_challenge"
                                        name="code_challenge"
                                        required
                                        maxLength={5}
                                        autoComplete="off"
                                        spellCheck={false}
                                        placeholder="Enter the 5-character code"
                                        className="col-span-2 h-10 text-center text-base uppercase sm:col-span-1"
                                    />
                                </div>
                                <InputError message={errors.code_challenge} />
                            </div>

                            {recaptcha?.enabled && recaptcha.siteKey && (
                                <div className="flex items-end">
                                    <RecaptchaWidget
                                        siteKey={recaptcha.siteKey}
                                    />
                                    <InputError message={errors.recaptcha} />
                                </div>
                            )}

                            <div className="mt-2 grid grid-cols-2 gap-3 md:col-span-2">
                                <Button
                                    asChild
                                    className="h-10 w-full bg-warning text-base text-warning-foreground hover:bg-warning/90"
                                >
                                    <Link href={login()}>
                                        <LogIn className="size-4" />
                                        Login
                                    </Link>
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={
                                        processing ||
                                        (isCoach
                                            ? !registration.coaches_enabled
                                            : !registration.users_enabled)
                                    }
                                    className="h-10 w-full text-base"
                                    tabIndex={5}
                                    data-test="register-user-button"
                                >
                                    {processing && <Spinner />}
                                    {!processing && (
                                        <UserPlus className="size-4" />
                                    )}
                                    Create account
                                </Button>
                            </div>
                        </div>

                        <div className="text-center text-sm font-medium text-muted-foreground">
                            DdOPAA Meet 2026 - SDO Davao de Oro
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Create your PMMS account',
    description:
        'Register securely. Coach details are reviewed before access is granted.',
    wide: true,
    gradient: true,
};
