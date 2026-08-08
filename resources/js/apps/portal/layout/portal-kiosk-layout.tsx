import type { PropsWithChildren } from 'react';
import { usePortalTheme } from '@/apps/portal/lib/use-portal-theme';

/** Bare, chrome-free shell for `?kiosk=1` visits to portal pages (TV/
 * projector/LED-wall displays) — no header, footer, or nav, just the
 * portal's own theme background. A fresh, portal-local counterpart to
 * the legacy `layouts/kiosk-layout.tsx`, not an import of it. No toggle
 * button here (no nav to put one in, and an unattended display shouldn't
 * have an interactive control anyway) — but it still honors whatever
 * Day/Night preference was last set on this same browser, via the same
 * shared `usePortalTheme()` store the header's toggle writes to. */
export default function PortalKioskLayout({ children }: PropsWithChildren) {
    const { theme } = usePortalTheme();

    return (
        <div
            className="pmms-portal min-h-screen bg-[var(--portal-bg)] text-[var(--portal-fg)]"
            data-theme={theme}
        >
            {children}
        </div>
    );
}
