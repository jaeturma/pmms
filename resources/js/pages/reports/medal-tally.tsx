import { Head } from '@inertiajs/react';
import { Crown } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { PageHeader } from '@/components/page-header';
import { ReportActions } from '@/components/report-actions';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { download } from '@/routes/reports/tally';
import { index as tallyIndex } from '@/routes/tally';

type SchoolRow = {
    position: number;
    school: string;
    district: string;
    gold: number;
    silver: number;
    bronze: number;
    total: number;
};

type DistrictRow = {
    position: number;
    district: string;
    gold: number;
    silver: number;
    bronze: number;
    total: number;
};

type Props = {
    schools: SchoolRow[];
    districts: DistrictRow[];
    meet: string | null;
    sport: string | null;
    filters: { meet_id: number | null; sport_id: number | null };
    generatedAt: string;
};

export default function MedalTallyReport({
    schools,
    districts,
    meet,
    sport,
    filters,
    generatedAt,
}: Props) {
    const query = {
        ...(filters.meet_id ? { meet_id: filters.meet_id } : {}),
        ...(filters.sport_id ? { sport_id: filters.sport_id } : {}),
    };

    return (
        <>
            <Head title="Medal tally report" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Medal tally report"
                    description={`${meet ?? 'All meets'} · ${sport ?? 'All sports'} — validated results only`}
                    actions={
                        <ReportActions downloadUrl={download({ query }).url} />
                    }
                />

                <p className="text-sm text-muted-foreground">
                    Generated {generatedAt}
                </p>

                {schools.length === 0 ? (
                    <EmptyState
                        icon={Crown}
                        title="No medals yet"
                        description="Validated results produce the tally."
                    />
                ) : (
                    <>
                        <section className="space-y-3">
                            <Heading variant="small" title="School standings" />
                            <div className="overflow-x-auto rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-12">
                                                #
                                            </TableHead>
                                            <TableHead>School</TableHead>
                                            <TableHead>District</TableHead>
                                            <TableHead className="w-16 text-center">
                                                Gold
                                            </TableHead>
                                            <TableHead className="w-16 text-center">
                                                Silver
                                            </TableHead>
                                            <TableHead className="w-16 text-center">
                                                Bronze
                                            </TableHead>
                                            <TableHead className="w-16 text-center">
                                                Total
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {schools.map((row) => (
                                            <TableRow key={row.school}>
                                                <TableCell>
                                                    {row.position}
                                                </TableCell>
                                                <TableCell className="font-medium">
                                                    {row.school}
                                                </TableCell>
                                                <TableCell>
                                                    {row.district}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.gold}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.silver}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.bronze}
                                                </TableCell>
                                                <TableCell className="text-center font-medium">
                                                    {row.total}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </section>

                        <section className="space-y-3">
                            <Heading
                                variant="small"
                                title="District standings"
                            />
                            <div className="overflow-x-auto rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-12">
                                                #
                                            </TableHead>
                                            <TableHead>District</TableHead>
                                            <TableHead className="w-16 text-center">
                                                Gold
                                            </TableHead>
                                            <TableHead className="w-16 text-center">
                                                Silver
                                            </TableHead>
                                            <TableHead className="w-16 text-center">
                                                Bronze
                                            </TableHead>
                                            <TableHead className="w-16 text-center">
                                                Total
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {districts.map((row) => (
                                            <TableRow key={row.district}>
                                                <TableCell>
                                                    {row.position}
                                                </TableCell>
                                                <TableCell className="font-medium">
                                                    {row.district}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.gold}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.silver}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {row.bronze}
                                                </TableCell>
                                                <TableCell className="text-center font-medium">
                                                    {row.total}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </section>
                    </>
                )}
            </div>
        </>
    );
}

MedalTallyReport.layout = {
    breadcrumbs: [
        {
            title: 'Medal tally',
            href: tallyIndex(),
        },
    ],
};
