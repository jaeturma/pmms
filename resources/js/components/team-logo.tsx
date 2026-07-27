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
