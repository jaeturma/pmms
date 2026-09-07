import { Head, Link, router } from '@inertiajs/react';
import { Copy, FileDown, FilePlus2, Pencil, Printer } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
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
import {
    create as davraaCreate,
    duplicate as davraaDuplicate,
    edit as davraaEdit,
    exportMethod as davraaExport,
    index as davraaIndex,
    print as davraaPrint,
    status as davraaStatus,
} from '@/routes/davraa-reports';

type Group = {
    id: number;
    name: string;
    sport: string;
    division: string;
    level: string;
    status: string;
    status_label: string;
    events_count: number;
    athletes_count: number;
    staff_count: number;
    created_by: string | null;
    can_manage: boolean;
};

type Props = {
    groups: Group[];
    filters: { status: string | null };
    canCreate: boolean;
    meet: string;
};

export default function DavraaReportsIndex({
    groups,
    filters,
    canCreate,
    meet,
}: Props) {
    const setStatus = (status: string) =>
        router.get(davraaIndex().url, status === 'all' ? {} : { status }, {
            preserveState: true,
            preserveScroll: true,
        });

    const changeStatus = (group: Group, status: string) =>
        router.patch(
            davraaStatus(group.id).url,
            { status },
            { preserveScroll: true },
        );

    return (
        <>
            <Head title="DAVRAA Report" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="DAVRAA Report"
                    description={`List of Recommended Qualifiers to DAVRAA — ${meet}. Grouping is set by the Tournament ICT, not derived from matches or results.`}
                    actions={
                        canCreate && (
                            <Button asChild>
                                <Link href={davraaCreate().url}>
                                    <FilePlus2 /> New report group
                                </Link>
                            </Button>
                        )
                    }
                />

                <div className="flex items-center gap-3 rounded-xl border bg-muted/20 p-4">
                    <Select
                        value={filters.status ?? 'all'}
                        onValueChange={setStatus}
                    >
                        <SelectTrigger
                            className="w-52"
                            aria-label="Filter by status"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                Draft &amp; finalized
                            </SelectItem>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="final">Finalized</SelectItem>
                            <SelectItem value="archived">Archived</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {groups.length === 0 ? (
                    <EmptyState
                        icon={FilePlus2}
                        title="No DAVRAA report groups yet"
                        description={
                            canCreate
                                ? 'Create a report group and pick the Sports Events, Coaches and Athletes that belong together.'
                                : 'Report groups created for your assigned sports will appear here.'
                        }
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Report Group</TableHead>
                                    <TableHead>Sport</TableHead>
                                    <TableHead>Division / Level</TableHead>
                                    <TableHead className="text-center">
                                        Events
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Athletes
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Staff
                                    </TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {groups.map((group) => (
                                    <TableRow key={group.id}>
                                        <TableCell className="font-medium">
                                            {group.name}
                                            {group.created_by && (
                                                <span className="block text-xs text-muted-foreground">
                                                    by {group.created_by}
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>{group.sport}</TableCell>
                                        <TableCell>
                                            {group.division} · {group.level}
                                        </TableCell>
                                        <TableCell className="text-center tabular-nums">
                                            {group.events_count}
                                        </TableCell>
                                        <TableCell className="text-center tabular-nums">
                                            {group.athletes_count}
                                        </TableCell>
                                        <TableCell className="text-center tabular-nums">
                                            {group.staff_count}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    group.status === 'final'
                                                        ? 'default'
                                                        : group.status ===
                                                            'archived'
                                                          ? 'outline'
                                                          : 'secondary'
                                                }
                                            >
                                                {group.status_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-wrap justify-end gap-1">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <a
                                                        href={
                                                            davraaPrint(
                                                                group.id,
                                                            ).url
                                                        }
                                                        target="_blank"
                                                        rel="noreferrer"
                                                    >
                                                        <Printer /> Print
                                                    </a>
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <a
                                                        href={
                                                            davraaExport(
                                                                group.id,
                                                            ).url
                                                        }
                                                    >
                                                        <FileDown /> Excel
                                                    </a>
                                                </Button>
                                                {group.can_manage && (
                                                    <>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={
                                                                    davraaEdit(
                                                                        group.id,
                                                                    ).url
                                                                }
                                                            >
                                                                <Pencil /> Edit
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                router.post(
                                                                    davraaDuplicate(
                                                                        group.id,
                                                                    ).url,
                                                                )
                                                            }
                                                        >
                                                            <Copy /> Duplicate
                                                        </Button>
                                                        {group.status !==
                                                        'archived' ? (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() =>
                                                                    changeStatus(
                                                                        group,
                                                                        'archived',
                                                                    )
                                                                }
                                                            >
                                                                Archive
                                                            </Button>
                                                        ) : (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() =>
                                                                    changeStatus(
                                                                        group,
                                                                        'draft',
                                                                    )
                                                                }
                                                            >
                                                                Restore
                                                            </Button>
                                                        )}
                                                    </>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>
        </>
    );
}

DavraaReportsIndex.layout = {
    breadcrumbs: [{ title: 'DAVRAA Report', href: '/davraa-reports' }],
};
