export function ScoreboardGameLabel({ mode }: { mode?: string | null }) {
    if (!mode) {
        return null;
    }

    const labels: Record<string, string> = {
        test: 'Test',
        finals: 'Finals Game',
        championship: 'Championship Game',
    };

    return (
        <div className="rounded-xl border border-amber-500/50 bg-amber-500/15 p-4 text-center text-xl font-bold">
            {labels[mode] ?? mode}
        </div>
    );
}
