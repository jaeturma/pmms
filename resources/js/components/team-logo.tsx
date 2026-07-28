/**
 * WP-10-11: every entry measured at real WCAG contrast against the
 * `text-white` this component pairs it with (OKLCH → linear sRGB →
 * relative luminance → ratio, the exact method
 * `docs/ui-ux/accessibility-review.md` already established) — the
 * original Tailwind 500/600 weights ranged only ~2.15:1–4.40:1, all
 * failing AA's 4.5:1 even though `aria-hidden` (this badge is
 * decorative, the real name is always visible as adjacent text — see
 * this component's own doc comment). WCAG 1.4.3 contrast still applies
 * to visually-presented text regardless of `aria-hidden`, which only
 * removes an element from the *assistive-tech* tree, not from what a
 * sighted low-vision user perceives — so this was a real, fixable gap,
 * not an exemption. Every hue's own 700 weight is the first uniform
 * tier where all eight measure ≥4.5:1 (5.05:1–7.29:1, see the
 * completion report for the full per-color table) — same hue angles,
 * just darker, so each municipality keeps a visually distinct color.
 */
const PALETTE = [
    'bg-blue-700',
    'bg-emerald-700',
    'bg-amber-700',
    'bg-rose-700',
    'bg-violet-700',
    'bg-cyan-700',
    'bg-orange-700',
    'bg-teal-700',
] as const;

/** Deterministic so the same name always gets the same color. */
function colorFor(name: string): string {
    let hash = 0;

    for (let i = 0; i < name.length; i++) {
        hash = (hash * 31 + name.charCodeAt(i)) | 0;
    }

    return PALETTE[Math.abs(hash) % PALETTE.length];
}

function initialsFor(name: string): string {
    const words = name.replace(/[—–-]/g, ' ').trim().split(/\s+/);

    if (words.length === 0 || words[0] === '') {
        return '?';
    }

    if (words.length === 1) {
        return words[0].slice(0, 2).toUpperCase();
    }

    return (words[0][0] + words[words.length - 1][0]).toUpperCase();
}

type Props = {
    name: string;
    className?: string;
    shape?: 'circle' | 'square';
};

/**
 * A placeholder "team logo" — a deterministic colored initials badge
 * generated from a name (municipality, school, or delegation label). No
 * logo upload infrastructure exists for any of those yet, so this stands
 * in wherever a real one would go: the public landing page's competing
 * municipalities, and each side of a live scoreboard.
 */
export function TeamLogo({ name, className = '', shape = 'circle' }: Props) {
    return (
        <div
            aria-hidden="true"
            className={`flex shrink-0 items-center justify-center font-semibold text-white ${shape === 'circle' ? 'rounded-full' : 'rounded-lg'} ${colorFor(name)} ${className}`}
        >
            {initialsFor(name)}
        </div>
    );
}
