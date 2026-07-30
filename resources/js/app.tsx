import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import { configureEcho } from '@laravel/echo-react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import KioskLayout from '@/layouts/kiosk-layout';
import PublicLayout from '@/layouts/public-layout';
import SettingsLayout from '@/layouts/settings/layout';
import PortalLayout from '@/apps/portal/layout/portal-layout';
import PortalKioskLayout from '@/apps/portal/layout/portal-kiosk-layout';

/** Same `?kiosk=1` check as `useKioskMode` (`hooks/use-kiosk-mode.ts`) —
 * duplicated rather than shared, since this runs outside React during
 * layout resolution and only has the raw page object, not a hook
 * context. */
const KIOSK_ELIGIBLE_PAGES = new Set(['public/scoreboard', 'public/tally', 'portal/scoreboard', 'portal/tally']);

function isKioskVisit(name: string, url: string): boolean {
    if (!KIOSK_ELIGIBLE_PAGES.has(name)) {
        return false;
    }

    const query = url.split('?')[1] ?? '';

    return new URLSearchParams(query).get('kiosk') === '1';
}

configureEcho({
    broadcaster: 'reverb',
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/** Explicit resolver (replacing the `@inertiajs/vite` pages-shorthand
 * default of `./pages`/`./Pages` only) so the new, strictly separated
 * `resources/js/apps/portal/pages` tree resolves alongside the
 * existing `resources/js/pages` tree without either affecting the
 * other. Portal pages are addressed as `portal/{name}` and stored
 * under the matching `apps/portal/pages/portal/{name}.tsx` path —
 * mirrors `config/inertia.php`'s `pages.paths` exactly, so backend
 * `assertInertia()`/`ensure_pages_exist` checks resolve the same file
 * this glob does. */
const legacyPages = import.meta.glob('./pages/**/*.tsx');
const portalPages = import.meta.glob('./apps/portal/pages/**/*.tsx');

async function resolvePage(name: string): Promise<ResolvedComponent> {
    const module = name.startsWith('portal/')
        ? await portalPages[`./apps/portal/pages/${name}.tsx`]?.()
        : await legacyPages[`./pages/${name}.tsx`]?.();

    if (!module) {
        throw new Error(`Page not found: ${name}`);
    }

    return ((module as { default?: ResolvedComponent }).default ?? module) as ResolvedComponent;
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: resolvePage,
    layout: (name, page) => {
        if (isKioskVisit(name, page?.url ?? '')) {
            return name.startsWith('portal/') ? PortalKioskLayout : KioskLayout;
        }

        switch (true) {
            case name.startsWith('public/'):
                return PublicLayout;
            case name.startsWith('portal/'):
                return PortalLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
