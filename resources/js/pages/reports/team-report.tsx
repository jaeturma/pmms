import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

type Option = { id: number; name: string };
type Award = {
    id: number;
    medal: string;
    tally_count: number;
    athletes: string[];
    mark: string | null;
};
type Sport = {
    id: number | string;
    name: string | null;
    events: {
        id: number;
        name: string | null;
        division: string | null;
        gender: string | null;
        awards: Award[];
    }[];
    signatories: {
        label: string;
        name: string | null;
        position: string | null;
    }[];
};
type Props = {
    meet: Option & {
        school_year: string | null;
        venue: string | null;
        starts_at: string | null;
        ends_at: string | null;
    };
    meetOptions: Option[];
    teamOptions: Option[];
    team: Option | null;
    summary: { gold: number; silver: number; bronze: number; total: number };
    sports: Sport[];
    generatedAt: string;
};

export default function TeamReport({
    meet,
    meetOptions,
    teamOptions,
    team,
    summary,
    sports,
    generatedAt,
}: Props) {
    const select = (meetId: number, teamId?: string) =>
        router.get('/reports/team', {
            meet_id: meetId,
            ...(teamId ? { delegation_id: teamId } : {}),
        });
    return (
        <>
            <Head title="Team Report" />
            <div className="medal-awards-report flex flex-col gap-6 p-6 print:p-0">
                <div className="flex flex-wrap items-end gap-4 print:hidden">
                    <label className="flex flex-col gap-1 text-sm">
                        Meet
                        <select
                            aria-label="Meet"
                            className="rounded border p-2"
                            value={meet.id}
                            onChange={(e) => select(Number(e.target.value))}
                        >
                            {meetOptions.map((option) => (
                                <option key={option.id} value={option.id}>
                                    {option.name}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="flex flex-col gap-1 text-sm">
                        Team / Delegation
                        <select
                            aria-label="Team / Delegation"
                            className="rounded border p-2"
                            value={team?.id ?? ''}
                            onChange={(e) => select(meet.id, e.target.value)}
                        >
                            <option value="">Select a Team / Delegation</option>
                            {teamOptions.map((option) => (
                                <option key={option.id} value={option.id}>
                                    {option.name}
                                </option>
                            ))}
                        </select>
                    </label>
                    <Button disabled={!team} onClick={() => window.print()}>
                        Print / Save PDF
                    </Button>
                </div>
                <header className="text-center">
                    <p className="font-semibold">{meet.name}</p>
                    {meet.school_year && <p>School Year {meet.school_year}</p>}
                    {meet.venue && <p>{meet.venue}</p>}
                    {(meet.starts_at || meet.ends_at) && (
                        <p>
                            {[meet.starts_at, meet.ends_at]
                                .filter(Boolean)
                                .join(' ? ')}
                        </p>
                    )}
                    <h1 className="mt-4 text-xl font-bold">TEAM REPORT</h1>
                    {team && <p className="font-semibold">Team: {team.name}</p>}
                    <p className="mt-2 text-xs">
                        Accepted / published medal awards ? Generated{' '}
                        {generatedAt}
                    </p>
                </header>
                {!team ? (
                    <p className="py-8 text-center">
                        Select a Team / Delegation to preview its medal report.
                    </p>
                ) : (
                    <>
                        <table
                            className="w-full border-collapse text-center text-sm"
                            aria-label="Medal summary"
                        >
                            <thead>
                                <tr>
                                    {['Gold', 'Silver', 'Bronze', 'Total'].map(
                                        (label) => (
                                            <th
                                                key={label}
                                                className="border p-2"
                                            >
                                                {label}
                                            </th>
                                        ),
                                    )}
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    {[
                                        summary.gold,
                                        summary.silver,
                                        summary.bronze,
                                        summary.total,
                                    ].map((count, index) => (
                                        <td key={index} className="border p-2">
                                            {count}
                                        </td>
                                    ))}
                                </tr>
                            </tbody>
                        </table>
                        {sports.length === 0 && (
                            <p className="py-8 text-center">
                                No accepted medal awards for this Team in the
                                selected Meet.
                            </p>
                        )}
                        {sports.map((sport) => (
                            <section
                                key={sport.id}
                                className="team-sport-section"
                            >
                                <h2 className="mb-3 border-b pb-2 text-lg font-bold">
                                    {sport.name ?? '?'}
                                </h2>
                                {sport.events.map((event) => (
                                    <div key={event.id} className="mb-6">
                                        <h3 className="mb-2 font-semibold">
                                            {event.name ?? '?'}{' '}
                                            <span className="font-normal">
                                                {[event.division, event.gender]
                                                    .filter(Boolean)
                                                    .join(' ? ')}
                                            </span>
                                        </h3>
                                        <table className="w-full border-collapse text-left text-sm">
                                            <thead>
                                                <tr>
                                                    {[
                                                        'Medal',
                                                        'Medal tally count',
                                                        'Athlete(s)',
                                                        'Result / Mark',
                                                    ].map((label) => (
                                                        <th
                                                            key={label}
                                                            className="border p-2"
                                                        >
                                                            {label}
                                                        </th>
                                                    ))}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {event.awards.map((award) => (
                                                    <tr key={award.id}>
                                                        <td className="border p-2 capitalize">
                                                            {award.medal}
                                                        </td>
                                                        <td className="border p-2">
                                                            {award.tally_count}
                                                        </td>
                                                        <td className="border p-2 whitespace-pre-line">
                                                            {award.athletes.join(
                                                                '\n',
                                                            ) || '?'}
                                                        </td>
                                                        <td className="border p-2">
                                                            {award.mark ?? '?'}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                ))}
                                <footer className="my-8 grid grid-cols-2 gap-12 text-sm">
                                    {sport.signatories.map((signer) => (
                                        <div key={signer.label}>
                                            <p>{signer.label}:</p>
                                            <p className="mt-8 min-h-5 font-semibold">
                                                {signer.name ?? ''}
                                            </p>
                                            <p className="min-h-5">
                                                {signer.position ?? ''}
                                            </p>
                                        </div>
                                    ))}
                                </footer>
                            </section>
                        ))}
                    </>
                )}
            </div>
        </>
    );
}
