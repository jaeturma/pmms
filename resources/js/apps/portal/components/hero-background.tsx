/**
 * The landing hero's animated background — built entirely from CSS
 * gradients, blurred shapes, and inline SVG line-art per the brief (no
 * AI-generated imagery, so it stays fast and infinitely crisp at any
 * size). Three soft, slow-drifting color blobs in the Division's own
 * palette (gold/maroon/ink) sit under a faint diagonal "track lane" and
 * orbit-ring motif — abstract, sports-inspired geometry, not a literal
 * copy of any real emblem. Motion is slow and subtle (18-24s drifts) and
 * fully disabled under `prefers-reduced-motion` via the same global
 * reset every other portal animation uses (`portal.css`).
 */
export function PortalHeroBackground() {
    return (
        <div aria-hidden="true" className="pointer-events-none absolute inset-0 overflow-hidden">
            <div className="absolute inset-0" style={{ background: 'var(--portal-gradient-hero)' }} />

            <div
                className="portal-hero-blob absolute -top-24 -left-20 size-[420px] rounded-full opacity-40 blur-3xl"
                style={{ background: 'var(--portal-accent)', animationDelay: '0s' }}
            />
            <div
                className="portal-hero-blob absolute top-1/3 -right-28 size-[380px] rounded-full opacity-30 blur-3xl"
                style={{ background: 'var(--portal-maroon)', animationDelay: '-6s' }}
            />
            <div
                className="portal-hero-blob absolute -bottom-32 left-1/4 size-[340px] rounded-full opacity-25 blur-3xl"
                style={{ background: 'var(--portal-accent)', animationDelay: '-12s' }}
            />

            <svg className="absolute inset-0 size-full opacity-[0.08]" preserveAspectRatio="none">
                <defs>
                    <pattern id="portal-hero-lanes" width="64" height="64" patternUnits="userSpaceOnUse" patternTransform="rotate(-24)">
                        <line x1="0" y1="0" x2="0" y2="64" stroke="white" strokeWidth="2" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#portal-hero-lanes)" />
            </svg>

            <svg className="portal-hero-orbit absolute top-1/2 left-1/2 size-[640px] -translate-x-1/2 -translate-y-1/2 opacity-[0.14] sm:size-[820px]" viewBox="0 0 200 200">
                <circle cx="100" cy="100" r="92" fill="none" stroke="white" strokeWidth="0.75" />
                <circle cx="100" cy="100" r="70" fill="none" stroke="white" strokeWidth="0.75" />
                <circle cx="100" cy="100" r="48" fill="none" stroke="white" strokeWidth="0.75" />
            </svg>

            <div className="absolute inset-0 bg-gradient-to-t from-black/35 via-transparent to-black/10" />
        </div>
    );
}
