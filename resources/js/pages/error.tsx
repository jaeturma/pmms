import { Head, Link, usePage } from '@inertiajs/react';
import { Compass, ShieldAlert } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import PublicLayout from '@/layouts/public-layout';
import { dashboard, home } from '@/routes';
import type { Auth } from '@/types/auth';

type Props = {
    status: number;
    title?: string;
    message?: string;
    canRepair?: boolean;
};

const defaults: Record<
    number,
    { title: string; message: string; icon: typeof ShieldAlert }
> = {
    403: {
        title: 'Permission denied',
        message:
            'Your account does not have permission to view this page. Contact the meet administrator if you believe this is a mistake.',
        icon: ShieldAlert,
    },
    404: {
        title: 'Page not found',
        message:
            "This page doesn't exist, or the meet it belongs to hasn't been published yet.",
        icon: Compass,
    },
    500: {
        title: 'Unable to complete this information',
        message:
            'A required or linked record may be missing. Review its delegation, school, sport, event entry, and team membership, then try again.',
        icon: ShieldAlert,
    },
};

export default function ErrorPage({ status, title, message, canRepair }: Props) {
    const { auth } = usePage().props;
    const fallback = defaults[status] ?? {
        title: `Error ${status}`,
        message: 'Something went wrong. Please try again.',
        icon: ShieldAlert,
    };

    return (
        <div className="flex min-h-svh items-center justify-center p-6 sm:p-10">
            <Head title={title ?? fallback.title} />
            <div className="w-full max-w-md animate-card-in sm:max-w-lg">
                <EmptyState
                    icon={fallback.icon}
                    title={title ?? fallback.title}
                    description={message ?? fallback.message}
                    action={<div className="flex flex-wrap justify-center gap-2">
                        {status === 500 && canRepair && <Button asChild><Link href="/data-repair">Inspect and repair data</Link></Button>}
                        <Button variant={status === 500 && canRepair ? 'outline' : 'default'} asChild>
                            {auth.user ? <Link href={dashboard()}>Back to dashboard</Link> : <Link href={home()}>Back to portal home</Link>}
                        </Button>
                    </div>}
                />
            </div>
        </div>
    );
}

ErrorPage.layout = (props: Props & { auth: Auth }) =>
    props.auth?.user ? AppLayout : PublicLayout;
