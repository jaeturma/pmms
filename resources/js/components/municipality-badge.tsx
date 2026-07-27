const PALETTE = [
    'bg-blue-500',
    'bg-emerald-500',
    'bg-amber-500',
    'bg-rose-500',
    'bg-violet-500',
    'bg-cyan-600',
    'bg-orange-500',
    'bg-teal-500',
] as const;

/** Deterministic so the same municipality always gets the same color. */
function colorFor(name: string): string {
    let hash = 0;

    for (let i = 0; i < name.length; i++) {
        hash = (hash * 31 + name.charCodeAt(i)) | 0;
    }

    return PALETTE[Math.abs(hash) % PALETTE.length];
}

function initialsFor(name: string): string {
    const words = name.replace(/[—–-]/g, ' ').trim().split(/\s+/);

    if (words.length === 1) {
        return words[0].slice(0, 2).toUpperCase();
    }

    return (words[0][0] + words[words.length - 1][0]).toUpperCase();
}

type Props = {
    name: string;
    className?: string;
};

/**
 * A placeholder logo for a competing municipality — no logo upload exists
 * yet, so this generates a deterministic colored initials avatar instead.
 */
export function MunicipalityBadge({ name, className = '' }: Props) {
    return (
        <div
            aria-hidden="true"
            className={`flex items-center justify-center rounded-full font-semibold text-white ${colorFor(name)} ${className}`}
        >
            {initialsFor(name)}
        </div>
    );
}
