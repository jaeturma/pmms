import { Head, useForm } from '@inertiajs/react';
import { CheckCircle2, Info } from 'lucide-react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
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
import { edit, update } from '@/routes/system-settings';

type Props = {
    settings: {
        recaptcha_enabled: boolean;
        recaptcha_site_key: string | null;
        has_recaptcha_secret_key: boolean;
        recaptcha_ready: boolean;

        smtp_host: string | null;
        smtp_port: number | null;
        smtp_username: string | null;
        has_smtp_password: boolean;
        smtp_encryption: 'tls' | 'ssl' | null;
        smtp_from_address: string | null;
        smtp_from_name: string | null;
        smtp_ready: boolean;

        email_verification_enabled: boolean;
        email_verification_active: boolean;
    };
};

export default function SystemSettingsEdit({ settings }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        recaptcha_enabled: settings.recaptcha_enabled,
        recaptcha_site_key: settings.recaptcha_site_key ?? '',
        recaptcha_secret_key: '',
        smtp_host: settings.smtp_host ?? '',
        smtp_port: settings.smtp_port?.toString() ?? '',
        smtp_username: settings.smtp_username ?? '',
        smtp_password: '',
        smtp_encryption: settings.smtp_encryption ?? '',
        smtp_from_address: settings.smtp_from_address ?? '',
        smtp_from_name: settings.smtp_from_name ?? '',
        email_verification_enabled: settings.email_verification_enabled,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(update().url, { preserveScroll: true });
    };

    return (
        <>
            <Head title="System settings" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="System settings"
                    description="reCAPTCHA and outgoing mail are only enforced once enabled here and their credentials are complete — until then the related checks stay inert."
                />

                <form onSubmit={submit} className="max-w-2xl space-y-10">
                    <section className="space-y-4">
                        <Heading
                            variant="small"
                            title="reCAPTCHA"
                            description="Google reCAPTCHA v2 (checkbox), shown on the login and registration pages once enabled."
                        />

                        {settings.recaptcha_enabled &&
                            !settings.recaptcha_ready && (
                                <Alert variant="destructive">
                                    <Info />
                                    <AlertTitle>Not enforced yet</AlertTitle>
                                    <AlertDescription>
                                        reCAPTCHA is enabled but the site key or
                                        secret key is missing below, so it isn't
                                        actually shown or checked yet.
                                    </AlertDescription>
                                </Alert>
                            )}

                        <div className="flex items-center space-x-3">
                            <Checkbox
                                id="recaptcha_enabled"
                                checked={data.recaptcha_enabled}
                                onCheckedChange={(checked) =>
                                    setData(
                                        'recaptcha_enabled',
                                        checked === true,
                                    )
                                }
                            />
                            <Label htmlFor="recaptcha_enabled">
                                Enable reCAPTCHA on login and registration
                            </Label>
                        </div>
                        <InputError message={errors.recaptcha_enabled} />

                        <div className="space-y-2">
                            <Label htmlFor="recaptcha_site_key">Site key</Label>
                            <Input
                                id="recaptcha_site_key"
                                value={data.recaptcha_site_key}
                                onChange={(e) =>
                                    setData(
                                        'recaptcha_site_key',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError message={errors.recaptcha_site_key} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="recaptcha_secret_key">
                                Secret key
                                {settings.has_recaptcha_secret_key && (
                                    <span className="ml-2 inline-flex items-center gap-1 text-xs font-normal text-muted-foreground">
                                        <CheckCircle2 className="size-3.5" />
                                        already set
                                    </span>
                                )}
                            </Label>
                            <Input
                                id="recaptcha_secret_key"
                                type="password"
                                autoComplete="off"
                                placeholder={
                                    settings.has_recaptcha_secret_key
                                        ? 'Leave blank to keep the current secret key'
                                        : ''
                                }
                                value={data.recaptcha_secret_key}
                                onChange={(e) =>
                                    setData(
                                        'recaptcha_secret_key',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError message={errors.recaptcha_secret_key} />
                        </div>
                    </section>

                    <section className="space-y-4">
                        <Heading
                            variant="small"
                            title="Outgoing mail (SMTP)"
                            description="Used for password resets and, once enabled below, new-account email verification."
                        />

                        {!settings.smtp_ready && (
                            <Alert>
                                <Info />
                                <AlertTitle>Not configured</AlertTitle>
                                <AlertDescription>
                                    Outgoing mail falls back to the server's
                                    default configuration until every field
                                    below is filled in.
                                </AlertDescription>
                            </Alert>
                        )}

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="smtp_host">Host</Label>
                                <Input
                                    id="smtp_host"
                                    value={data.smtp_host}
                                    onChange={(e) =>
                                        setData('smtp_host', e.target.value)
                                    }
                                />
                                <InputError message={errors.smtp_host} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="smtp_port">Port</Label>
                                <Input
                                    id="smtp_port"
                                    type="number"
                                    value={data.smtp_port}
                                    onChange={(e) =>
                                        setData('smtp_port', e.target.value)
                                    }
                                />
                                <InputError message={errors.smtp_port} />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="smtp_username">Username</Label>
                            <Input
                                id="smtp_username"
                                value={data.smtp_username}
                                onChange={(e) =>
                                    setData('smtp_username', e.target.value)
                                }
                            />
                            <InputError message={errors.smtp_username} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="smtp_password">
                                Password
                                {settings.has_smtp_password && (
                                    <span className="ml-2 inline-flex items-center gap-1 text-xs font-normal text-muted-foreground">
                                        <CheckCircle2 className="size-3.5" />
                                        already set
                                    </span>
                                )}
                            </Label>
                            <Input
                                id="smtp_password"
                                type="password"
                                autoComplete="off"
                                placeholder={
                                    settings.has_smtp_password
                                        ? 'Leave blank to keep the current password'
                                        : ''
                                }
                                value={data.smtp_password}
                                onChange={(e) =>
                                    setData('smtp_password', e.target.value)
                                }
                            />
                            <InputError message={errors.smtp_password} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="smtp_encryption">Encryption</Label>
                            <Select
                                value={data.smtp_encryption || 'none'}
                                onValueChange={(value) =>
                                    setData(
                                        'smtp_encryption',
                                        value === 'none' ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger
                                    id="smtp_encryption"
                                    className="w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">None</SelectItem>
                                    <SelectItem value="tls">TLS</SelectItem>
                                    <SelectItem value="ssl">SSL</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.smtp_encryption} />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="smtp_from_address">
                                    From address
                                </Label>
                                <Input
                                    id="smtp_from_address"
                                    type="email"
                                    value={data.smtp_from_address}
                                    onChange={(e) =>
                                        setData(
                                            'smtp_from_address',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={errors.smtp_from_address}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="smtp_from_name">
                                    From name
                                </Label>
                                <Input
                                    id="smtp_from_name"
                                    value={data.smtp_from_name}
                                    onChange={(e) =>
                                        setData(
                                            'smtp_from_name',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.smtp_from_name} />
                            </div>
                        </div>
                    </section>

                    <section className="space-y-4">
                        <Heading
                            variant="small"
                            title="Email verification"
                            description="New accounts must verify their email before signing in. Accounts that already existed when this is turned on are grandfathered in and never blocked."
                        />

                        {settings.email_verification_enabled &&
                            !settings.email_verification_active && (
                                <Alert variant="destructive">
                                    <Info />
                                    <AlertTitle>Not enforced yet</AlertTitle>
                                    <AlertDescription>
                                        Email verification is enabled but
                                        outgoing mail isn't fully configured
                                        above, so verification emails can't be
                                        sent yet.
                                    </AlertDescription>
                                </Alert>
                            )}

                        <div className="flex items-center space-x-3">
                            <Checkbox
                                id="email_verification_enabled"
                                checked={data.email_verification_enabled}
                                onCheckedChange={(checked) =>
                                    setData(
                                        'email_verification_enabled',
                                        checked === true,
                                    )
                                }
                            />
                            <Label htmlFor="email_verification_enabled">
                                Require new accounts to verify their email
                            </Label>
                        </div>
                        <InputError
                            message={errors.email_verification_enabled}
                        />
                    </section>

                    <Button type="submit" disabled={processing}>
                        Save changes
                    </Button>
                </form>
            </div>
        </>
    );
}

SystemSettingsEdit.layout = {
    breadcrumbs: [
        {
            title: 'System settings',
            href: edit(),
        },
    ],
};
