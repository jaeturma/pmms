import { Dumbbell, Footprints, LayoutGrid, Swords, Waves } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { SportRow } from '@/components/medals-by-sport-card';

/**
 * Purely decorative per-sport icon (WP-08-09) — no functional meaning,
 * just a reasonable visual match by sport name. Ball sports and any
 * unrecognized sport share the same generic fallback rather than
 * guessing at icons that don't exist in this project's icon set.
 */
function sportIcon(name: string): LucideIcon {
    const lower = name.toLowerCase();

    if (lower.includes('swim')) {
        return Waves;
    }

    if (lower.includes('athletic') || lower.includes('track')) {
        return Footprints;
    }

    if (
        lower.includes('arnis') ||
        lower.includes('boxing') ||
        lower.includes('fenc') ||
        lower.includes('taekwondo')
    ) {
        return Swords;
    }

    return Dumbbell;
}

/**
 * A compact, horizontally-scrollable "highlights" strip of the busiest
 * sports (WP-08-09's mobile "sports strip") — the full breakdown stays
 * in `MedalsBySportCard` below it on the page; this is a shorter,
 * icon-forward preview, not a second copy of the complete data. A
 * trailing "More sports" tile jumps to that full table (a same-page
 * `#anchor`, not a page navigation — a plain `<a>`, not Inertia's
 * `Link`) when there are more sports than fit in the strip.
 */
export function SportsMedalStrip({
    rows,
    moreHref,
    visibleCount = 4,
}: {
    rows: SportRow[];
    moreHref: string;
    visibleCount?: number;
}) {
    if (rows.length === 0) {
        return null;
    }

    const visible = rows.slice(0, visibleCount);
    const hasMore = rows.length > visibleCount;

    return (
        <div className="flex gap-2 overflow-x-auto pb-1">
            {visible.map((row) => {
                const Icon = sportIcon(row.sport);

                return (
                    <div
                        key={row.sport}
                        className="flex w-20 shrink-0 flex-col items-center gap-1 rounded-lg border p-2 text-center"
                    >
                        <Icon
                            aria-hidden="true"
                            className="size-5 text-primary"
                        />
                        <span className="w-full truncate text-xs font-medium">
                            {row.sport}
                        </span>
                        <span className="text-[0.65rem] text-muted-foreground">
                            <span className="text-medal-gold-foreground">
                                {row.gold}
                            </span>{' '}
                            <span className="text-medal-silver-foreground">
                                {row.silver}
                            </span>{' '}
                            <span className="text-medal-bronze-foreground">
                                {row.bronze}
                            </span>
                        </span>
                    </div>
                );
            })}
            {hasMore && (
                <a
                    href={moreHref}
                    className="flex w-20 shrink-0 flex-col items-center justify-center gap-1 rounded-lg border p-2 text-center text-xs text-muted-foreground hover:bg-accent"
                >
                    <LayoutGrid aria-hidden="true" className="size-5" />
                    More sports
                </a>
            )}
        </div>
    );
}
