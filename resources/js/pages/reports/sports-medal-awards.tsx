import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

type Option = { id: number; name: string };
type Award = {
    id: number;
    medal: string;
    tally_count: number;
    physical_count: number;
    athletes: string[];
    team: string | null;
    coaches: { name: string; role: string }[];
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
    sportOptions: Option[];
    sportId: number | null;
    canSelectAll: boolean;
    sportLabel: string;
    sports: Sport[];
    generatedAt: string;
};

export default function SportsMedalAwards({
    meet,
    meetOptions,
    sportOptions,
    sportId,
    canSelectAll,
    sportLabel,
    sports,
    generatedAt,
}: Props) {
    const select = (meetId: number, selectedSport?: string) =>
        router.get('/reports/sports-medal-awards', {
            meet_id: meetId,
            ...(selectedSport ? { sport_id: selectedSport } : {}),
        });
    const exportCsv = () => {
        const rows: (string | number | null)[][] = [
            ['SPORTS MEDAL AWARDS REPORT'],
            [
                meet.name,
                meet.school_year,
                meet.venue,
                meet.starts_at,
                meet.ends_at,
            ],
            [sportLabel],
            [
                'Sport',
                'Event',
                'Division',
                'Gender',
                'Medal',
                'Tally count',
                'Physical medals',
                'Athletes',
                'Team / Delegation',
                'Coach',
                'Result',
            ],
        ];
        for (const sport of sports) {
            for (const event of sport.events) {
                for (const award of event.awards)
                    rows.push([
                        sport.name,
                        event.name,
                        event.division,
                        event.gender,
                        award.medal,
                        award.tally_count,
                        award.physical_count,
                        award.athletes.join('; '),
                        award.team,
                        award.coaches.map((c) => c.name).join('; '),
                        award.mark,
                    ]);
            }
            for (const signer of sport.signatories)
                rows.push([
                    sport.name,
                    signer.label,
                    signer.name,
                    signer.position,
                ]);
        }
        const csv = rows
            .map((row) =>
                row
                    .map((value) => {
                        const text = String(value ?? '');
                        const safe = /^[=+@\-\t\r]/.test(text)
                            ? `'${text}`
                            : text;
                        return `"${safe.replaceAll('"', '""')}"`;
                    })
                    .join(','),
            )
            .join('\r\n');
        const url = URL.createObjectURL(
            new Blob(['\uFEFF', csv], { type: 'text/csv;charset=utf-8' }),
        );
        const link = document.createElement('a');
        link.href = url;
        link.download = 'sports-medal-awards.csv';
        link.click();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    };
    return (
        <>
            <Head title="Sports Medal Awards Report" />
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
                        Sport
                        <select
                            aria-label="Sport"
                            className="rounded border p-2"
                            value={sportId ?? ''}
                            onChange={(e) => select(meet.id, e.target.value)}
                        >
                            {canSelectAll && (
                                <option value="">All Sports</option>
                            )}
                            {sportOptions.map((option) => (
                                <option key={option.id} value={option.id}>
                                    {option.name}
                                </option>
                            ))}
                        </select>
                    </label>
                    <Button onClick={() => window.print()}>
                        Print / Save PDF
                    </Button>
                    <Button variant="outline" onClick={exportCsv}>
                        Download CSV
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
                    <h1 className="mt-4 text-xl font-bold">
                        SPORTS MEDAL AWARDS REPORT
                    </h1>
                    <p className="font-semibold">{sportLabel}</p>
                    <p className="mt-2 text-xs">
                        Accepted / published Results ? Generated {generatedAt}
                    </p>
                </header>
                {sports.length === 0 && (
                    <p className="py-8 text-center">
                        No accepted medal awards for this selection.
                    </p>
                )}
                {sports.map((sport) => (
                    <section key={sport.id} className="sport-section">
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
                                                'Tally count',
                                                'Physical medals',
                                                'Athlete(s)',
                                                'Team / Delegation',
                                                'Coach',
                                                'Result',
                                            ].map((title) => (
                                                <th
                                                    key={title}
                                                    className="border p-2"
                                                >
                                                    {title}
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
                                                <td className="border p-2">
                                                    {award.physical_count}
                                                </td>
                                                <td className="border p-2 whitespace-pre-line">
                                                    {award.athletes.join(
                                                        '\n',
                                                    ) || '?'}
                                                </td>
                                                <td className="border p-2">
                                                    {award.team || '?'}
                                                </td>
                                                <td className="border p-2 whitespace-pre-line">
                                                    {award.coaches
                                                        .map((c) => c.name)
                                                        .join('\n') || '?'}
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
            </div>
        </>
    );
}
