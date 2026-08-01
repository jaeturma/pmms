import { cn } from '@/apps/portal/lib/utils';

type PortalTorchIconProps = {
    className?: string;
};

/**
 * The landing hero's "official torch" mark — an original abstract line
 * mark built from SVG paths (per the brief: no AI-generated artwork),
 * not a stock icon. Three overlapping flame petals in the Division's
 * gold accent sit above a minimal cup-and-handle silhouette in the ink
 * tone. The flame petals gently breathe via `.portal-torch-flame`
 * (`portal.css`) — disabled under `prefers-reduced-motion` by the same
 * global reset every other portal animation already relies on.
 */
export function PortalTorchIcon({ className }: PortalTorchIconProps) {
    return (
        <svg
            viewBox="0 0 64 96"
            aria-hidden="true"
            className={cn('overflow-visible', className)}
        >
            <defs>
                <linearGradient id="portal-torch-flame-gradient" x1="0" y1="1" x2="0" y2="0">
                    <stop offset="0%" stopColor="var(--portal-maroon)" />
                    <stop offset="55%" stopColor="var(--portal-accent)" />
                    <stop offset="100%" stopColor="#fff4cf" />
                </linearGradient>
            </defs>

            {/* Handle */}
            <rect x="29" y="46" width="6" height="34" rx="3" fill="currentColor" opacity="0.85" />
            {/* Cup */}
            <path d="M18 40 L46 40 L40 52 Q32 58 24 52 Z" fill="currentColor" opacity="0.85" />

            {/* Flame petals — each on its own transform-origin so the flicker
                keyframe can scale them independently around their own base. */}
            <g style={{ transformOrigin: '32px 42px' }} className="portal-torch-flame portal-torch-flame-1">
                <path
                    d="M32 4c7 8 11 16 11 24 0 8-5 14-11 14s-11-6-11-14c0-8 4-16 11-24Z"
                    fill="url(#portal-torch-flame-gradient)"
                />
            </g>
            <g style={{ transformOrigin: '24px 40px' }} className="portal-torch-flame portal-torch-flame-2">
                <path
                    d="M23 16c4 6 6 11 6 16 0 5-3 9-7 9s-7-4-7-9c0-5 3-10 8-16Z"
                    fill="url(#portal-torch-flame-gradient)"
                    opacity="0.85"
                />
            </g>
            <g style={{ transformOrigin: '40px 40px' }} className="portal-torch-flame portal-torch-flame-3">
                <path
                    d="M41 16c4 6 6 11 6 16 0 5-3 9-7 9s-7-4-7-9c0-5 3-10 8-16Z"
                    fill="url(#portal-torch-flame-gradient)"
                    opacity="0.85"
                />
            </g>
        </svg>
    );
}
