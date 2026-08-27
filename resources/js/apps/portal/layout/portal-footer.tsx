import { Link, usePage } from '@inertiajs/react';
import type { PortalNavShared } from '@/apps/portal/types';
import {
    meet as publicMeet,
    rankings as publicRankings,
    results as publicResults,
    tally as publicTally,
} from '@/routes/public';

type PortalFooterPageProps = {
    publicNav: PortalNavShared;
};

export function PortalFooter() {
    const { props } = usePage<PortalFooterPageProps>();
    const nav = props.publicNav;

    return (
        <footer className="border-t border-[var(--portal-border)] bg-[var(--portal-ink)] text-[var(--portal-ink-foreground)]">
            <div className="w-full px-4 py-5 sm:px-6 lg:px-10 xl:px-16 2xl:px-24">
                <div className="flex items-center justify-between gap-6">
                    {nav && (
                        <nav
                            className="hidden gap-x-6 gap-y-2 text-sm sm:flex sm:flex-wrap"
                            aria-label="Footer"
                        >
                            <Link
                                href={publicMeet(nav.meetId).url}
                                className="text-[var(--portal-ink-foreground)]/70 hover:text-[var(--portal-ink-foreground)]"
                            >
                                Schedule
                            </Link>
                            <Link
                                href={publicResults(nav.meetId).url}
                                className="text-[var(--portal-ink-foreground)]/70 hover:text-[var(--portal-ink-foreground)]"
                            >
                                Results
                            </Link>
                            <Link
                                href={publicTally(nav.meetId).url}
                                className="text-[var(--portal-ink-foreground)]/70 hover:text-[var(--portal-ink-foreground)]"
                            >
                                Medal tally
                            </Link>
                            <Link
                                href={publicRankings(nav.meetId).url}
                                className="text-[var(--portal-ink-foreground)]/70 hover:text-[var(--portal-ink-foreground)]"
                            >
                                Standings
                            </Link>
                            <Link
                                href="/news"
                                className="text-[var(--portal-ink-foreground)]/70 hover:text-[var(--portal-ink-foreground)]"
                            >
                                News
                            </Link>
                            <Link
                                href="/about"
                                className="text-[var(--portal-ink-foreground)]/70 hover:text-[var(--portal-ink-foreground)]"
                            >
                                About
                            </Link>
                            <Link
                                href="/faq"
                                className="text-[var(--portal-ink-foreground)]/70 hover:text-[var(--portal-ink-foreground)]"
                            >
                                FAQs
                            </Link>
                        </nav>
                    )}
                    <p className="ml-auto shrink-0 text-right text-xs text-[var(--portal-ink-foreground)]/70">
                        &copy; {new Date().getFullYear()} Schools Division
                        Office. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    );
}
