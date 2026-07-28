import type { PropsWithChildren } from 'react';

/**
 * Layout for `?kiosk=1` visits (WP-08.5-07) — a bare full-viewport canvas
 * with none of `PublicLayout`'s header, footer, or bottom tab bar. A TV/
 * projector/LED-wall/kiosk display should show nothing but the content
 * itself: no "Sign in"/"Dashboard" button, no site navigation, no
 * back-links — all of which `PublicLayout` would otherwise render. The
 * page itself (`public/scoreboard.tsx`, `public/tally.tsx`) is
 * responsible for its own safe-margin padding, branding strip, and
 * connection-status indicator; this layout only supplies the stripped-
 * down chrome-free shell both pages share.
 */
export default function KioskLayout({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen bg-background text-foreground">
            {children}
        </div>
    );
}
