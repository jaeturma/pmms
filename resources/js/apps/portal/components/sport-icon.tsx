import {
    Dumbbell,
    Footprints,
    Goal,
    Grid3x3,
    HandMetal,
    Swords,
    Target,
    Trophy,
    Users,
    Volleyball,
    Waves,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/apps/portal/lib/utils';

/**
 * A colorful pictogram per catalog sport, keyed by `SportPortalSlug`
 * value — mirrors the backend enum by hand, same convention as
 * `sport-terminology.ts`. `lucide-react` has no dedicated icon for most
 * individual sports (confirmed against the full icon set before writing
 * this — no basketball/football/badminton/table-tennis/chess-piece
 * icons exist), so several sports intentionally share a base shape
 * (ball/combat/racket-adjacent) and are told apart by their own distinct
 * color instead of a fabricated bespoke icon. Colors are chosen to read
 * clearly on both Day and Night Mode surfaces without a separate dark
 * variant — a saturated 500/600-level foreground on a low-opacity tint
 * background, the same "fixed identity color, not inverted" approach the
 * Day/Night theme already uses for medal colors and the LIVE indicator.
 */
type SportIconStyle = {
    icon: LucideIcon;
    bg: string;
    fg: string;
};

const SPORT_ICON_STYLES: Record<string, SportIconStyle> = {
    athletics: { icon: Footprints, bg: 'bg-orange-500/15', fg: 'text-orange-600' },
    archery: { icon: Target, bg: 'bg-rose-500/15', fg: 'text-rose-600' },
    arnis: { icon: Swords, bg: 'bg-amber-500/15', fg: 'text-amber-600' },
    badminton: { icon: Volleyball, bg: 'bg-lime-500/15', fg: 'text-lime-600' },
    baseball: { icon: Trophy, bg: 'bg-red-500/15', fg: 'text-red-600' },
    basketball: { icon: Volleyball, bg: 'bg-orange-500/20', fg: 'text-orange-600' },
    billiard: { icon: Target, bg: 'bg-emerald-500/15', fg: 'text-emerald-600' },
    bocce: { icon: Volleyball, bg: 'bg-teal-500/15', fg: 'text-teal-600' },
    boxing: { icon: HandMetal, bg: 'bg-red-500/20', fg: 'text-red-700' },
    chess: { icon: Grid3x3, bg: 'bg-indigo-500/15', fg: 'text-indigo-600' },
    dancesports: { icon: Users, bg: 'bg-pink-500/15', fg: 'text-pink-600' },
    football: { icon: Goal, bg: 'bg-green-500/15', fg: 'text-green-600' },
    futsal: { icon: Goal, bg: 'bg-teal-500/20', fg: 'text-teal-700' },
    'goal-ball': { icon: Goal, bg: 'bg-purple-500/15', fg: 'text-purple-600' },
    gymnastics: { icon: Users, bg: 'bg-fuchsia-500/15', fg: 'text-fuchsia-600' },
    'pencak-silat': { icon: Swords, bg: 'bg-emerald-500/20', fg: 'text-emerald-700' },
    swimming: { icon: Waves, bg: 'bg-blue-500/15', fg: 'text-blue-600' },
    weightlifting: { icon: Dumbbell, bg: 'bg-stone-500/15', fg: 'text-stone-600' },
    'sepak-takraw': { icon: Volleyball, bg: 'bg-cyan-500/15', fg: 'text-cyan-600' },
    softball: { icon: Trophy, bg: 'bg-yellow-500/15', fg: 'text-yellow-600' },
    taekwondo: { icon: Swords, bg: 'bg-sky-500/15', fg: 'text-sky-600' },
    'table-tennis': { icon: Volleyball, bg: 'bg-cyan-500/20', fg: 'text-cyan-700' },
    tennis: { icon: Volleyball, bg: 'bg-lime-500/20', fg: 'text-lime-700' },
    volleyball: { icon: Volleyball, bg: 'bg-sky-500/20', fg: 'text-sky-700' },
    wrestling: { icon: Users, bg: 'bg-stone-500/20', fg: 'text-stone-700' },
    wushu: { icon: Swords, bg: 'bg-red-500/15', fg: 'text-red-600' },
    'paragames-athletics': { icon: Footprints, bg: 'bg-violet-500/15', fg: 'text-violet-600' },
    'paragames-swimming': { icon: Waves, bg: 'bg-violet-500/20', fg: 'text-violet-700' },
};

const FALLBACK_STYLE: SportIconStyle = { icon: Trophy, bg: 'bg-slate-500/15', fg: 'text-slate-600' };

export function sportIconStyle(slug: string): SportIconStyle {
    return SPORT_ICON_STYLES[slug] ?? FALLBACK_STYLE;
}

/**
 * Legacy name-matching lookup, kept for the handful of existing callers
 * (Teams feature) that key off a free-text sport name rather than a
 * `SportPortalSlug`. New code should prefer `sportIconStyle()`.
 */
export function sportIcon(name: string): LucideIcon {
    const lower = name.toLowerCase();

    if (lower.includes('swim')) {
        return Waves;
    }

    if (lower.includes('athletic') || lower.includes('track')) {
        return Footprints;
    }

    if (lower.includes('weightlift')) {
        return Dumbbell;
    }

    if (
        lower.includes('arnis') ||
        lower.includes('boxing') ||
        lower.includes('taekwondo') ||
        lower.includes('wushu') ||
        lower.includes('pencak silat') ||
        lower.includes('wrestling') ||
        lower.includes('fenc')
    ) {
        return Swords;
    }

    return Trophy;
}

export function PortalSportIcon({ slug, className }: { slug: string; className?: string }) {
    const { icon: Icon, bg, fg } = sportIconStyle(slug);

    return (
        <span className={cn('inline-flex items-center justify-center rounded-full', bg, className)}>
            <Icon className={cn(fg)} aria-hidden="true" />
        </span>
    );
}
