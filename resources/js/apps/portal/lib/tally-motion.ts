/**
 * Single source of truth for the live `/tally` animation timing.
 *
 * The CSS side (`resources/css/portal.css`) reads the same numbers from
 * the `--portal-tally-motion` / `--portal-tally-highlight` custom
 * properties — keep the two in sync. Inline transitions built in JS use
 * the CSS variable directly so only the stylesheet needs the literal.
 */

/** Medal-count pulse and delegation/team row rank movement. */
export const TALLY_MOTION_MS = 3000;

/** The lingering "just updated" tint on a row / number. */
export const TALLY_HIGHLIGHT_MS = 2800;

/** Easing shared with the rest of the portal (`--portal-ease`). */
export const TALLY_EASING = 'cubic-bezier(0.22, 1, 0.36, 1)';

/** Transition value for a JS-driven inline style — resolves the CSS
 * variable at the element, with a literal fallback. */
export const TALLY_ROW_TRANSITION = `transform var(--portal-tally-motion, ${TALLY_MOTION_MS}ms) var(--portal-ease, ${TALLY_EASING})`;
