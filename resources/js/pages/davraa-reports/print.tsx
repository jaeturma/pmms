import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { index as davraaIndex } from '@/routes/davraa-reports';

type Row = {
    no: number;
    designation: string;
    lrn: string;
    last_name: string;
    given_names: string;
    middle_initial: string;
    school_name: string;
    district_name: string;
    incomplete: string[];
};

type Props = {
    report: {
        id: number;
        name: string;
        sport: string | null;
        event: string;
        division: string;
        level: string;
        status_label: string;
        notes: string | null;
        rows: Row[];
    };
};

const SIGNATORIES = [
    ['Prepared By:', 'Tournament Secretary'],
    ['Recommended By:', 'Tournament Manager'],
];

export default function DavraaReportPrint({ report }: Props) {
    const incomplete = report.rows.some((r) => r.incomplete.length > 0);

    return (
        <>
            <Head title={`DAVRAA — ${report.name}`} />
            <style>{`
                @page { size: A4 portrait; margin: 14mm; }
                @media print {
                    body * { visibility: hidden; }
                    #davraa-report, #davraa-report * { visibility: visible; }
                    #davraa-report { position: absolute; inset: 0; padding: 0; }
                    .no-print { display: none !important; }
                }
            `}</style>

            <div className="no-print mx-auto flex max-w-4xl items-center justify-between gap-3 p-4">
                <Button variant="outline" asChild>
                    <Link href={davraaIndex().url}>
                        <ArrowLeft /> Back
                    </Link>
                </Button>
                <Button onClick={() => window.print()}>
                    <Printer /> Print
                </Button>
            </div>

            {incomplete && (
                <p className="no-print mx-auto max-w-4xl px-4 text-sm text-amber-700">
                    Some rows have incomplete data (LRN / school / district).
                    They are printed as blanks — fill them in the source record
                    or edit the report before submitting.
                </p>
            )}

            <main
                id="davraa-report"
                className="mx-auto max-w-4xl bg-white p-8 text-black"
            >
                <header className="mb-4 text-center">
                    <h1 className="text-base font-bold tracking-wide">
                        LIST OF RECOMMENDED QUALIFIERS TO DAVRAA
                    </h1>
                    <div className="mt-2 space-y-0.5 text-sm">
                        <p>
                            <span className="font-semibold">Event:</span>{' '}
                            {report.event}
                        </p>
                        <p>
                            <span className="font-semibold">Division:</span>{' '}
                            {report.division}
                        </p>
                        <p>
                            <span className="font-semibold">Level:</span>{' '}
                            {report.level}
                        </p>
                    </div>
                </header>

                <table className="w-full border-collapse text-[11px]">
                    <thead>
                        <tr className="bg-neutral-100">
                            {[
                                'No.',
                                'Designation',
                                'LRN',
                                'Last Name',
                                'Given Name(s)',
                                'M.I.',
                                'Name of School',
                                'Name of District',
                            ].map((label) => (
                                <th
                                    key={label}
                                    className="border border-black px-1.5 py-1 text-left font-semibold"
                                >
                                    {label}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {report.rows.length === 0 && (
                            <tr>
                                <td
                                    colSpan={8}
                                    className="border border-black px-1.5 py-3 text-center text-neutral-500"
                                >
                                    No qualifiers recorded yet.
                                </td>
                            </tr>
                        )}
                        {report.rows.map((row) => (
                            <tr key={row.no}>
                                <td className="border border-black px-1.5 py-1 text-center">
                                    {row.no}
                                </td>
                                <td className="border border-black px-1.5 py-1">
                                    {row.designation}
                                </td>
                                <td className="border border-black px-1.5 py-1 tabular-nums">
                                    {row.lrn}
                                </td>
                                <td className="border border-black px-1.5 py-1 uppercase">
                                    {row.last_name}
                                </td>
                                <td className="border border-black px-1.5 py-1 uppercase">
                                    {row.given_names}
                                </td>
                                <td className="border border-black px-1.5 py-1 text-center uppercase">
                                    {row.middle_initial}
                                </td>
                                <td className="border border-black px-1.5 py-1">
                                    {row.school_name}
                                </td>
                                <td className="border border-black px-1.5 py-1">
                                    {row.district_name}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                <div className="mt-12 grid grid-cols-2 gap-10 text-sm">
                    {SIGNATORIES.map(([label, role]) => (
                        <div key={role}>
                            <p>{label}</p>
                            <div className="mt-10 border-t border-black pt-1 text-center font-semibold">
                                {role}
                            </div>
                        </div>
                    ))}
                </div>
            </main>
        </>
    );
}
