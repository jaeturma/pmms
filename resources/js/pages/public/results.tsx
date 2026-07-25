import { Head, router } from '@inertiajs/react';
import { Award } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
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
import { results as publicResults } from '@/routes/public';

type Placement = {
    rank: number;
    athlete: string;
    school: string;
    mark: string | null;
    is_tie: boolean;
};

type Result = {
    id: number;
    event: string;
    official_as_of: string | null;
    placements: Placement[];
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
    results: Result[];
    filters: { sport_id: number | null };
    sportOptions: Option[];
};

export default function PublicResults({
    meet,
    results,
    filters,
    sportOptions,
}: Props) {
    const applySport = (value: string) => {
        router.get(
            publicResults(meet.id).url,
            value !== 'all' ? { sport_id: value } : {},
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={`Results — ${meet.name}`} />
            <div className="flex flex-col gap-6">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {meet.name}
                        </h1>
                        <Badge variant="secondary">{meet.status_label}</Badge>
                    </div>
                    <p className="mt-1 text-sm text-muted-foreground">
                        SY {meet.school_year} · Official results — validated
                        standings only.
                    </p>
                </div>

                <PublicMeetNav meetId={meet.id} active="results" />

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

                {results.length === 0 ? (
                    <EmptyState
                        icon={Award}
                        title="No results yet"
                        description="Official results appear here as soon as they are validated."
                    />
                ) : (
                    <div className="flex flex-col gap-4">
                        {results.map((result) => (
                            <section
                                key={result.id}
                                className="rounded-xl border"
                            >
                                <div className="border-b p-4">
                                    <p className="font-medium">
                                        {result.event}
                                    </p>
                                    {result.official_as_of && (
                                        <p className="text-sm text-muted-foreground">
                                            Official as of{' '}
                                            {result.official_as_of}
                                        </p>
                                    )}
                                </div>
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-16">
                                                    Rank
                                                </TableHead>
                                                <TableHead>Athlete</TableHead>
                                                <TableHead>School</TableHead>
                                                <TableHead>
                                                    Score / mark
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {result.placements.map(
                                                (placement, i) => (
                                                    <TableRow key={i}>
                                                        <TableCell className="font-medium">
                                                            {placement.rank}
                                                            {placement.is_tie &&
                                                                ' (tie)'}
                                                        </TableCell>
                                                        <TableCell>
                                                            {placement.athlete}
                                                        </TableCell>
                                                        <TableCell>
                                                            {placement.school}
                                                        </TableCell>
                                                        <TableCell>
                                                            {placement.mark ??
                                                                '—'}
                                                        </TableCell>
                                                    </TableRow>
                                                ),
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </section>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
