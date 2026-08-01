import { cn } from '@/apps/portal/lib/utils';

type MunicipalityCrestProps = {
    name: string;
    logoUrl?: string | null;
    size?: 'sm' | 'md' | 'lg';
    /** 'circle' (default) — the usual ringed badge, used for rankings/
     * standings/home. 'square' drops the circular crop and the ring
     * outline entirely, for both the real-logo image and the initials
     * placeholder — used on the live scoreboards and the emphasized
     * ranking tables, where a plain square tile reads better than a
     * ringed circle. */
    shape?: 'circle' | 'square';
    className?: string;
};

/** A visible, on-brand stand-in for a real municipality logo. Renders the
 * admin-uploaded crest (District::logo, served by `districts.logo`) when
 * `logoUrl` is given; falls back to a deterministic initials badge —
 * picking one of the Division's own colors per municipality name — for
 * every municipality that hasn't had a crest uploaded yet. */
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

export function crestToneFor(name: string): (typeof TONES)[number] {
    return TONES[hashName(name) % TONES.length];
}

export function initialsFor(name: string): string {
    const words = name.trim().split(/\s+/);

    if (words.length === 1) {
        return words[0].slice(0, 2).toUpperCase();
    }

    return (words[0][0] + words[words.length - 1][0]).toUpperCase();
}

const sizeClasses: Record<NonNullable<MunicipalityCrestProps['size']>, string> = {
    sm: 'size-8 text-xs ring-1',
    md: 'size-14 text-lg ring-2',
    lg: 'size-20 text-2xl sm:size-24 sm:text-3xl ring-2',
};

export function MunicipalityCrest({ name, logoUrl, size = 'md', shape = 'circle', className }: MunicipalityCrestProps) {
    if (logoUrl) {
        return (
            <img
                src={logoUrl}
                alt={`${name} crest`}
                className={cn(
                    sizeClasses[size],
                    'shrink-0 object-cover',
                    shape === 'circle'
                        ? 'rounded-full shadow-md ring-2 ring-offset-2 ring-offset-[var(--portal-surface)]'
                        : 'ring-0',
                    className,
                )}
                style={shape === 'circle' ? { ['--tw-ring-color' as string]: 'var(--portal-border)' } : undefined}
            />
        );
    }

    const tone = crestToneFor(name);

    return (
        <span
            className={cn(
                sizeClasses[size],
                'inline-flex shrink-0 items-center justify-center font-bold shadow-md ring-offset-2 ring-offset-[var(--portal-surface)]',
                shape === 'circle' ? 'rounded-full' : 'rounded-none ring-0',
                className,
            )}
            style={{
                backgroundColor: tone.bg,
                color: tone.fg,
                ...(shape === 'circle' ? { ['--tw-ring-color' as string]: tone.ring } : {}),
            }}
            aria-hidden="true"
        >
            {initialsFor(name)}
        </span>
    );
}

type MunicipalityWatermarkProps = {
    name: string;
    className?: string;
};

/** A large, faint decorative crest for card backgrounds — the same
 * deterministic color/initials as `MunicipalityCrest`, just huge and
 * low-opacity so it reads as a watermark behind real content rather
 * than competing with it. */
export function MunicipalityWatermark({ name, className }: MunicipalityWatermarkProps) {
    const tone = crestToneFor(name);

    return (
        <span
            aria-hidden="true"
            className={cn(
                'pointer-events-none absolute flex items-center justify-center overflow-hidden rounded-full font-black opacity-[0.07] select-none',
                className,
            )}
            style={{ color: tone.bg }}
        >
            {initialsFor(name)}
        </span>
    );
}
