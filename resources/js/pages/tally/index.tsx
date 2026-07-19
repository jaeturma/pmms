import { Head, Link, router } from '@inertiajs/react';
import { Crown, Printer } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { tally as tallyReport } from '@/routes/reports';
import { index } from '@/routes/tally';

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

type Option = { id: number; label: string };

type Props = {
    schools: SchoolRow[];
    districts: DistrictRow[];
    filters: { meet_id: number | null; sport_id: number | null };
    meetOptions: Option[];
    sportOptions: Option[];
    generatedAt: string;
};

function MedalCells({
    row,
}: {
    row: Pick<SchoolRow, 'gold' | 'silver' | 'bronze' | 'total'>;
}) {
    return (
        <>
            <TableCell className="text-center">{row.gold}</TableCell>
            <TableCell className="text-center">{row.silver}</TableCell>
            <TableCell className="text-center">{row.bronze}</TableCell>
            <TableCell className="text-center font-medium">
                {row.total}
            </TableCell>
        </>
    );
}

function MedalHeader() {
    return (
        <>
            <TableHead className="w-16 text-center">Gold</TableHead>
            <TableHead className="w-16 text-center">Silver</TableHead>
            <TableHead className="w-16 text-center">Bronze</TableHead>
            <TableHead className="w-16 text-center">Total</TableHead>
        </>
    );
}

export default function Tally({
    schools,
    districts,
    filters,
    meetOptions,
    sportOptions,
    generatedAt,
}: Props) {
    const applyFilters = (overrides: {
        meet_id?: string;
        sport_id?: string;
    }) => {
        const params: Record<string, string> = {};

        const meetId = overrides.meet_id ?? String(filters.meet_id ?? '');
        const sportId = overrides.sport_id ?? String(filters.sport_id ?? '');

        if (meetId && meetId !== 'all') {
            params.meet_id = meetId;
        }

        if (sportId && sportId !== 'all') {
            params.sport_id = sportId;
        }

        router.get(index().url, params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Medal tally" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Medal tally"
                    description="Standings computed from validated results only."
                    actions={
                        <Button variant="outline" asChild>
                            <Link
                                href={
                                    tallyReport({
                                        query: {
                                            ...(filters.meet_id
                                                ? { meet_id: filters.meet_id }
                                                : {}),
                                            ...(filters.sport_id
                                                ? {
                                                      sport_id:
                                                          filters.sport_id,
                                                  }
                                                : {}),
                                        },
                                    }).url
                                }
                            >
                                <Printer />
                                Printable report
                            </Link>
                        </Button>
                    }
                />

                <div className="flex flex-wrap gap-2">
                    <Select
                        value={String(filters.meet_id ?? 'all')}
                        onValueChange={(value) =>
                            applyFilters({ meet_id: value })
                        }
                    >
                        <SelectTrigger
                            className="w-56"
                            aria-label="Filter by meet"
                        >
                            <SelectValue placeholder="All meets" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All meets</SelectItem>
                            {meetOptions.map((option) => (
                                <SelectItem
                                    key={option.id}
                                    value={String(option.id)}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={String(filters.sport_id ?? 'all')}
                        onValueChange={(value) =>
                            applyFilters({ sport_id: value })
                        }
                    >
                        <SelectTrigger
                            className="w-56"
                            aria-label="Filter by sport"
                        >
                            <SelectValue placeholder="All sports" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All sports</SelectItem>
                            {sportOptions.map((option) => (
                                <SelectItem
                                    key={option.id}
                                    value={String(option.id)}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <p className="text-sm text-muted-foreground">
                    Generated {generatedAt}. Ties share medals; corrections to
                    validated results update the tally automatically.
                </p>

                {schools.length === 0 ? (
                    <EmptyState
                        icon={Crown}
                        title="No medals yet"
                        description="Standings appear as soon as results are validated."
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
                                            <MedalHeader />
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
                                                <MedalCells row={row} />
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
                                            <MedalHeader />
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
                                                <MedalCells row={row} />
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

Tally.layout = {
    breadcrumbs: [
        {
            title: 'Medal tally',
            href: index(),
        },
    ],
};
