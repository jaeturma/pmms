import { Head, router } from '@inertiajs/react';
import { ScrollText } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { PaginationControls } from '@/components/pagination-controls';
import type { Paginated } from '@/components/pagination-controls';
import { SearchBar } from '@/components/search-bar';
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
import { index } from '@/routes/audit-logs';

type AuditLogRow = {
    id: number;
    action: string;
    user: string | null;
    subject: string | null;
    context: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string | null;
};

type Props = {
    logs: Paginated<AuditLogRow>;
    filters: { search: string; action: string | null };
    actionOptions: string[];
};

function contextSummary(context: Record<string, unknown> | null): string {
    if (!context) {
        return '';
    }

    return Object.entries(context)
        .map(([key, value]) => `${key}: ${String(value)}`)
        .join(', ');
}

export default function AuditLogs({ logs, filters, actionOptions }: Props) {
    const searchParams: Record<string, string> = filters.search
        ? { search: filters.search }
        : {};
    const actionParams: Record<string, string> = filters.action
        ? { action: filters.action }
        : {};

    const applyAction = (value: string) => {
        router.get(
            index().url,
            {
                ...searchParams,
                ...(value === 'all' ? {} : { action: value }),
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Audit log" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Audit log"
                    description="Who did what, when, and from where."
                />

                <div className="flex flex-wrap gap-2">
                    <SearchBar
                        initial={filters.search}
                        placeholder="Search action or user"
                        url={index().url}
                        extraParams={actionParams}
                    />
                    <Select
                        value={filters.action ?? 'all'}
                        onValueChange={applyAction}
                    >
                        <SelectTrigger
                            className="w-64"
                            aria-label="Filter by action"
                        >
                            <SelectValue placeholder="All actions" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All actions</SelectItem>
                            {actionOptions.map((action) => (
                                <SelectItem key={action} value={action}>
                                    {action}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {logs.data.length === 0 ? (
                    <EmptyState
                        icon={ScrollText}
                        title="No audit entries found"
                        description="Recorded actions will appear here."
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>When</TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead>Action</TableHead>
                                    <TableHead>Subject</TableHead>
                                    <TableHead>Details</TableHead>
                                    <TableHead>IP</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {logs.data.map((log) => (
                                    <TableRow key={log.id}>
                                        <TableCell className="whitespace-nowrap">
                                            {log.created_at}
                                        </TableCell>
                                        <TableCell>
                                            {log.user ?? 'System'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {log.action}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {log.subject ?? '—'}
                                        </TableCell>
                                        <TableCell className="max-w-xs truncate text-muted-foreground">
                                            {contextSummary(log.context)}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {log.ip_address ?? '—'}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <PaginationControls
                    page={logs}
                    url={index().url}
                    label="entries"
                    params={{ ...searchParams, ...actionParams }}
                />
            </div>
        </>
    );
}

AuditLogs.layout = {
    breadcrumbs: [
        {
            title: 'Audit log',
            href: index(),
        },
    ],
};
