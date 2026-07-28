import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';

export interface PublicFooterNavItem {
    label: string;
    href: string;
}

interface PublicFooterProps {
    /** Null when no published meet exists yet — the meet-info column
     * is simply omitted rather than showing invented placeholder text. */
    meetName: string | null;
    venue: string | null;
    schoolYear: string | null;
    quickLinks: PublicFooterNavItem[];
}

/**
 * Real multi-column public footer (WP-10-02), replacing the prior
 * one-line footer. Only ever shows real PMMS data (meet name, venue,
 * school year) sourced from the shared `publicNav` prop — no invented
 * office-contact section, per the phase's resolved scope decision.
 */
export function PublicFooter({
    meetName,
    venue,
    schoolYear,
    quickLinks,
}: PublicFooterProps) {
    return (
        <footer className="hidden border-t bg-muted/30 sm:block">
            <div className="mx-auto grid w-full max-w-5xl gap-8 px-4 py-10 sm:grid-cols-3">
                <div className="space-y-2">
                    <Link
                        href={home()}
                        className="flex items-center gap-2 font-semibold"
                    >
                        <span className="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                            <AppLogoIcon className="size-5 fill-current" />
                        </span>
                        <span>PMMS</span>
                    </Link>
                    <p className="text-sm text-muted-foreground">
                        Provincial Meet Management System — DepEd Schools
                        Division Office.
                    </p>
                </div>

                {meetName && (
                    <div className="space-y-2 text-sm">
                        <h2 className="font-semibold text-foreground">
                            Current Meet
                        </h2>
                        <p className="text-muted-foreground">{meetName}</p>
                        {venue && (
                            <p className="text-muted-foreground">{venue}</p>
                        )}
                        {schoolYear && (
                            <p className="text-muted-foreground">
                                SY {schoolYear}
                            </p>
                        )}
                    </div>
                )}

                {quickLinks.length > 0 && (
                    <div className="space-y-2 text-sm">
                        <h2 className="font-semibold text-foreground">
                            Quick Links
                        </h2>
                        <ul className="space-y-1">
                            {quickLinks.map((link) => (
                                <li key={link.label}>
                                    <Link
                                        href={link.href}
                                        className="text-muted-foreground hover:text-foreground"
                                    >
                                        {link.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>

            <div className="border-t px-4 py-4 text-center text-xs text-muted-foreground">
                PMMS Division Edition — DepEd Schools Division Office
            </div>
        </footer>
    );
}
