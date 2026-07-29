/**
 * The 12 lightweight sport-portal routes (Phase 12) — presentational
 * config only (labels/terminology), mirroring `App\Enums\SportPortalSlug`
 * on the backend (the two lists are kept in sync by hand; the backend
 * enum is the source of truth for which `Sport` row a slug resolves
 * to). `supportsStandings`/`supportsLeadingScorers`/`supportsBracket`
 * are honestly `false` for every sport today — no team win/loss
 * aggregation, per-athlete point attribution, or bracket-tree data
 * exists anywhere in this schema (`docs/phases/phase-12-lightweight-
 * sport-mini-portals/DATA-CONTRACT-MAP.md` §D/E/F). Never flip these
 * to `true` without the backend actually computing that data first.
 */
export type SportScoringType =
    'team-score' | 'sets' | 'combat' | 'race' | 'time-distance' | 'rank-only';

export type SportPortalConfig = {
    slug: string;
    name: string;
    scoringType: SportScoringType;
    supportsStandings: boolean;
    supportsLeadingScorers: boolean;
    supportsBracket: boolean;
    terminology: {
        game: string;
        period: string;
        points: string;
    };
};

export const sportPortals: Record<string, SportPortalConfig> = {
    basketball: {
        slug: 'basketball',
        name: 'Basketball',
        scoringType: 'team-score',
        supportsStandings: false,
        supportsLeadingScorers: false,
        supportsBracket: false,
        terminology: { game: 'game', period: 'quarter', points: 'points' },
    },
    volleyball: {
        slug: 'volleyball',
        name: 'Volleyball',
        scoringType: 'sets',
        supportsStandings: false,
        supportsLeadingScorers: false,
        supportsBracket: false,
        terminology: { game: 'match', period: 'set', points: 'points' },
    },
    baseball: {
        slug: 'baseball',
        name: 'Baseball',
        scoringType: 'team-score',
        supportsStandings: false,
        supportsLeadingScorers: false,
        supportsBracket: false,
        terminology: { game: 'game', period: 'inning', points: 'runs' },
    },
    softball: {
        slug: 'softball',
        name: 'Softball',
        scoringType: 'team-score',
        supportsStandings: false,
        supportsLeadingScorers: false,
        supportsBracket: false,
        terminology: { game: 'game', period: 'inning', points: 'runs' },
    },
    football: {
        slug: 'football',
        name: 'Football',
        scoringType: 'team-score',
        supportsStandings: false,
        supportsLeadingScorers: false,
        supportsBracket: false,
        terminology: { game: 'match', period: 'half', points: 'goals' },
    },
    'sepak-takraw': {
        slug: 'sepak-takraw',
        name: 'Sepak Takraw',
        scoringType: 'sets',
        supportsStandings: false,
        supportsLeadingScorers: false,
        supportsBracket: false,
        terminology: { game: 'match', period: 'set', points: 'points' },
    },
    badminton: {
        slug: 'badminton',
        name: 'Badminton',
        scoringType: 'sets',
        supportsStandings: false,
        supportsLeadingScorers: false,
        supportsBracket: false,
        terminology: { game: 'match', period: 'set', points: 'points' },
    },
    'table-tennis': {
        slug: 'table-tennis',
        name: 'Table Tennis',
        scoringType: 'sets',
        supportsStandings: false,
        supportsLeadingScorers: false,
        supportsBracket: false,
        terminology: { game: 'match', period: 'set', points: 'points' },
    },
    chess: {
        slug: 'chess',
        name: 'Chess',
        scoringType: 'rank-only',
        supportsStandings: false,
        supportsLeadingScorers: false,
        supportsBracket: false,
        terminology: { game: 'match', period: 'round', points: 'points' },
    },
    boxing: {
        slug: 'boxing',
        name: 'Boxing',
        scoringType: 'combat',
        supportsStandings: false,
        supportsLeadingScorers: false,
        supportsBracket: false,
        terminology: { game: 'bout', period: 'round', points: 'points' },
    },
    athletics: {
        slug: 'athletics',
        name: 'Athletics',
        scoringType: 'race',
        supportsStandings: false,
        supportsLeadingScorers: false,
        supportsBracket: false,
        terminology: {
            game: 'event',
            period: 'heat/final',
            points: 'time/distance',
        },
    },
    swimming: {
        slug: 'swimming',
        name: 'Swimming',
        scoringType: 'time-distance',
        supportsStandings: false,
        supportsLeadingScorers: false,
        supportsBracket: false,
        terminology: { game: 'event', period: 'heat/final', points: 'time' },
    },
};
