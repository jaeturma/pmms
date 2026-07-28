import { createInertiaApp } from '@inertiajs/react';
import { configureEcho } from '@laravel/echo-react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import KioskLayout from '@/layouts/kiosk-layout';
import PublicLayout from '@/layouts/public-layout';
import SettingsLayout from '@/layouts/settings/layout';

/** Same `?kiosk=1` check as `useKioskMode` (`hooks/use-kiosk-mode.ts`) —
 * duplicated rather than shared, since this runs outside React during
 * layout resolution and only has the raw page object, not a hook
 * context. */
const KIOSK_ELIGIBLE_PAGES = new Set(['public/scoreboard', 'public/tally']);

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

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name, page) => {
        if (isKioskVisit(name, page?.url ?? '')) {
            return KioskLayout;
        }

        switch (true) {
            case name.startsWith('public/'):
                return PublicLayout;
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
