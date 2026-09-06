import { useEffect, useLayoutEffect, useRef } from 'react';
import { usePortalReducedMotion } from '@/apps/portal/lib/use-reduced-motion';

const FLASH_MS = 2400;

// `useLayoutEffect` on the client (the transform must be applied before
// paint or the row flashes at its new spot first); harmless `useEffect`
// where there is no DOM.
const useBeforePaint =
    typeof window === 'undefined' ? useEffect : useLayoutEffect;

type Options = {
    /** key → a value (e.g. Total medals) whose increase since the last
     * commit should flash the row via a `data-updated="true"` attribute. */
    flashOnIncrease?: Map<string, number>;
};

/**
 * FLIP list reordering (plus an optional per-row "just updated" flash)
 * for `/tally`'s standings tables — all imperative, so it never triggers
 * a React re-render.
 *
 * `prevTops` keeps each row's layout offset from the previous commit;
 * after every commit this reads the new offset, applies the inverted
 * difference with an instant transform, then releases it so the row
 * transitions to zero — a smooth slide to its new rank however many
 * places it moved. `offsetTop` (not `getBoundingClientRect().top`) so a
 * page scroll between polls can never corrupt the delta.
 *
 * A no-op — and adds no transitions — when `enabled` is false or the
 * viewer prefers reduced motion.
 */
export function usePortalFlipRows(enabled: boolean, options: Options = {}) {
    const { flashOnIncrease } = options;
    const reduced = usePortalReducedMotion();
    const els = useRef(new Map<string, HTMLElement>());
    const refCallbacks = useRef(
        new Map<string, (el: HTMLElement | null) => void>(),
    );
    const prevTops = useRef(new Map<string, number>());
    const prevFlash = useRef(new Map<string, number>());

    useBeforePaint(() => {
        if (!enabled || reduced) {
            return;
        }

        const seen = new Set<string>();

        els.current.forEach((el, key) => {
            seen.add(key);

            const nextTop = el.offsetTop;
            const previousTop = prevTops.current.get(key);

            prevTops.current.set(key, nextTop);

            if (previousTop !== undefined) {
                const delta = previousTop - nextTop;

                if (Math.abs(delta) >= 1) {
                    el.style.transition = 'none';
                    el.style.transform = `translateY(${delta}px)`;
                    el.style.zIndex = '1';

                    requestAnimationFrame(() => {
                        el.style.transition =
                            'transform 500ms var(--portal-ease, cubic-bezier(0.22, 1, 0.36, 1))';
                        el.style.transform = '';
                    });

                    const clear = () => {
                        el.style.transition = '';
                        el.style.zIndex = '';
                        el.removeEventListener('transitionend', clear);
                    };

                    el.addEventListener('transitionend', clear);
                }
            }

            const nextValue = flashOnIncrease?.get(key);
            const previousValue = prevFlash.current.get(key);

            if (nextValue !== undefined) {
                if (previousValue !== undefined && nextValue > previousValue) {
                    el.setAttribute('data-updated', 'true');
                    window.setTimeout(
                        () => el.removeAttribute('data-updated'),
                        FLASH_MS,
                    );
                }

                prevFlash.current.set(key, nextValue);
            }
        });

        for (const key of [...prevTops.current.keys()]) {
            if (!seen.has(key)) {
                prevTops.current.delete(key);
                prevFlash.current.delete(key);
                refCallbacks.current.delete(key);
            }
        }
    });

    /** Stable ref callback per row key — no ref churn between renders. */
    const rowRef = (key: string) => {
        let callback = refCallbacks.current.get(key);

        if (!callback) {
            callback = (el: HTMLElement | null) => {
                if (el) {
                    els.current.set(key, el);
                } else {
                    els.current.delete(key);
                }
            };
            refCallbacks.current.set(key, callback);
        }

        return callback;
    };

    return { rowRef, reduced };
}
