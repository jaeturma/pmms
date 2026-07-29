import { useEffect, useState } from 'react';

/**
 * Whether the browser tab is currently visible
 * (`document.visibilityState === 'visible'`) — used to pause polling
 * while a tab is in the background (Phase 12 §9's "visibility-aware
 * polling" requirement), so nobody's phone battery/data plan pays for
 * updates nobody is looking at. Every existing poll in this app
 * (`tally.tsx`'s kiosk mode, `scoreboard.tsx`) runs continuously with no
 * pause-on-hidden-tab behavior — this is a small, reusable primitive
 * for any future page that polls, not just this phase's own two.
 */
export function usePageVisible(): boolean {
    const [visible, setVisible] = useState(
        () => document.visibilityState === 'visible',
    );

    useEffect(() => {
        const onVisibilityChange = () =>
            setVisible(document.visibilityState === 'visible');

        document.addEventListener('visibilitychange', onVisibilityChange);

        return () =>
            document.removeEventListener(
                'visibilitychange',
                onVisibilityChange,
            );
    }, []);

    return visible;
}
