/**
 * Typed, defensive readers over a boxing `ScoringSession.sport_state`
 * blob. `rounds` is always real — every boxing session gets it from
 * `ScoringSessionController::round()` (10-point-must round scoring).
 * Everything else (judge scorecards, knockdowns, punch stats, warnings/
 * deductions, ring officials, weight class) is optional, additive
 * `sport_state` seeded as SYNTHETIC_DEMO data by `DdopaaLiveScoringSeeder`
 * for the reskinned scoreboard, docs/ui-ux/boxing.html. Every reader here
 * returns `undefined` for a missing/unexpectedly-shaped key rather than
 * throwing — callers render an honest empty state for whatever isn't
 * there.
 */

export type BoxingRound = {
    round: number;
    score_a: number;
    score_b: number;
};

export type BoxingJudgeScore = {
    name: string;
    red: number;
    blue: number;
};

export type BoxingJudgeCard = {
    judge: number;
    red: number;
    blue: number;
};

export type BoxingJudgeRound = {
    round: number;
    cards: BoxingJudgeCard[];
};

export type BoxingDecision = {
    manual: boolean;
    status: 'provisional' | 'final';
    method: 'points' | 'rsc' | 'rsc_i' | 'ko' | 'dsq' | 'wo' | 'abd' | 'nc';
    winner: 'a' | 'b' | null;
    type?: 'unanimous' | 'majority' | 'split' | 'draw' | null;
    note?: string | null;
    tally?: { a: number; b: number; even: number };
    rounds_scored?: number;
    total_rounds?: number;
};

export type BoxingCornerStats = {
    punches_landed: number;
    punches_thrown: number;
};

export type BoxingWarnings = {
    warnings_a: number;
    warnings_b: number;
    deductions: number;
};

export type BoxingOfficial = {
    name: string;
    role: string;
};

export type BoxingBoutMeta = {
    weight_class?: string;
    ring?: string;
    rounds_format?: string;
};

type SportState = Record<string, unknown> | null;

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

/** Always present for a real boxing session — falls back to an empty
 * list only if `sport_state` is entirely absent (a bout that hasn't had
 * a round scored yet). */
export function readRounds(state: SportState): BoxingRound[] {
    if (!isRecord(state) || !Array.isArray(state.rounds)) {
        return [];
    }

    return state.rounds.filter(
        (round): round is BoxingRound =>
            isRecord(round) && typeof round.round === 'number' && typeof round.score_a === 'number' && typeof round.score_b === 'number',
    );
}

export function totalRounds(state: SportState): number | undefined {
    if (!isRecord(state) || typeof state.total_rounds !== 'number') {
        return undefined;
    }

    return state.total_rounds;
}

export function knockdownCount(state: SportState, key: 'knockdowns_a' | 'knockdowns_b'): number | undefined {
    if (!isRecord(state) || typeof state[key] !== 'number') {
        return undefined;
    }

    return state[key];
}

export function readJudges(state: SportState): BoxingJudgeScore[] | undefined {
    if (!isRecord(state) || !Array.isArray(state.judges)) {
        return undefined;
    }

    const judges = state.judges.filter(
        (judge): judge is BoxingJudgeScore =>
            isRecord(judge) && typeof judge.name === 'string' && typeof judge.red === 'number' && typeof judge.blue === 'number',
    );

    return judges.length > 0 ? judges : undefined;
}

/** The real per-judge 10-point-must scorecards for each completed round
 * (`ScoringSessionController::recordBoxingJudgeRound()`). On the public
 * payload the cards are withheld during a live bout unless the meet opted
 * into disclosure — `judgeScoresHidden()` reports that. */
export function readJudgeRounds(state: SportState): BoxingJudgeRound[] {
    if (!isRecord(state) || !Array.isArray(state.judge_rounds)) {
        return [];
    }

    return state.judge_rounds
        .filter(
            (round): round is BoxingJudgeRound =>
                isRecord(round) &&
                typeof round.round === 'number' &&
                Array.isArray(round.cards) &&
                round.cards.every(
                    (card): card is BoxingJudgeCard =>
                        isRecord(card) &&
                        typeof card.judge === 'number' &&
                        typeof card.red === 'number' &&
                        typeof card.blue === 'number',
                ),
        )
        .sort((a, b) => a.round - b.round);
}

export function judgeScoresHidden(state: SportState): boolean {
    return isRecord(state) && state.judge_scores_hidden === true;
}

export function judgeCount(state: SportState): number | undefined {
    if (!isRecord(state) || typeof state.judge_count !== 'number') {
        return undefined;
    }

    return state.judge_count;
}

/** Per-side referee point deductions — bout-wide, applied to every judge's
 * card. Falls back to the legacy single `deductions` count (synthetic demo
 * data only) split to neither side. */
export function readDeductions(state: SportState): { a: number; b: number } | undefined {
    if (!isRecord(state)) {
        return undefined;
    }

    if (typeof state.deductions_a === 'number' || typeof state.deductions_b === 'number') {
        return {
            a: typeof state.deductions_a === 'number' ? state.deductions_a : 0,
            b: typeof state.deductions_b === 'number' ? state.deductions_b : 0,
        };
    }

    return undefined;
}

export function readDecision(state: SportState): BoxingDecision | undefined {
    if (!isRecord(state) || !isRecord(state.decision)) {
        return undefined;
    }

    const decision = state.decision;

    if (typeof decision.status !== 'string' || typeof decision.method !== 'string') {
        return undefined;
    }

    return decision as unknown as BoxingDecision;
}

/** Each judge's running card total from the recorded rounds, so the public
 * board can show the judges' tally exactly as the decision engine derives
 * it (deductions applied to the corner's total, never a raw round score). */
export function judgeCardTotals(
    state: SportState,
): { judge: number; red: number; blue: number }[] {
    const rounds = readJudgeRounds(state);
    const deductions = readDeductions(state) ?? { a: 0, b: 0 };
    const totals = new Map<number, { red: number; blue: number }>();

    for (const round of rounds) {
        for (const card of round.cards) {
            const total = totals.get(card.judge) ?? { red: 0, blue: 0 };
            total.red += card.red;
            total.blue += card.blue;
            totals.set(card.judge, total);
        }
    }

    return [...totals.entries()]
        .sort(([a], [b]) => a - b)
        .map(([judge, total]) => ({
            judge,
            red: total.red - deductions.a,
            blue: total.blue - deductions.b,
        }));
}

function readCornerStats(value: unknown): BoxingCornerStats | undefined {
    if (!isRecord(value) || typeof value.punches_landed !== 'number' || typeof value.punches_thrown !== 'number') {
        return undefined;
    }

    return value as unknown as BoxingCornerStats;
}

export function readBoutStats(state: SportState): { a: BoxingCornerStats; b: BoxingCornerStats } | undefined {
    if (!isRecord(state) || !isRecord(state.bout_stats)) {
        return undefined;
    }

    const a = readCornerStats(state.bout_stats.a);
    const b = readCornerStats(state.bout_stats.b);

    return a && b ? { a, b } : undefined;
}

export function accuracy(landed: number, thrown: number): string {
    return thrown > 0 ? `${Math.round((landed / thrown) * 100)}%` : '0%';
}

export function readWarnings(state: SportState): BoxingWarnings | undefined {
    if (!isRecord(state)) {
        return undefined;
    }

    const keys = ['warnings_a', 'warnings_b', 'deductions'] as const;

    if (!keys.every((key) => typeof state[key] === 'number')) {
        return undefined;
    }

    return {
        warnings_a: state.warnings_a as number,
        warnings_b: state.warnings_b as number,
        deductions: state.deductions as number,
    };
}

export function readOfficials(state: SportState): BoxingOfficial[] | undefined {
    if (!isRecord(state) || !Array.isArray(state.officials)) {
        return undefined;
    }

    const officials = state.officials.filter(
        (official): official is BoxingOfficial => isRecord(official) && typeof official.name === 'string' && typeof official.role === 'string',
    );

    return officials.length > 0 ? officials : undefined;
}

export function readBoutMeta(state: SportState): BoxingBoutMeta | undefined {
    if (!isRecord(state)) {
        return undefined;
    }

    const meta: BoxingBoutMeta = {
        weight_class: typeof state.weight_class === 'string' ? state.weight_class : undefined,
        ring: typeof state.ring === 'string' ? state.ring : undefined,
        rounds_format: typeof state.rounds_format === 'string' ? state.rounds_format : undefined,
    };

    return meta.weight_class || meta.ring || meta.rounds_format ? meta : undefined;
}
