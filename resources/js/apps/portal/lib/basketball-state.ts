/**
 * Typed, defensive readers over a basketball `ScoringSession.sport_state`
 * blob. Every field past `fouls_a`/`fouls_b` (quarters, timeouts, shot
 * clock, team stats, box score) is optional, additive `sport_state` —
 * seeded as SYNTHETIC_DEMO data by `DdopaaLiveScoringSeeder` for the
 * reskinned scoreboard, docs/ui-ux/basketball.html. A real basketball
 * session that only ever calls `ScoringSessionController::foul()` still
 * has just `fouls_a`/`fouls_b`, so every reader here returns `undefined`
 * rather than throwing when a key is missing or shaped unexpectedly —
 * callers render an honest empty state for whatever isn't there.
 */

export type BasketballQuarter = {
    label: string;
    a: number;
    b: number;
};

export type BasketballTeamStats = {
    fg_made: number;
    fg_att: number;
    three_made: number;
    three_att: number;
    ft_made: number;
    ft_att: number;
    rebounds: number;
    assists: number;
    turnovers: number;
    steals: number;
    blocks: number;
};

export type BasketballPerformer = {
    number: string;
    name: string;
    pts: number;
    reb: number;
    ast: number;
};

type SportState = Record<string, unknown> | null;

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function readNumber(record: Record<string, unknown>, key: string): number {
    const value = record[key];

    return typeof value === 'number' ? value : 0;
}

export function foulCount(
    state: SportState,
    key: 'fouls_a' | 'fouls_b',
): number {
    return isRecord(state) ? readNumber(state, key) : 0;
}

export function timeoutCount(
    state: SportState,
    key: 'timeouts_a' | 'timeouts_b',
): number | undefined {
    if (!isRecord(state) || typeof state[key] !== 'number') {
        return undefined;
    }

    return state[key];
}

export function gameClock(
    state: SportState,
): { seconds: number; updatedAt: string | null } | undefined {
    if (!isRecord(state) || typeof state.game_clock_seconds !== 'number') {
        return undefined;
    }

    return {
        seconds: Math.max(0, state.game_clock_seconds),
        updatedAt:
            typeof state.game_clock_updated_at === 'string'
                ? state.game_clock_updated_at
                : null,
    };
}

export function possessionSide(state: SportState): 'a' | 'b' | null {
    if (
        !isRecord(state) ||
        (state.possession !== 'a' && state.possession !== 'b')
    ) {
        return null;
    }

    return state.possession;
}

export function readQuarters(
    state: SportState,
): BasketballQuarter[] | undefined {
    if (!isRecord(state) || !Array.isArray(state.quarters)) {
        return undefined;
    }

    const quarters = state.quarters.filter(
        (quarter): quarter is BasketballQuarter =>
            isRecord(quarter) &&
            typeof quarter.label === 'string' &&
            typeof quarter.a === 'number' &&
            typeof quarter.b === 'number',
    );

    return quarters.length > 0 ? quarters : undefined;
}

export function quarterCount(state: SportState): 2 | 4 {
    if (isRecord(state) && (state.quarters === 2 || state.quarters === 4)) {
        return state.quarters;
    }

    // Legacy showcase sessions stored completed quarter rows under this
    // key. Those basketball games use the standard four-quarter format.
    return 4;
}

function readTeamStatsSide(value: unknown): BasketballTeamStats | undefined {
    if (!isRecord(value)) {
        return undefined;
    }

    const keys: Array<keyof BasketballTeamStats> = [
        'fg_made',
        'fg_att',
        'three_made',
        'three_att',
        'ft_made',
        'ft_att',
        'rebounds',
        'assists',
        'turnovers',
        'steals',
        'blocks',
    ];

    if (!keys.every((key) => typeof value[key] === 'number')) {
        return undefined;
    }

    return value as unknown as BasketballTeamStats;
}

export function readTeamStats(
    state: SportState,
): { a: BasketballTeamStats; b: BasketballTeamStats } | undefined {
    if (!isRecord(state) || !isRecord(state.team_stats)) {
        return undefined;
    }

    const a = readTeamStatsSide(state.team_stats.a);
    const b = readTeamStatsSide(state.team_stats.b);

    return a && b ? { a, b } : undefined;
}

function readPerformerList(value: unknown): BasketballPerformer[] | undefined {
    if (!Array.isArray(value)) {
        return undefined;
    }

    const performers = value.filter(
        (performer): performer is BasketballPerformer =>
            isRecord(performer) &&
            typeof performer.number === 'string' &&
            typeof performer.name === 'string' &&
            typeof performer.pts === 'number' &&
            typeof performer.reb === 'number' &&
            typeof performer.ast === 'number',
    );

    return performers.length > 0 ? performers : undefined;
}

export function readBoxScore(
    state: SportState,
): { a: BasketballPerformer[]; b: BasketballPerformer[] } | undefined {
    if (!isRecord(state) || !isRecord(state.box_score)) {
        return undefined;
    }

    const a = readPerformerList(state.box_score.a);
    const b = readPerformerList(state.box_score.b);

    return a && b ? { a, b } : undefined;
}

export function shootingLine(made: number, att: number): string {
    const pct = att > 0 ? Math.round((made / att) * 100) : 0;

    return `${made}/${att} (${pct}%)`;
}
