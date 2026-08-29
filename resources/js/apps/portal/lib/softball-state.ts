/**
 * Typed, defensive readers over a softball/baseball `ScoringSession.
 * sport_state` blob. `inning`/`half`/`outs`/`balls`/`strikes`/`innings`
 * are always real — every softball session gets them from
 * `ScoringSessionController::count()`/`inningRun()`. Everything else
 * (runners on base, hits/errors, team stats, box score, pitchers) is
 * optional, additive `sport_state` seeded as SYNTHETIC_DEMO data by
 * `DdopaaLiveScoringSeeder` for the reskinned scoreboard, docs/ui-ux/
 * softball-live.html. Every reader here returns `undefined` for a
 * missing/unexpectedly-shaped key rather than throwing — callers render
 * an honest empty state for whatever isn't there.
 */

export type SoftballInning = {
    inning: number;
    runs_a: number;
    runs_b: number;
};

export type SoftballCount = {
    inning: number;
    half: 'top' | 'bottom';
    outs: number;
    balls: number;
    strikes: number;
    innings: SoftballInning[];
};

export type SoftballRunners = {
    first: boolean;
    second: boolean;
    third: boolean;
};

export type SoftballTeamStats = {
    walks: number;
    strikeouts: number;
    stolen_bases: number;
    batting_avg: string;
    slugging_pct: string;
};

export type SoftballPerformer = {
    number: string;
    name: string;
    line: string;
};

export type SoftballPitcher = {
    number: string;
    name: string;
    throws: string;
    line: string;
    pitches: number;
};

type SportState = Record<string, unknown> | null;

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

/** Always present for a real softball/baseball session — falls back to
 * a fresh game's zeroed state only if `sport_state` is somehow entirely
 * absent (a session that's never had a single count/run event yet). */
export function readCount(state: SportState): SoftballCount {
    const fallback: SoftballCount = {
        inning: 1,
        half: 'top',
        outs: 0,
        balls: 0,
        strikes: 0,
        innings: [],
    };

    if (!isRecord(state)) {
        return fallback;
    }

    const innings = Array.isArray(state.innings)
        ? state.innings.filter(
              (row): row is SoftballInning =>
                  isRecord(row) &&
                  typeof row.inning === 'number' &&
                  typeof row.runs_a === 'number' &&
                  typeof row.runs_b === 'number',
          )
        : [];

    return {
        inning:
            typeof state.inning === 'number' ? state.inning : fallback.inning,
        half: state.half === 'bottom' ? 'bottom' : 'top',
        outs: typeof state.outs === 'number' ? state.outs : 0,
        balls: typeof state.balls === 'number' ? state.balls : 0,
        strikes: typeof state.strikes === 'number' ? state.strikes : 0,
        innings,
    };
}

export function readRunners(state: SportState): SoftballRunners | undefined {
    if (!isRecord(state) || !isRecord(state.runners)) {
        return undefined;
    }

    const { first, second, third } = state.runners;

    if (
        typeof first !== 'boolean' ||
        typeof second !== 'boolean' ||
        typeof third !== 'boolean'
    ) {
        return undefined;
    }

    return { first, second, third };
}

export function readHitsAndErrors(
    state: SportState,
):
    | { hits_a: number; hits_b: number; errors_a: number; errors_b: number }
    | undefined {
    if (!isRecord(state)) {
        return undefined;
    }

    const keys = ['hits_a', 'hits_b', 'errors_a', 'errors_b'] as const;

    if (!keys.every((key) => typeof state[key] === 'number')) {
        return undefined;
    }

    return {
        hits_a: state.hits_a as number,
        hits_b: state.hits_b as number,
        errors_a: state.errors_a as number,
        errors_b: state.errors_b as number,
    };
}

function readTeamStatsSide(value: unknown): SoftballTeamStats | undefined {
    if (!isRecord(value)) {
        return undefined;
    }

    if (
        typeof value.walks !== 'number' ||
        typeof value.strikeouts !== 'number' ||
        typeof value.stolen_bases !== 'number' ||
        typeof value.batting_avg !== 'string' ||
        typeof value.slugging_pct !== 'string'
    ) {
        return undefined;
    }

    return value as unknown as SoftballTeamStats;
}

export function readTeamStats(
    state: SportState,
): { a: SoftballTeamStats; b: SoftballTeamStats } | undefined {
    if (!isRecord(state) || !isRecord(state.team_stats)) {
        return undefined;
    }

    const a = readTeamStatsSide(state.team_stats.a);
    const b = readTeamStatsSide(state.team_stats.b);

    return a && b ? { a, b } : undefined;
}

function readPerformerList(value: unknown): SoftballPerformer[] | undefined {
    if (!Array.isArray(value)) {
        return undefined;
    }

    const performers = value.filter(
        (performer): performer is SoftballPerformer =>
            isRecord(performer) &&
            typeof performer.number === 'string' &&
            typeof performer.name === 'string' &&
            typeof performer.line === 'string',
    );

    return performers.length > 0 ? performers : undefined;
}

export function readBoxScore(
    state: SportState,
): { a: SoftballPerformer[]; b: SoftballPerformer[] } | undefined {
    if (!isRecord(state) || !isRecord(state.box_score)) {
        return undefined;
    }

    const a = readPerformerList(state.box_score.a);
    const b = readPerformerList(state.box_score.b);

    return a && b ? { a, b } : undefined;
}

function readPitcher(value: unknown): SoftballPitcher | undefined {
    if (
        !isRecord(value) ||
        typeof value.number !== 'string' ||
        typeof value.name !== 'string' ||
        typeof value.throws !== 'string' ||
        typeof value.line !== 'string' ||
        typeof value.pitches !== 'number'
    ) {
        return undefined;
    }

    return value as unknown as SoftballPitcher;
}

export function readPitchers(
    state: SportState,
): { a: SoftballPitcher; b: SoftballPitcher } | undefined {
    if (!isRecord(state) || !isRecord(state.pitchers)) {
        return undefined;
    }

    const a = readPitcher(state.pitchers.a);
    const b = readPitcher(state.pitchers.b);

    return a && b ? { a, b } : undefined;
}
