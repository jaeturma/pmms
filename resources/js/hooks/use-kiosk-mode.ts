import { usePage } from '@inertiajs/react';

/**
 * True on a `?kiosk=1` visit (WP-08.5-07) — the one query flag that
 * switches `public/scoreboard.tsx`/`public/tally.tsx` into their TV/
 * projector/LED-wall/kiosk rendering. No new route or backend prop: the
 * same controller action and props already serve the normal page, this
 * only changes what the page (and `app.tsx`'s layout resolver) render
 * with them.
 */
export function useKioskMode(): boolean {
    const { url } = usePage();
    const query = url.split('?')[1] ?? '';

    return new URLSearchParams(query).get('kiosk') === '1';
}
