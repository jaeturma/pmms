import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

/** "District" -> "Districts", "Municipality" -> "Municipalities". */
export function pluralizeAreaLabel(areaLabel: string): string {
    return areaLabel.endsWith('y')
        ? `${areaLabel.slice(0, -1)}ies`
        : `${areaLabel}s`;
}

/** A `StatCard` description for a "recent activity" delta — omitted
 * (not shown as "+0") when there's nothing recent to report. */
export function recentDescription(count: number): string | undefined {
    return count > 0 ? `+${count} in the last 24 hours` : undefined;
}

/** Simple English pluralization for the sport-portal terminology labels
 * (Phase 12 — "game"/"match"/"bout"/"event", the only real values in
 * `sport-portals.ts`'s config) — handles the sibilant-ending case
 * ("match" -> "matches"), not a full grammatical implementation. */
export function pluralize(word: string): string {
    return /[sxz]$|[cs]h$/.test(word) ? `${word}es` : `${word}s`;
}

/** "game" -> "Games", for a section heading built from a lowercase
 * terminology label. */
export function capitalize(word: string): string {
    return word.charAt(0).toUpperCase() + word.slice(1);
}
