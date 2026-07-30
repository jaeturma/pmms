/**
 * Per-sport wording for the 12 mini portals — authored independently
 * for the portal design system rather than imported from
 * `@/config/sport-portals`, so `apps/portal` has no dependency outside
 * its own tree. Mirrors the real `App\Enums\SportPortalSlug` set on
 * the backend; kept in sync by hand the same way the legacy config
 * already was.
 */
export type PortalSportTerminology = {
    game: string;
    period: string;
    points: string;
};

export const PORTAL_SPORT_TERMINOLOGY: Record<string, PortalSportTerminology> = {
    basketball: { game: 'game', period: 'quarter', points: 'points' },
    volleyball: { game: 'match', period: 'set', points: 'points' },
    baseball: { game: 'game', period: 'inning', points: 'runs' },
    softball: { game: 'game', period: 'inning', points: 'runs' },
    football: { game: 'match', period: 'half', points: 'goals' },
    'sepak-takraw': { game: 'match', period: 'set', points: 'points' },
    badminton: { game: 'match', period: 'set', points: 'points' },
    'table-tennis': { game: 'match', period: 'set', points: 'points' },
    chess: { game: 'match', period: 'round', points: 'points' },
    boxing: { game: 'bout', period: 'round', points: 'points' },
    athletics: { game: 'event', period: 'heat/final', points: 'time/distance' },
    swimming: { game: 'event', period: 'heat/final', points: 'time' },
};

/** The 12 permanent sport-portal slugs and display names — mirrors
 * `App\Enums\SportPortalSlug`, kept in sync by hand the same way the
 * legacy config already was. */
export const PORTAL_SPORTS: Array<{ slug: string; name: string }> = [
    { slug: 'basketball', name: 'Basketball' },
    { slug: 'volleyball', name: 'Volleyball' },
    { slug: 'baseball', name: 'Baseball' },
    { slug: 'softball', name: 'Softball' },
    { slug: 'football', name: 'Football' },
    { slug: 'sepak-takraw', name: 'Sepak Takraw' },
    { slug: 'badminton', name: 'Badminton' },
    { slug: 'table-tennis', name: 'Table Tennis' },
    { slug: 'chess', name: 'Chess' },
    { slug: 'boxing', name: 'Boxing' },
    { slug: 'athletics', name: 'Athletics' },
    { slug: 'swimming', name: 'Swimming' },
];

export function pluralize(word: string): string {
    return word.endsWith('s') ? word : `${word}s`;
}

export function capitalize(word: string): string {
    return word.charAt(0).toUpperCase() + word.slice(1);
}
