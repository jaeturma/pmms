import type { PropsWithChildren } from 'react';

/** Bare, chrome-free shell for `?kiosk=1` visits to portal pages (TV/
 * projector/LED-wall displays) — no header, footer, or nav, just the
 * portal's own theme background. A fresh, portal-local counterpart to
 * the legacy `layouts/kiosk-layout.tsx`, not an import of it. */
export default function PortalKioskLayout({ children }: PropsWithChildren) {
    return <div className="pmms-portal min-h-screen bg-[var(--portal-bg)] text-[var(--portal-fg)]">{children}</div>;
}
