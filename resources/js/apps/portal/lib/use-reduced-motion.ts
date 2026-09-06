import { useEffect, useState } from 'react';

const QUERY = '(prefers-reduced-motion: reduce)';

/** Local to the portal — mirrors the reasoning behind
 * `use-page-visible.ts`: a self-contained implementation so nothing
 * under `apps/portal` depends on a module outside its own tree.
 *
 * `true` while the viewer has asked the OS to minimise animation; the
 * tally page then updates numbers and row order instantly instead of
 * transitioning them. */
export function usePortalReducedMotion(): boolean {
    const [reduced, setReduced] = useState(
        () =>
            typeof window !== 'undefined' &&
            typeof window.matchMedia === 'function' &&
            window.matchMedia(QUERY).matches,
    );

    useEffect(() => {
        if (typeof window.matchMedia !== 'function') {
            return;
        }

        const media = window.matchMedia(QUERY);
        const handler = () => setReduced(media.matches);

        handler();
        media.addEventListener('change', handler);

        return () => media.removeEventListener('change', handler);
    }, []);

    return reduced;
}
