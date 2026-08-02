import { createInertiaApp } from '@inertiajs/react';
import type { ResolvedComponent } from '@inertiajs/react';
import type { ComponentType } from 'react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';

/** Same `?kiosk=1` check as `useKioskMode` (`hooks/use-kiosk-mode.ts`) —
 * duplicated rather than shared, since this runs outside React during
 * layout resolution and only has the raw page object, not a hook
 * context. */
const KIOSK_ELIGIBLE_PAGES = new Set([
    'public/scoreboard',
    'public/tally',
    'portal/scoreboard',
    'portal/tally',
]);

function isKioskVisit(name: string, url: string): boolean {
    if (!KIOSK_ELIGIBLE_PAGES.has(name)) {
        return false;
    }

    const query = url.split('?')[1] ?? '';

    return new URLSearchParams(query).get('kiosk') === '1';
}

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

type Layout =
    | ComponentType<{ children: React.ReactNode }>
    | ComponentType<{ children: React.ReactNode }>[];

/** Populated as a side effect of `resolvePage()` below, read back
 * synchronously by the `layout` callback passed to `createInertiaApp` —
 * see that callback for why this indirection exists. Keyed by page name
 * (plus kiosk state, since the same page name resolves to two different
 * layouts depending on `?kiosk=1`) rather than a single shared variable:
 * this app uses Inertia's hover `prefetch` (`app-sidebar.tsx` and
 * others), which resolves a *different* page's layout in the background
 * while the current one is still on screen — a single last-write-wins
 * variable would let that background resolution briefly apply the wrong
 * layout to the page actually being displayed on any incidental re-render. */
const layoutCache = new Map<string, Layout>();

function layoutCacheKey(name: string, url: string): string {
    return `${name}:${isKioskVisit(name, url) ? 'kiosk' : 'normal'}`;
}

/**
 * Each of the seven layouts is imported lazily, only for the one page
 * actually being visited. The previous version statically imported all
 * seven (Admin/Auth/Kiosk/Public/Settings/Portal/PortalKiosk) at the top
 * of this file — since app.tsx is Inertia's one entry chunk, that pulled
 * every layout's full dependency tree (including the admin sidebar's
 * ~20 individually-chunked lucide icons) into app.tsx's *static* import
 * graph on every single page load, regardless of which single layout
 * that page actually needed. The entry's manifest `imports` count ended
 * up near 90 chunks, each getting its own `modulepreload` response
 * header on every request — a real, measurable inefficiency (~9KB of
 * headers on the plain home page) and a plausible contributor to
 * intermittent "Premature end of script headers" crashes seen in
 * Apache's error log under mod_fcgid.
 */
async function resolveLayout(name: string, url: string): Promise<Layout> {
    if (isKioskVisit(name, url)) {
        return name.startsWith('portal/')
            ? (await import('@/apps/portal/layout/portal-kiosk-layout')).default
            : (await import('@/layouts/kiosk-layout')).default;
    }

    if (name.startsWith('public/')) {
        return (await import('@/layouts/public-layout')).default;
    }

    if (name.startsWith('portal/')) {
        return (await import('@/apps/portal/layout/portal-layout')).default;
    }

    if (name.startsWith('auth/')) {
        return (await import('@/layouts/auth-layout')).default;
    }

    if (name.startsWith('settings/')) {
        const [appLayout, settingsLayout] = await Promise.all([
            import('@/layouts/app-layout'),
            import('@/layouts/settings/layout'),
        ]);

        return [appLayout.default, settingsLayout.default];
    }

    return (await import('@/layouts/app-layout')).default;
}

async function resolvePage(
    name: string,
    page?: { url: string },
): Promise<ResolvedComponent> {
    const module = name.startsWith('portal/')
        ? await portalPages[`./apps/portal/pages/${name}.tsx`]?.()
        : await legacyPages[`./pages/${name}.tsx`]?.();

    if (!module) {
        throw new Error(`Page not found: ${name}`);
    }

    // Resolved (and cached) before the page component is returned, so
    // it's always ready by the time Inertia's synchronous `layout`
    // callback below looks it up for this same page transition.
    const url = page?.url ?? '';
    const key = layoutCacheKey(name, url);

    if (!layoutCache.has(key)) {
        layoutCache.set(key, await resolveLayout(name, url));
    }

    return ((module as { default?: ResolvedComponent }).default ??
        module) as ResolvedComponent;
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: resolvePage,
    // Not read directly by most pages — Inertia only calls this when a
    // page's own `Component.layout` is absent, or (the common case here,
    // ~40 pages) a plain `{ breadcrumbs: [...] }` props object, in which
    // case Inertia uses *this* return value as the actual layout
    // component(s) and merges those breadcrumbs in as props. Kept as a
    // synchronous callback (Inertia's own API requires that) reading the
    // already-resolved entry from `layoutCache` populated by
    // `resolvePage()` above, rather than importing every layout eagerly
    // here.
    layout: (name, page) =>
        layoutCache.get(layoutCacheKey(name, page?.url ?? '')) ?? [],
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
