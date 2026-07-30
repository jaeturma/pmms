import { Link, usePage } from '@inertiajs/react';
import { home } from '@/routes';
import {
    about as publicAbout,
    faqs as publicFaqs,
    meet as publicMeet,
    news as publicNews,
    rankings as publicRankings,
    results as publicResults,
    tally as publicTally,
} from '@/routes/public';
import type { PortalNavShared } from '@/apps/portal/types';

type PortalFooterPageProps = {
    publicNav: PortalNavShared;
};

export function PortalFooter() {
    const { props } = usePage<PortalFooterPageProps>();
    const nav = props.publicNav;

    return (
        <footer className="border-t border-[var(--portal-border)] bg-[var(--portal-surface)] text-[var(--portal-surface-foreground)]">
            <div className="mx-auto max-w-6xl px-4 py-8 sm:px-6">
                <div className="flex flex-col gap-6 sm:flex-row sm:justify-between">
                    <div>
                        <Link href={home().url} className="text-sm font-bold">
                            {nav?.meetName ?? 'Provincial Meet Portal'}
                        </Link>
                        {nav && (
                            <p className="mt-1 text-xs text-[var(--portal-muted-foreground)]">
                                SY {nav.schoolYear}
                                {nav.venue && ` · ${nav.venue}`}
                            </p>
                        )}
                    </div>

                    {nav && (
                        <nav className="grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:flex sm:flex-wrap" aria-label="Footer">
                            <Link href={publicMeet(nav.meetId).url} className="text-[var(--portal-muted-foreground)] hover:text-[var(--portal-fg)]">
                                Schedule
                            </Link>
                            <Link href={publicResults(nav.meetId).url} className="text-[var(--portal-muted-foreground)] hover:text-[var(--portal-fg)]">
                                Results
                            </Link>
                            <Link href={publicTally(nav.meetId).url} className="text-[var(--portal-muted-foreground)] hover:text-[var(--portal-fg)]">
                                Medal tally
                            </Link>
                            <Link href={publicRankings(nav.meetId).url} className="text-[var(--portal-muted-foreground)] hover:text-[var(--portal-fg)]">
                                Standings
                            </Link>
                            <Link href={publicNews(nav.meetId).url} className="text-[var(--portal-muted-foreground)] hover:text-[var(--portal-fg)]">
                                News
                            </Link>
                            <Link href={publicAbout(nav.meetId).url} className="text-[var(--portal-muted-foreground)] hover:text-[var(--portal-fg)]">
                                About
                            </Link>
                            <Link href={publicFaqs(nav.meetId).url} className="text-[var(--portal-muted-foreground)] hover:text-[var(--portal-fg)]">
                                FAQs
                            </Link>
                        </nav>
                    )}
                </div>

                <p className="mt-6 text-xs text-[var(--portal-muted-foreground)]">
                    &copy; {new Date().getFullYear()} Schools Division Office. All rights reserved.
                </p>
            </div>
        </footer>
    );
}
