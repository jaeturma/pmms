import { Head, router, usePage } from '@inertiajs/react';
import { Crown } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { PublicMeetNav } from '@/components/public-meet-nav';
import { Badge } from '@/components/ui/badge';
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
import { tally as publicTally } from '@/routes/public';

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
    meet: {
        id: number;
        name: string;
        school_year: string;
        starts_at: string;
        ends_at: string;
        venue: string | null;
        status_label: string;
    };
    schools: SchoolRow[];
    districts: DistrictRow[];
    filters: { sport_id: number | null };
    sportOptions: Option[];
};

function MedalHeader() {
    return (
        <>
            <TableHead className="w-14 text-center">Gold</TableHead>
            <TableHead className="w-14 text-center">Silver</TableHead>
            <TableHead className="w-14 text-center">Bronze</TableHead>
            <TableHead className="w-14 text-center">Total</TableHead>
        </>
    );
}

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

export default function PublicTally({
    meet,
    schools,
    districts,
    filters,
    sportOptions,
}: Props) {
    const { division } = usePage().props;
    const areaLabel = division.areaLabel;

    const applySport = (value: string) => {
        router.get(
            publicTally(meet.id).url,
            value !== 'all' ? { sport_id: value } : {},
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={`Medal tally — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {meet.name}
                        </h1>
                        <Badge variant="secondary">{meet.status_label}</Badge>
                    </div>
                    <p className="mt-1 text-sm text-muted-foreground">
                        SY {meet.school_year} · Medal tally — computed from
                        validated results only; ties share medals.
                    </p>
                </div>

                <PublicMeetNav meetId={meet.id} active="tally" />

                {sportOptions.length > 0 && (
                    <Select
                        value={String(filters.sport_id ?? 'all')}
                        onValueChange={applySport}
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
                )}

                {districts.length === 0 ? (
                    <EmptyState
                        icon={Crown}
                        title="No medals yet"
                        description="Standings appear as soon as results are validated."
                    />
                ) : (
                    <>
                        <section className="flex flex-col gap-3">
                            <Heading
                                title={`${areaLabel} standings`}
                                description="Overall standings for this meet."
                            />
                            <div className="overflow-x-auto rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-10">
                                                #
                                            </TableHead>
                                            <TableHead>{areaLabel}</TableHead>
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

                        <section className="flex flex-col gap-3">
                            <Heading
                                variant="small"
                                title="School standings"
                                description="Reference only — shows which school each medal came from."
                            />
                            <div className="overflow-x-auto rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-10">
                                                #
                                            </TableHead>
                                            <TableHead>School</TableHead>
                                            <TableHead>{areaLabel}</TableHead>
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
                    </>
                )}
            </div>
        </>
    );
}
