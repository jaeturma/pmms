import { cn } from '@/apps/portal/lib/utils';

type MunicipalityCrestProps = {
    name: string;
    size?: 'md' | 'lg';
    className?: string;
};

/** A visible, on-brand stand-in for a real municipality logo. PMMS has
 * no logo-upload infrastructure or stored image for any municipality
 * (no DB column, no uploaded file, anywhere) — this deterministically
 * picks one of the Division's own colors per municipality name, so
 * every crest still reads as branded rather than a generic gray
 * placeholder, until real logo uploads exist. */
const TONES = [
    { bg: 'var(--portal-accent)', fg: 'var(--portal-accent-foreground)', ring: 'var(--portal-accent)' },
    { bg: 'var(--portal-ink)', fg: 'var(--portal-ink-foreground)', ring: 'var(--portal-ink)' },
    { bg: 'var(--portal-maroon)', fg: 'var(--portal-maroon-foreground)', ring: 'var(--portal-maroon)' },
] as const;

function hashName(name: string): number {
    let hash = 0;

    for (let i = 0; i < name.length; i++) {
        hash = (hash * 31 + name.charCodeAt(i)) >>> 0;
    }

    return hash;
}

function initialsFor(name: string): string {
    const words = name.trim().split(/\s+/);

    if (words.length === 1) {
        return words[0].slice(0, 2).toUpperCase();
    }

    return (words[0][0] + words[words.length - 1][0]).toUpperCase();
}

const sizeClasses: Record<NonNullable<MunicipalityCrestProps['size']>, string> = {
    md: 'size-14 text-lg',
    lg: 'size-20 text-2xl sm:size-24 sm:text-3xl',
};

export function MunicipalityCrest({ name, size = 'md', className }: MunicipalityCrestProps) {
    const tone = TONES[hashName(name) % TONES.length];

    return (
        <span
            className={cn(
                'portal-icon-badge shrink-0 font-bold shadow-md ring-2 ring-offset-2 ring-offset-[var(--portal-surface)]',
                sizeClasses[size],
                className,
            )}
            style={{ backgroundColor: tone.bg, color: tone.fg, ['--tw-ring-color' as string]: tone.ring }}
            aria-hidden="true"
        >
            {initialsFor(name)}
        </span>
    );
}
