import { useEffect, useLayoutEffect, useRef } from 'react';
import {
    TALLY_HIGHLIGHT_MS,
    TALLY_MOTION_MS,
    TALLY_ROW_TRANSITION,
} from '@/apps/portal/lib/tally-motion';
import { usePortalReducedMotion } from '@/apps/portal/lib/use-reduced-motion';

// `useLayoutEffect` on the client (the transform must be applied before
// paint or the row flashes at its new spot first); harmless `useEffect`
// where there is no DOM.
const useBeforePaint =
    typeof window === 'undefined' ? useEffect : useLayoutEffect;

/** Current animated translateY of a row, mid-transition — so an
 * interrupted slide resumes from where it visually is rather than
 * snapping. */
function currentTranslateY(el: HTMLElement): number {
    const transform = getComputedStyle(el).transform;

    if (!transform || transform === 'none') {
        return 0;
    }

    const matrix = transform.match(/matrix\(([^)]+)\)/);

    if (matrix) {
        return Number.parseFloat(matrix[1].split(',')[5]) || 0;
    }

    const matrix3d = transform.match(/matrix3d\(([^)]+)\)/);

    if (matrix3d) {
        return Number.parseFloat(matrix3d[1].split(',')[13]) || 0;
    }

    return 0;
}

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
 * difference (carrying over any in-flight translate so a mid-slide poll
 * reconciles without a jump) as an instant transform, then releases it
 * so the row transitions to zero over ~3s — a smooth slide to its new
 * rank however many places it moved. `offsetTop` (not
 * `getBoundingClientRect().top`) so a page scroll between polls can
 * never corrupt the delta.
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
    const settleTimers = useRef(new Map<string, number>());

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
                // Classic FLIP delta + whatever is left of an in-flight
                // slide, so a second poll mid-animation continues smoothly
                // from the row's current on-screen position.
                const delta = previousTop - nextTop + currentTranslateY(el);

                if (Math.abs(delta) >= 1) {
                    const previousTimer = settleTimers.current.get(key);

                    if (previousTimer !== undefined) {
                        window.clearTimeout(previousTimer);
                    }

                    el.style.transition = 'none';
                    el.style.transform = `translateY(${delta}px)`;
                    el.style.zIndex = '1';

                    requestAnimationFrame(() => {
                        el.style.transition = TALLY_ROW_TRANSITION;
                        el.style.transform = '';
                    });

                    settleTimers.current.set(
                        key,
                        window.setTimeout(() => {
                            el.style.transition = '';
                            el.style.zIndex = '';
                            settleTimers.current.delete(key);
                        }, TALLY_MOTION_MS + 80),
                    );
                }
            }

            const nextValue = flashOnIncrease?.get(key);
            const previousValue = prevFlash.current.get(key);

            if (nextValue !== undefined) {
                if (previousValue !== undefined && nextValue > previousValue) {
                    el.setAttribute('data-updated', 'true');
                    window.setTimeout(
                        () => el.removeAttribute('data-updated'),
                        TALLY_HIGHLIGHT_MS + 120,
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

                const timer = settleTimers.current.get(key);

                if (timer !== undefined) {
                    window.clearTimeout(timer);
                    settleTimers.current.delete(key);
                }
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
