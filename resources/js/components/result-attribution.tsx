import { useEffect, useState } from 'react';

export type Attribution = {
    athlete_id: number | null;
    athlete_ids: number[];
    team_entry_id?: number | null;
    coaches: { user_id: number; role: string; name?: string }[];
    players?: string[];
};
export const emptyAttribution = (): Attribution => ({
    athlete_id: null,
    athlete_ids: [],
    team_entry_id: null,
    coaches: [],
});
type Option = { id: number; label: string; athlete_ids?: number[] };

export function AttributionFields({
    eventId,
    delegationId,
    team,
    value,
    onChange,
}: {
    eventId: number;
    delegationId: number;
    team: boolean;
    value: Attribution;
    onChange: (value: Attribution) => void;
}) {
    const [options, setOptions] = useState<{
        athletes: Option[];
        teams: Option[];
        coaches: Option[];
    }>({ athletes: [], teams: [], coaches: [] });
    const [search, setSearch] = useState('');
    const [error, setError] = useState('');
    useEffect(() => {
        const controller = new AbortController();

        if (eventId && delegationId) {
            fetch(
                `/results/attribution-options?event_id=${eventId}&delegation_id=${delegationId}`,
                {
                    signal: controller.signal,
                    headers: { Accept: 'application/json' },
                },
            )
                .then(async (response) => {
                    if (!response.ok) {
                        throw new Error(
                            'Reporting choices could not be loaded.',
                        );
                    }

                    return response.json();
                })
                .then((data) => {
                    setOptions(data);
                    setError('');
                })
                .catch((e) => {
                    if (e.name !== 'AbortError') {
                        setError(e.message);
                    }
                });
        }

        return () => controller.abort();
    }, [eventId, delegationId]);
    const athletes = options.athletes.filter((a) =>
        a.label.toLowerCase().includes(search.toLowerCase()),
    );

    if (!eventId || !delegationId) {
        return null;
    }

    return (
        <details className="space-y-3 rounded border p-3" open={!team}>
            <summary className="cursor-pointer text-sm font-medium">
                {team
                    ? `View / Manage Roster (${value.athlete_ids.length} athletes linked)`
                    : 'Athlete (optional)'}
            </summary>
            <p className="text-xs text-muted-foreground">
                {team
                    ? `${value.coaches.length} coaches linked. Roster may remain incomplete.`
                    : value.athlete_id
                      ? 'Athlete linked: Complete'
                      : 'Athlete linked: Missing'}{' '}
                Reporting only; medal count is unchanged.
            </p>
            {error && <p role="alert">{error}</p>}
            {team && (
                <label className="block text-sm">
                    Import Team Entry
                    <select
                        className="block w-full rounded border p-2"
                        value={value.team_entry_id ?? ''}
                        onChange={(e) => {
                            const selected = options.teams.find(
                                (t) => t.id === Number(e.target.value),
                            );
                            onChange({
                                ...value,
                                team_entry_id: selected?.id ?? null,
                                athlete_ids:
                                    selected?.athlete_ids ?? value.athlete_ids,
                            });
                        }}
                    >
                        <option value="">No Team Entry (optional)</option>
                        {options.teams.map((t) => (
                            <option key={t.id} value={t.id}>
                                {t.label}
                            </option>
                        ))}
                    </select>
                </label>
            )}
            <input
                aria-label="Search athlete by name"
                className="w-full rounded border p-2"
                placeholder="Search athlete by name"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
            />
            {team ? (
                <div className="max-h-48 overflow-auto">
                    {athletes.map((a) => (
                        <label key={a.id} className="flex gap-2 p-1">
                            <input
                                type="checkbox"
                                checked={value.athlete_ids.includes(a.id)}
                                onChange={(e) =>
                                    onChange({
                                        ...value,
                                        athlete_ids: e.target.checked
                                            ? [...value.athlete_ids, a.id]
                                            : value.athlete_ids.filter(
                                                  (id) => id !== a.id,
                                              ),
                                    })
                                }
                            />
                            {a.label}
                        </label>
                    ))}
                </div>
            ) : (
                <select
                    aria-label="Athlete (optional)"
                    className="w-full rounded border p-2"
                    value={value.athlete_id ?? ''}
                    onChange={(e) =>
                        onChange({
                            ...value,
                            athlete_id: Number(e.target.value) || null,
                        })
                    }
                >
                    <option value="">Select Athlete - optional</option>
                    {options.athletes
                        .filter(
                            (a) =>
                                athletes.includes(a) ||
                                a.id === value.athlete_id,
                        )
                        .map((a) => (
                            <option key={a.id} value={a.id}>
                                {a.label}
                            </option>
                        ))}
                </select>
            )}
            {team && (
                <div>
                    <button
                        type="button"
                        className="text-sm underline"
                        onClick={() =>
                            onChange({
                                ...value,
                                athlete_ids: [],
                                team_entry_id: null,
                            })
                        }
                    >
                        Clear roster links
                    </button>
                    <p className="text-sm font-medium">
                        Team Coaches (optional)
                    </p>
                    <button
                        type="button"
                        className="text-sm underline"
                        onClick={() => onChange({ ...value, coaches: [] })}
                    >
                        Clear coach links
                    </button>
                    {options.coaches.map((c) => (
                        <label
                            className="flex items-center justify-between gap-2 p-1"
                            key={c.id}
                        >
                            {c.label}
                            <select
                                aria-label={`${c.label} coach role`}
                                value={
                                    value.coaches.find(
                                        (v) => v.user_id === c.id,
                                    )?.role ?? ''
                                }
                                onChange={(e) =>
                                    onChange({
                                        ...value,
                                        coaches: [
                                            ...value.coaches.filter(
                                                (v) => v.user_id !== c.id,
                                            ),
                                            ...(e.target.value
                                                ? [
                                                      {
                                                          user_id: c.id,
                                                          role: e.target.value,
                                                      },
                                                  ]
                                                : []),
                                        ],
                                    })
                                }
                            >
                                <option value="">Not linked</option>
                                <option value="primary">
                                    Head / Primary Coach
                                </option>
                                <option value="assistant">
                                    Assistant Coach
                                </option>
                            </select>
                        </label>
                    ))}
                </div>
            )}
        </details>
    );
}
