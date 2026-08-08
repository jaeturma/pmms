import { useSyncExternalStore } from 'react';

export type PortalTheme = 'light' | 'dark';

const STORAGE_KEY = 'pmms-public-theme';

/** Module-level singleton store — same `useSyncExternalStore` + listener-
 * set shape as `@/hooks/use-appearance`'s admin equivalent, but a fresh,
 * portal-local implementation (own storage key, own default) rather than
 * importing it, so nothing under `apps/portal` depends on a module
 * outside its own tree and an Admin appearance change can never affect
 * the public portal or vice versa. Unlike the admin hook, this never
 * reads `prefers-color-scheme` — Day Mode is the fixed default for every
 * visitor until they explicitly choose Night Mode. */
const listeners = new Set<() => void>();
let currentTheme: PortalTheme = 'light';
let initialized = false;

function readStoredTheme(): PortalTheme {
    if (typeof window === 'undefined') {
        return 'light';
    }

    return localStorage.getItem(STORAGE_KEY) === 'dark' ? 'dark' : 'light';
}

function ensureInitialized(): void {
    if (initialized) {
        return;
    }

    initialized = true;
    currentTheme = readStoredTheme();
}

function subscribe(callback: () => void): () => void {
    listeners.add(callback);

    return () => listeners.delete(callback);
}

function notify(): void {
    listeners.forEach((listener) => listener());
}

function getSnapshot(): PortalTheme {
    ensureInitialized();

    return currentTheme;
}

/** Day Mode, unconditionally — the one value this app would ever render
 * before any client-only state (e.g. a server-rendered snapshot) could
 * know a stored preference. This app has no SSR today, but keeping this
 * honest costs nothing. */
function getServerSnapshot(): PortalTheme {
    return 'light';
}

function setStoredTheme(theme: PortalTheme): void {
    currentTheme = theme;

    if (typeof window !== 'undefined') {
        localStorage.setItem(STORAGE_KEY, theme);
    }

    notify();
}

export type UsePortalThemeReturn = {
    readonly theme: PortalTheme;
    readonly toggleTheme: () => void;
};

/** The public portal's Day/Night preference — `localStorage`-only, no
 * login required, no backend request. Shared across every component that
 * calls this hook (the layout, which applies `data-theme` to its own
 * `.pmms-portal` root, and the header's toggle button) via the module
 * singleton above, so they always agree without prop-drilling. */
export function usePortalTheme(): UsePortalThemeReturn {
    const theme = useSyncExternalStore(
        subscribe,
        getSnapshot,
        getServerSnapshot,
    );

    const toggleTheme = (): void => {
        setStoredTheme(theme === 'dark' ? 'light' : 'dark');
    };

    return { theme, toggleTheme } as const;
}
