import { Head, Link } from '@inertiajs/react';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalResultPlacements } from '@/apps/portal/components/result-placements';
import { PortalSectionHeader } from '@/apps/portal/components/section-header';
import type {
    PortalLatestResult,
    PortalResultPlacement,
} from '@/apps/portal/types';

type Props = {
    event: {
        id: number;
        name: string;
        sport: string;
        category: string;
        sport_url: string;
    };
    meet: { id: number; name: string } | null;
    standings: Array<
        PortalResultPlacement & { result_id: number; status_label: string }
    >;
    results: Array<PortalLatestResult & { id: number }>;
    versusResults: Array<PortalLatestResult & { id: number }>;
    teamStandings: { columns: Record<string, string>; rows: Array<{ rank: number; delegation_id: number; team: string; played: number; wins: number; losses: number }>; note: string };
};

export default function PortalSportEvent({
    event,
    meet,
    standings,
    results,
    versusResults,
    teamStandings,
}: Props) {
    return (
        <>
            <Head title={`${event.name} — ${event.sport}`} />
            <div className="flex flex-col gap-6">
                <Link
                    href={event.sport_url}
                    className="text-sm font-semibold underline"
                >
                    Back to {event.sport}
                </Link>
                <PortalHero
                    title={event.name}
                    description={`${event.sport} · ${event.category}`}
                    meta={<span>{meet?.name ?? 'No active meet'}</span>}
                />
                <section className="space-y-3">
                    {teamStandings.rows.length > 0 && <div className="space-y-3"><PortalSectionHeader title="Event Team Standings" /><p className="text-sm">{teamStandings.note}</p><div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead><tr><th className="p-3">Rank</th><th className="p-3">Team / Delegation</th>{Object.entries(teamStandings.columns).map(([key, label]) => <th className="p-3" key={key}>{label}</th>)}</tr></thead><tbody>{teamStandings.rows.map((row) => <tr key={row.delegation_id}><td className="p-3">{row.rank}</td><td className="p-3">{row.team}</td>{Object.keys(teamStandings.columns).map((key) => <td className="p-3" key={key}>{row[key as 'played' | 'wins' | 'losses']}</td>)}</tr>)}</tbody></table></div></div>}
                    {versusResults.length > 0 && <div className="space-y-4"><PortalSectionHeader title="Accepted Versus Results" />{versusResults.map((result) => <article key={result.id} className="rounded border p-4"><PortalResultPlacements result={result} /></article>)}</div>}
                    <PortalSectionHeader title="Team Standing" />
                    <p className="text-sm text-[var(--portal-muted-foreground)]">
                        Recorded places and scores from accepted non-medal
                        results.
                    </p>
                    {standings.length ? (
                        <div className="overflow-x-auto rounded-lg border border-[var(--portal-border)]">
                            <table className="w-full text-left text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="p-3">Place</th>
                                        <th className="p-3">
                                            Delegation / Team
                                        </th>
                                        <th className="p-3">
                                            Score / Points / Time
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {standings.map((row, i) => (
                                        <tr
                                            key={row.id ?? i}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">
                                                {row.rank}
                                                {row.is_tie ? ' (tie)' : ''}
                                            </td>
                                            <td className="p-3">
                                                {row.delegation}
                                            </td>
                                            <td className="p-3">
                                                {row.mark || '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p>No accepted standings yet.</p>
                    )}
                </section>
                <section className="space-y-4">
                    <PortalSectionHeader title="Declared Winners and Results" />
                    {!results.length && <p>No accepted medal results yet.</p>}
                    {results.map((result) => (
                        <article
                            id={`result-${result.id}`}
                            key={result.id}
                            className="scroll-mt-20 rounded-lg border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4"
                        >
                            <p className="mb-3 font-semibold">
                                Declared winner
                                {result.placements.filter((p) => p.rank === 1)
                                    .length > 1
                                    ? 's'
                                    : ''}
                                :{' '}
                                {result.placements
                                    .filter((p) => p.rank === 1)
                                    .map((p) => p.athlete)
                                    .join(', ') || 'Not declared'}
                            </p>
                            <PortalResultPlacements result={result} />
                        </article>
                    ))}
                </section>
            </div>
        </>
    );
}
