import { Head } from '@inertiajs/react';
import { Activity, FileText, Users } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes';

type Stat = {
    key: string;
    label: string;
    value: number;
};

type ActivityEntry = {
    id: number;
    action: string;
    user: string | null;
    created_at_human: string | null;
};

type Props = {
    stats: Stat[];
    recentActivity: ActivityEntry[];
};

const statIcons: Record<string, LucideIcon> = {
    users: Users,
    uploads: FileText,
    activity_today: Activity,
};

export default function Dashboard({ stats, recentActivity }: Props) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Dashboard"
                    description="Overview of activity across the system."
                />

                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    {stats.map((stat) => (
                        <StatCard
                            key={stat.key}
                            label={stat.label}
                            value={stat.value}
                            icon={statIcons[stat.key]}
                        />
                    ))}
                </div>

                <section className="flex flex-col gap-3">
                    <h2 className="text-base font-medium">Recent Activity</h2>
                    {recentActivity.length === 0 ? (
                        <EmptyState
                            icon={Activity}
                            title="No activity yet"
                            description="Actions performed in the system will appear here."
                        />
                    ) : (
                        <div className="rounded-xl border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Action</TableHead>
                                        <TableHead>User</TableHead>
                                        <TableHead>When</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recentActivity.map((entry) => (
                                        <TableRow key={entry.id}>
                                            <TableCell className="font-medium">
                                                {entry.action}
                                            </TableCell>
                                            <TableCell>
                                                {entry.user ?? 'System'}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {entry.created_at_human}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
