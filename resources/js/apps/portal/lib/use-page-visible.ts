import { useEffect, useState } from 'react';

/** Local to the portal — a fresh implementation rather than importing
 * `@/hooks/use-page-visible`, so nothing under `apps/portal` depends
 * on a module outside its own tree. */
export function usePortalPageVisible(): boolean {
    const [visible, setVisible] = useState(() => document.visibilityState === 'visible');

    useEffect(() => {
        const handler = () => setVisible(document.visibilityState === 'visible');

        document.addEventListener('visibilitychange', handler);

        return () => document.removeEventListener('visibilitychange', handler);
    }, []);

    return visible;
}
