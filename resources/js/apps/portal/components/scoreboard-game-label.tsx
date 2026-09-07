const MODE_LABELS: Record<string, string> = {
    test: 'Test',
    finals: 'Finals Game',
    championship: 'Championship Game',
};

/** The amber game-mode banner above a public scoreboard. When both team
 * names are known it becomes a broadcast-style title:
 * `Championship Game (TEAM "Nabunturan" VS TEAM "New Bataan")`. */
export function ScoreboardGameLabel({
    mode,
    teamA,
    teamB,
}: {
    mode?: string | null;
    teamA?: string | null;
    teamB?: string | null;
}) {
    if (!mode) {
        return null;
    }

    const base = MODE_LABELS[mode] ?? mode;
    const title =
        teamA && teamB
            ? `${base} (TEAM "${teamA}" VS TEAM "${teamB}")`
            : base;

    return (
        <div className="rounded-xl border border-amber-500/50 bg-amber-500/15 p-4 text-center text-xl font-bold">
            {title}
        </div>
    );
}
