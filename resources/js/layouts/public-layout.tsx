import { Head, Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard, home, login } from '@/routes';

/**
 * Layout for the public portal: no authentication, no app sidebar —
 * a lightweight mobile-first shell for visitors.
 */
export default function PublicLayout({ children }: PropsWithChildren) {
    const { auth } = usePage().props;

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <Head>
                <meta
                    name="description"
                    content="Provincial Meet portal: published schedules, official validated results, live medal tally, and announcements — no account needed."
                />
            </Head>

            <header className="border-b">
                <div className="mx-auto flex w-full max-w-5xl items-center justify-between gap-3 px-4 py-3">
                    <Link
                        href={home()}
                        className="flex items-center gap-2 font-semibold"
                    >
                        <span className="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                            <AppLogoIcon className="size-5 fill-current" />
                        </span>
                        <span className="leading-tight">
                            PMMS
                            <span className="block text-xs font-normal text-muted-foreground">
                                Provincial Meet
                            </span>
                        </span>
                    </Link>
                    {auth.user ? (
                        <Button asChild variant="outline" size="sm">
                            <Link href={dashboard()}>Dashboard</Link>
                        </Button>
                    ) : (
                        <Button asChild variant="outline" size="sm">
                            <Link href={login()}>Sign in</Link>
                        </Button>
                    )}
                </div>
            </header>

            <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-6">
                {children}
            </main>

            <footer className="border-t px-4 py-6 text-center text-xs text-muted-foreground">
                PMMS Division Edition — DepEd Schools Division Office
            </footer>
        </div>
    );
}
