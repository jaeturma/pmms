import { Head, Link, usePage } from '@inertiajs/react';
import {
    Award,
    CalendarDays,
    Home as HomeIcon,
    Radio,
    Trophy,
} from 'lucide-react';
import type { PropsWithChildren } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { PublicBottomNav } from '@/components/public-bottom-nav';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard, home, login } from '@/routes';
import {
    meet as publicMeet,
    results as publicResults,
    tally as publicTally,
} from '@/routes/public';

/**
 * Layout for the public portal: no authentication, no app sidebar —
 * a lightweight mobile-first shell for visitors.
 */
export default function PublicLayout({ children }: PropsWithChildren) {
    const page = usePage();
    const { auth, publicNav } = page.props;
    const currentPath = page.url.split('?')[0];

    const topNavItems = publicNav
        ? [
              { label: 'Home', href: home().url },
              { label: 'Schedule', href: publicMeet(publicNav.meetId).url },
              { label: 'Results', href: publicResults(publicNav.meetId).url },
              { label: 'Medal Tally', href: publicTally(publicNav.meetId).url },
          ]
        : [{ label: 'Home', href: home().url }];

    // Mobile-only bottom tab bar (WP-08-09) — same real destinations as
    // the header nav above, plus a "Live" tab when there's an active
    // match; shorter labels since 5 tabs share one row.
    const bottomNavItems = publicNav
        ? [
              { label: 'Home', href: home().url, icon: HomeIcon },
              {
                  label: 'Schedule',
                  href: publicMeet(publicNav.meetId).url,
                  icon: CalendarDays,
              },
              {
                  label: 'Results',
                  href: publicResults(publicNav.meetId).url,
                  icon: Award,
              },
              {
                  label: 'Ranking',
                  href: publicTally(publicNav.meetId).url,
                  icon: Trophy,
              },
              ...(publicNav.liveCount > 0
                  ? [
                        {
                            label: 'Live',
                            href: publicMeet(publicNav.meetId).url,
                            icon: Radio,
                        },
                    ]
                  : []),
          ]
        : [{ label: 'Home', href: home().url, icon: HomeIcon }];

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <Head>
                <meta
                    name="description"
                    content="Provincial Meet portal: published schedules, official validated results, live medal tally, and announcements — no account needed."
                />
            </Head>

            <header className="border-b">
                <div className="mx-auto flex w-full max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3">
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

                    {/* Below `sm:` this is replaced by the fixed bottom
                        tab bar (`PublicBottomNav`) — the reference's
                        mobile shell moves navigation there instead. */}
                    <nav
                        aria-label="Site sections"
                        className="order-3 hidden w-full items-center gap-1 overflow-x-auto sm:order-none sm:flex sm:w-auto"
                    >
                        {topNavItems.map((item) => {
                            const isActive = item.href === currentPath;

                            return (
                                <Button
                                    key={item.label}
                                    variant={isActive ? 'secondary' : 'ghost'}
                                    size="sm"
                                    className="shrink-0"
                                    aria-current={isActive ? 'page' : undefined}
                                    asChild
                                >
                                    <Link href={item.href}>{item.label}</Link>
                                </Button>
                            );
                        })}
                    </nav>

                    <div className="flex items-center gap-2">
                        {publicNav && publicNav.liveCount > 0 && (
                            <Button
                                asChild
                                variant="outline"
                                size="sm"
                                className="border-destructive/30 text-destructive"
                            >
                                <Link href={publicMeet(publicNav.meetId).url}>
                                    <Radio aria-hidden="true" />
                                    Live now
                                    <Badge
                                        variant="destructive"
                                        className="ml-0.5"
                                    >
                                        {publicNav.liveCount}
                                    </Badge>
                                </Link>
                            </Button>
                        )}
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
                </div>
            </header>

            <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-6 pb-20 sm:pb-6">
                {children}
            </main>

            <footer className="hidden border-t px-4 py-6 text-center text-xs text-muted-foreground sm:block">
                PMMS Division Edition — DepEd Schools Division Office
            </footer>

            <PublicBottomNav items={bottomNavItems} currentPath={currentPath} />
        </div>
    );
}
