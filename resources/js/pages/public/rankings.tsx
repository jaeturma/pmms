import { Head, Link, usePage } from '@inertiajs/react';
import { Crown, Info } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { MedalCells, MedalHeader } from '@/components/medal-table-parts';
import { PublicPageHero } from '@/components/public-page-hero';
import { RankBadge } from '@/components/rank-badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { tally as publicTally } from '@/routes/public';

type DistrictRow = {
    position: number;
    district: string;
    gold: number;
    silver: number;
    bronze: number;
    total: number;
    points: number;
};

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
    districts: DistrictRow[];
    generatedAt: string;
};

export default function PublicRankings({
    meet,
    districts,
    generatedAt,
}: Props) {
    const { division } = usePage().props;
    const areaLabel = division.areaLabel;

    return (
        <>
            <Head title={`Rankings — ${meet.name}`} />
            <div className="flex flex-col gap-6 sm:gap-8">
                <PublicPageHero
                    title="Rankings"
                    description={`Official overall ranking of every competing ${areaLabel.toLowerCase()} in this meet.`}
                    meta={
                        <div className="flex flex-wrap items-center gap-2">
                            <span>{meet.name}</span>
                            <Badge
                                variant="secondary"
                                className="bg-white/15 text-white"
                            >
                                {meet.status_label}
                            </Badge>
                        </div>
                    }
                />

                {districts.length === 0 ? (
                    <EmptyState
                        icon={Crown}
                        title="No medals yet"
                        description="Rankings appear as soon as results are validated."
                    />
                ) : (
                    <div className="flex animate-card-in flex-col gap-4 sm:gap-6">
                        <div className="overflow-x-auto rounded-xl border">
                            <Table className="text-base">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-12">
                                            Rank
                                        </TableHead>
                                        <TableHead>{areaLabel}</TableHead>
                                        <MedalHeader />
                                        <TableHead className="w-20 text-center">
                                            Points
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {districts.map((row) => (
                                        <TableRow key={row.district}>
                                            <TableCell>
                                                <RankBadge
                                                    position={row.position}
                                                />
                                            </TableCell>
                                            <TableCell className="font-medium">
                                                {row.district}
                                            </TableCell>
                                            <MedalCells row={row} />
                                            <TableCell className="text-center font-medium">
                                                {row.points}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Rank is based on: Gold, then Silver, then Bronze.
                            Points (Gold=3, Silver=2, Bronze=1) are shown for
                            reference — they do not change the official rank
                            order above.
                        </p>
                    </div>
                )}

                <Alert>
                    <Info aria-hidden="true" />
                    <AlertDescription>
                        As of {generatedAt}. For the full medal breakdown by
                        sport and school, see{' '}
                        <Link
                            href={publicTally(meet.id)}
                            className="font-medium underline underline-offset-2"
                        >
                            Medal Tally
                        </Link>
                        .
                    </AlertDescription>
                </Alert>
            </div>
        </>
    );
}
