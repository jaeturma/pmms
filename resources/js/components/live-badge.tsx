import { cn } from '@/lib/utils';

type Props = {
    label?: string;
    className?: string;
};

/**
 * The system's one "this is happening right now" indicator (Phase 8.5,
 * WP-08.5-02) — a small red pill with a breathing dot
 * (`--animate-pulse-live`, app.css), replacing the plain static
 * `Badge variant="destructive"` used ad hoc for "Live" text before this
 * WP. Deliberately a slow opacity pulse, not a flash/blink — the motion
 * guidelines explicitly warn against flashing — and the dot alone is
 * `aria-hidden`; the visible "Live" text already carries the meaning for
 * assistive tech, so nothing is announced twice. Automatically inert
 * under `prefers-reduced-motion` via the global reset in app.css, no
 * extra work needed here.
 */
export function LiveBadge({ label = 'Live', className = '' }: Props) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full bg-destructive px-2.5 py-0.5 text-xs font-semibold text-white',
                className,
            )}
        >
            <span
                aria-hidden="true"
                className="size-1.5 animate-pulse-live rounded-full bg-white"
            />
            {label}
        </span>
    );
}
