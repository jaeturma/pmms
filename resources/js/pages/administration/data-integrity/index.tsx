import { Head, Link, router } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Issue = {
    key: string;
    type: string;
    source_type: string;
    source_id: number;
    sport: string;
    event: string;
    delegation: string;
    relationship: string;
    severity: string;
    blocks_operation: boolean;
    suggested_action: string;
    repair_url: string | null;
};
type Props = {
    issues: Issue[];
    counts: Record<string, number>;
    total: number;
    page: number;
    filters: { type: string };
    meet: string;
};

export default function DataIntegrity({
    issues,
    counts,
    total,
    page,
    filters,
    meet,
}: Props) {
    const navigate = (nextPage: number, type = filters.type) =>
        router.get(
            '/administration/data-integrity',
            { page: nextPage, type },
            { preserveState: true },
        );

    return (
        <>
            <Head title="Data Integrity" />
            <div className="space-y-5 p-4">
                <PageHeader
                    title="Data Integrity"
                    description={`Relationship concerns for ${meet}. Review each record before repairing it.`}
                />
                <p className="rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm text-sky-950">
                    This scan does not change records. Missing reporting links
                    do not prevent result acceptance or medal counting.
                </p>
                <div className="flex flex-wrap items-center gap-3">
                    <label className="text-sm">
                        Issue type{' '}
                        <select
                            aria-label="Issue type"
                            value={filters.type}
                            onChange={(e) => navigate(1, e.target.value)}
                            className="ml-2 rounded border bg-background p-2"
                        >
                            <option value="">All concerns</option>
                            {Object.entries(counts).map(([type, count]) => (
                                <option key={type} value={type}>
                                    {type.replaceAll('_', ' ')} ({count})
                                </option>
                            ))}
                        </select>
                    </label>
                    <span className="text-sm text-muted-foreground">
                        {total} concerns
                    </span>
                    <Button variant="outline" onClick={() => navigate(page)}>
                        Refresh scan
                    </Button>
                </div>
                {issues.length === 0 ? (
                    <p className="rounded border p-6">
                        No concerns found for this filter.
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted">
                                <tr>
                                    {[
                                        'Source / issue',
                                        'Sport / Event',
                                        'Delegation',
                                        'Missing relationship',
                                        'Severity',
                                        'Suggested action',
                                    ].map((label) => (
                                        <th key={label} className="p-3">
                                            {label}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {issues.map((issue) => (
                                    <tr
                                        key={issue.key}
                                        className="border-t align-top"
                                    >
                                        <td className="p-3">
                                            <strong>
                                                {issue.source_type} #
                                                {issue.source_id}
                                            </strong>
                                            <p>
                                                {issue.type.replaceAll(
                                                    '_',
                                                    ' ',
                                                )}
                                            </p>
                                        </td>
                                        <td className="p-3">
                                            {issue.sport}
                                            <p className="text-muted-foreground">
                                                {issue.event}
                                            </p>
                                        </td>
                                        <td className="p-3">
                                            {issue.delegation}
                                        </td>
                                        <td className="p-3">
                                            {issue.relationship}
                                        </td>
                                        <td className="p-3">
                                            <Badge
                                                variant={
                                                    issue.severity === 'error'
                                                        ? 'destructive'
                                                        : 'outline'
                                                }
                                            >
                                                {issue.severity}
                                            </Badge>
                                            <p className="mt-1">
                                                {issue.blocks_operation
                                                    ? 'Not selectable until repaired'
                                                    : 'Reporting only; operations can continue'}
                                            </p>
                                        </td>
                                        <td className="max-w-xs space-y-2 p-3">
                                            <p>{issue.suggested_action}</p>
                                            {issue.repair_url && (
                                                <Button
                                                    asChild
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    <Link
                                                        href={issue.repair_url}
                                                    >
                                                        {issue.type ===
                                                            'orphan_roster' ||
                                                        issue.type ===
                                                            'cross_delegation_roster'
                                                            ? 'Link Athlete'
                                                            : 'Review record'}
                                                    </Link>
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
                <div className="flex items-center gap-3">
                    <Button
                        variant="outline"
                        disabled={page <= 1}
                        onClick={() => navigate(page - 1)}
                    >
                        Previous
                    </Button>
                    <span>
                        Page {page} of {Math.max(1, Math.ceil(total / 50))}
                    </span>
                    <Button
                        variant="outline"
                        disabled={page * 50 >= total}
                        onClick={() => navigate(page + 1)}
                    >
                        Next
                    </Button>
                </div>
            </div>
        </>
    );
}

DataIntegrity.layout = {
    breadcrumbs: [
        { title: 'Data Integrity', href: '/administration/data-integrity' },
    ],
};
