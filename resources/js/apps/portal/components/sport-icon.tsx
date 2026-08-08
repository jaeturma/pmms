import { Dumbbell, Footprints, Swords, Trophy, Waves } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

/**
 * Purely decorative per-sport icon — a reasonable visual match by sport
 * name, not a claim about the sport's actual equipment/rules. Lightweight
 * local mapping (`lucide-react`, already bundled) rather than fetching an
 * external per-sport icon library; any sport not recognized here (most of
 * the ~28-sport catalog) falls back to a generic `Trophy`, not a guess at
 * an icon that doesn't exist in this project's icon set. Portal-local
 * copy of the same idea as `resources/js/components/sports-medal-strip.
 * tsx`'s `sportIcon()` — not imported from there, since the portal tree is
 * meant to stay self-contained from the legacy public UI components.
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
