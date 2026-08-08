/**
 * Per-sport wording for all 28 mini portals — authored independently
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
    athletics: { game: 'event', period: 'heat/final', points: 'time/distance' },
    archery: { game: 'match', period: 'end', points: 'score' },
    arnis: { game: 'bout', period: 'round', points: 'points' },
    badminton: { game: 'match', period: 'set', points: 'points' },
    baseball: { game: 'game', period: 'inning', points: 'runs' },
    basketball: { game: 'game', period: 'quarter', points: 'points' },
    billiard: { game: 'match', period: 'rack', points: 'racks' },
    bocce: { game: 'match', period: 'end', points: 'points' },
    boxing: { game: 'bout', period: 'round', points: 'points' },
    chess: { game: 'match', period: 'round', points: 'points' },
    dancesports: { game: 'competition', period: 'round', points: 'score' },
    football: { game: 'match', period: 'half', points: 'goals' },
    futsal: { game: 'match', period: 'half', points: 'goals' },
    'goal-ball': { game: 'match', period: 'half', points: 'goals' },
    gymnastics: { game: 'competition', period: 'routine', points: 'score' },
    'pencak-silat': { game: 'bout', period: 'round', points: 'points' },
    swimming: { game: 'event', period: 'heat/final', points: 'time' },
    weightlifting: { game: 'competition', period: 'attempt', points: 'kg' },
    'sepak-takraw': { game: 'match', period: 'set', points: 'points' },
    softball: { game: 'game', period: 'inning', points: 'runs' },
    taekwondo: { game: 'bout', period: 'round', points: 'points' },
    'table-tennis': { game: 'match', period: 'set', points: 'points' },
    tennis: { game: 'match', period: 'set', points: 'points' },
    volleyball: { game: 'match', period: 'set', points: 'points' },
    wrestling: { game: 'match', period: 'period', points: 'points' },
    wushu: { game: 'bout', period: 'round', points: 'points' },
    'paragames-athletics': { game: 'event', period: 'heat/final', points: 'time/distance' },
    'paragames-swimming': { game: 'event', period: 'heat/final', points: 'time' },
};

/** All 28 permanent sport-portal slugs and display names — mirrors
 * `App\Enums\SportPortalSlug`, kept in sync by hand the same way the
 * legacy config already was. */
export const PORTAL_SPORTS: Array<{ slug: string; name: string }> = [
    { slug: 'athletics', name: 'Athletics' },
    { slug: 'archery', name: 'Archery' },
    { slug: 'arnis', name: 'Arnis' },
    { slug: 'badminton', name: 'Badminton' },
    { slug: 'baseball', name: 'Baseball' },
    { slug: 'basketball', name: 'Basketball' },
    { slug: 'billiard', name: 'Billiard' },
    { slug: 'bocce', name: 'Bocce' },
    { slug: 'boxing', name: 'Boxing' },
    { slug: 'chess', name: 'Chess' },
    { slug: 'dancesports', name: 'Dancesports' },
    { slug: 'football', name: 'Football' },
    { slug: 'futsal', name: 'Futsal' },
    { slug: 'goal-ball', name: 'Goal Ball' },
    { slug: 'gymnastics', name: 'Gymnastics' },
    { slug: 'pencak-silat', name: 'Pencak Silat' },
    { slug: 'swimming', name: 'Swimming' },
    { slug: 'weightlifting', name: 'Weightlifting' },
    { slug: 'sepak-takraw', name: 'Sepak Takraw' },
    { slug: 'softball', name: 'Softball' },
    { slug: 'taekwondo', name: 'Taekwondo' },
    { slug: 'table-tennis', name: 'Table Tennis' },
    { slug: 'tennis', name: 'Tennis' },
    { slug: 'volleyball', name: 'Volleyball' },
    { slug: 'wrestling', name: 'Wrestling' },
    { slug: 'wushu', name: 'Wushu' },
    { slug: 'paragames-athletics', name: 'Paragames - Athletics' },
    { slug: 'paragames-swimming', name: 'Paragames - Swimming' },
];

export function pluralize(word: string): string {
    return word.endsWith('s') ? word : `${word}s`;
}

export function capitalize(word: string): string {
    return word.charAt(0).toUpperCase() + word.slice(1);
}
