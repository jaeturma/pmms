import { Head, Link } from '@inertiajs/react';
import { ShieldAlert } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

type Props = {
    status: number;
    title?: string;
    message?: string;
};

const defaults: Record<number, { title: string; message: string }> = {
    403: {
        title: 'Permission denied',
        message:
            'Your account does not have permission to view this page. Contact the meet administrator if you believe this is a mistake.',
    },
};

export default function ErrorPage({ status, title, message }: Props) {
    const fallback = defaults[status] ?? {
        title: `Error ${status}`,
        message: 'Something went wrong. Please try again.',
    };

    return (
        <div className="flex min-h-svh items-center justify-center p-6">
            <Head title={title ?? fallback.title} />
            <div className="w-full max-w-md">
                <EmptyState
                    icon={ShieldAlert}
                    title={title ?? fallback.title}
                    description={message ?? fallback.message}
                    action={
                        <Button asChild>
                            <Link href={dashboard()}>Back to dashboard</Link>
                        </Button>
                    }
                />
            </div>
        </div>
    );
}
