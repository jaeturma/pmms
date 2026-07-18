import { Head, Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Welcome" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full">
                    <nav className="mx-auto flex w-full max-w-4xl items-center justify-end gap-2 p-6">
                        {auth.user ? (
                            <Button asChild variant="outline">
                                <Link href={dashboard()}>Dashboard</Link>
                            </Button>
                        ) : (
                            <>
                                <Button asChild variant="ghost">
                                    <Link href={login()}>Log in</Link>
                                </Button>
                                <Button asChild variant="outline">
                                    <Link href={register()}>Register</Link>
                                </Button>
                            </>
                        )}
                    </nav>
                </header>
                <main className="flex flex-1 items-center justify-center p-6">
                    <div className="flex max-w-xl flex-col items-center text-center">
                        <div className="mb-6 flex size-16 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
                            <AppLogoIcon className="size-9 fill-current" />
                        </div>
                        <h1 className="text-3xl font-semibold tracking-tight">
                            Provincial Meet Management System
                        </h1>
                        <p className="mt-2 text-sm font-medium text-muted-foreground">
                            Division Edition
                        </p>
                        <p className="mt-6 text-balance text-muted-foreground">
                            Management of provincial athletic meets for the
                            Schools Division Office — registration, scheduling,
                            results, and reporting in one place.
                        </p>
                        <div className="mt-8 flex gap-3">
                            {auth.user ? (
                                <Button asChild size="lg">
                                    <Link href={dashboard()}>
                                        Go to Dashboard
                                    </Link>
                                </Button>
                            ) : (
                                <Button asChild size="lg">
                                    <Link href={login()}>Get Started</Link>
                                </Button>
                            )}
                        </div>
                    </div>
                </main>
                <footer className="p-6 text-center text-xs text-muted-foreground">
                    PMMS Division Edition — DepEd Schools Division Office
                </footer>
            </div>
        </>
    );
}
