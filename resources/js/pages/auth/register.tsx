import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { ChevronDown, LogIn, RefreshCw, UserPlus } from 'lucide-react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import RecaptchaWidget from '@/components/recaptcha-widget';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Spinner } from '@/components/ui/spinner';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { store } from '@/routes/register';
import { login } from '@/routes';

type Props = {
    passwordRules: string;
    municipalities: Array<{ id: number; name: string }>;
    events: Array<{ id: number; label: string }>;
    codeChallengeImage: string;
};

export default function Register({
    passwordRules,
    municipalities,
    events,
    codeChallengeImage,
}: Props) {
    const { recaptcha } = usePage().props;
    const [isCoach, setIsCoach] = useState(false);
    const [selectedEventIds, setSelectedEventIds] = useState<number[]>([]);

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
                                <InputError message={errors.password_confirmation} />
                            </div>

                            <div className="md:col-span-2 rounded-lg border bg-muted/40 p-4">
                                <label className="flex items-start gap-3 text-sm">
                                    <Checkbox
                                        name="account_type"
                                        value="coach"
                                        checked={isCoach}
                                        onCheckedChange={(checked) =>
                                            setIsCoach(checked === true)
                                        }
                                    />
                                    <span>
                                        <span className="block font-medium">Register as a coach</span>
                                        <span className="text-muted-foreground">
                                            Coach accounts require a municipality/team and sports event.
                                        </span>
                                    </span>
                                </label>
                                <InputError message={errors.account_type} />
                            </div>

                            {isCoach && (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="district_id">Municipality / Team *</Label>
                                        <Select name="district_id" required>
                                            <SelectTrigger id="district_id" className="!h-10 w-full">
                                                <SelectValue placeholder="Select municipality / team" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {municipalities.map((municipality) => (
                                                    <SelectItem key={municipality.id} value={String(municipality.id)}>
                                                        {municipality.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.district_id} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="event_ids">Specific sports events *</Label>
                                        {selectedEventIds.map((eventId) => (
                                            <input key={eventId} type="hidden" name="event_ids[]" value={eventId} />
                                        ))}
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button id="event_ids" type="button" variant="outline" className="!h-10 w-full justify-between px-3 font-normal">
                                                    <span className="truncate">
                                                        {selectedEventIds.length === 0
                                                            ? 'Select one or more sports events'
                                                            : `${selectedEventIds.length} event${selectedEventIds.length === 1 ? '' : 's'} selected`}
                                                    </span>
                                                    <ChevronDown className="size-4 opacity-60" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="start" className="max-h-72 w-[var(--radix-dropdown-menu-trigger-width)] overflow-y-auto">
                                                {events.map((event) => (
                                                    <DropdownMenuCheckboxItem
                                                        key={event.id}
                                                        checked={selectedEventIds.includes(event.id)}
                                                        onSelect={(event) => event.preventDefault()}
                                                        onCheckedChange={(checked) =>
                                                            setSelectedEventIds((current) =>
                                                                checked
                                                                    ? [...current, event.id]
                                                                    : current.filter((id) => id !== event.id),
                                                            )
                                                        }
                                                    >
                                                        {event.label}
                                                    </DropdownMenuCheckboxItem>
                                                ))}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                        <InputError message={errors.event_ids} />
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
                                    className="h-10 w-full text-base"
                                    tabIndex={5}
                                    data-test="register-user-button"
                                >
                                    {processing && <Spinner />}
                                    {!processing && <UserPlus className="size-4" />}
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
    description: 'Register securely. Coach details are reviewed before access is granted.',
    wide: true,
};
