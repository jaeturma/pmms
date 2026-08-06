import { Link, usePage } from '@inertiajs/react';
import { Menu, Radio, X } from 'lucide-react';
import { useState } from 'react';
import { dashboard, home, login } from '@/routes';
import {
    about as publicAbout,
    faqs as publicFaqs,
    gallery as publicGallery,
    meet as publicMeet,
    news as publicNews,
    results as publicResults,
    search as publicSearch,
    sportPortal,
    sports as publicSports,
    tally as publicTally,
} from '@/routes/public';
import { PortalNavigation, type PortalNavItem } from '@/apps/portal/layout/portal-navigation';
import { PortalNavDropdown } from '@/apps/portal/layout/portal-nav-dropdown';
import { PORTAL_SPORTS } from '@/apps/portal/lib/sport-terminology';
import type { PortalNavShared } from '@/apps/portal/types';

type PortalHeaderPageProps = {
    auth: { user: { id: number } | null };
    publicNav: PortalNavShared;
    division: { logoUrl: string | null };
};

type PortalHeaderProps = {
    activePath: string;
};

export function PortalHeader({ activePath }: PortalHeaderProps) {
    const { props } = usePage<PortalHeaderPageProps>();
    const [menuOpen, setMenuOpen] = useState(false);
    const nav = props.publicNav;

    const items: PortalNavItem[] = [{ label: 'Home', href: home().url, active: activePath === '/' }];

    if (nav) {
        items.push(
            { label: 'Schedule', href: publicMeet(nav.meetId).url, active: activePath === publicMeet(nav.meetId).url },
            { label: 'Results', href: publicResults(nav.meetId).url, active: activePath === publicResults(nav.meetId).url },
            { label: 'Medal Tally', href: publicTally(nav.meetId).url, active: activePath === publicTally(nav.meetId).url },
        );
    }

    const sportItems = PORTAL_SPORTS.map((sport) => ({
        label: sport.name,
        href: sportPortal(sport.slug).url,
    }));

    const moreItems = nav
        ? [
              { label: 'Sports directory', href: publicSports(nav.meetId).url },
              { label: 'Gallery', href: publicGallery(nav.meetId).url },
              { label: 'News', href: publicNews(nav.meetId).url },
              { label: 'About', href: publicAbout(nav.meetId).url },
              { label: 'FAQs', href: publicFaqs(nav.meetId).url },
              { label: 'Search', href: publicSearch(nav.meetId).url },
          ]
        : [];

    return (
        <header className="sticky top-0 z-40 bg-[var(--portal-accent)] text-[var(--portal-accent-foreground)] shadow-md">
            <div className="flex h-16 w-full items-center justify-between gap-4 px-4 sm:px-6 lg:px-10 xl:px-16 2xl:px-24">
                <Link href={home().url} className="flex min-w-0 items-center gap-2 text-base font-bold text-[var(--portal-accent-foreground)]">
                    {props.division.logoUrl ? (
                        <img
                            src={props.division.logoUrl}
                            alt=""
                            className="size-8 rounded-[calc(var(--portal-radius)-0.25rem)] object-cover"
                        />
                    ) : (
                        <span className="flex size-8 items-center justify-center rounded-[calc(var(--portal-radius)-0.25rem)] bg-[var(--portal-ink)] text-sm text-[var(--portal-ink-foreground)]">
                            PM
                        </span>
                    )}
                    <span className="min-w-0 truncate">{nav?.meetName ?? 'Provincial Meet Portal'}</span>
                </Link>

                <div className="hidden items-center md:flex">
                    <PortalNavigation items={items} />
                    <PortalNavDropdown label="Sports" items={sportItems} />
                    {moreItems.length > 0 && <PortalNavDropdown label="More" items={moreItems} />}
                </div>

                <div className="flex items-center gap-2">
                    {nav && nav.liveCount > 0 && (
                        <span className="hidden items-center gap-1.5 rounded-full bg-[var(--portal-live)]/10 px-2.5 py-1 text-xs font-medium text-[var(--portal-live)] sm:flex">
                            <Radio aria-hidden="true" className="size-3.5" />
                            {nav.liveCount} live
                        </span>
                    )}
                    <Link
                        href={props.auth.user ? dashboard().url : login().url}
                        className="hidden rounded-[calc(var(--portal-radius)-0.25rem)] border border-[var(--portal-accent-foreground)]/30 px-3 py-1.5 text-sm font-medium text-[var(--portal-accent-foreground)] transition-colors hover:bg-[var(--portal-accent-foreground)]/10 sm:inline-flex"
                    >
                        {props.auth.user ? 'Dashboard' : 'Staff login'}
                    </Link>
                    <button
                        type="button"
                        className="inline-flex size-9 items-center justify-center rounded-[calc(var(--portal-radius)-0.25rem)] text-[var(--portal-accent-foreground)] hover:bg-[var(--portal-accent-foreground)]/10 md:hidden"
                        aria-label={menuOpen ? 'Close menu' : 'Open menu'}
                        aria-expanded={menuOpen}
                        onClick={() => setMenuOpen((value) => !value)}
                    >
                        {menuOpen ? <X aria-hidden="true" /> : <Menu aria-hidden="true" />}
                    </button>
                </div>
            </div>

            {menuOpen && (
                <div className="border-t border-[var(--portal-accent-foreground)]/20 px-4 py-3 md:hidden">
                    <PortalNavigation
                        items={[...items, ...sportItems.map((item) => ({ ...item, active: false })), ...moreItems.map((item) => ({ ...item, active: false }))]}
                        className="flex-col items-stretch gap-1"
                        itemClassName="w-full"
                        onNavigate={() => setMenuOpen(false)}
                    />
                    <Link
                        href={props.auth.user ? dashboard().url : login().url}
                        className="mt-2 block rounded-[calc(var(--portal-radius)-0.25rem)] border border-[var(--portal-accent-foreground)]/30 px-3 py-2 text-center text-sm font-medium text-[var(--portal-accent-foreground)]"
                    >
                        {props.auth.user ? 'Dashboard' : 'Staff login'}
                    </Link>
                </div>
            )}
        </header>
    );
}
