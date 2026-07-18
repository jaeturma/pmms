import { Head } from '@inertiajs/react';
import { Users } from 'lucide-react';
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
import { index as delegationsIndex } from '@/routes/delegations';
import { download } from '@/routes/reports/roster';

type AthleteRow = {
    id: number;
    last_name: string;
    first_name: string;
    sex_label: string;
    birthdate: string;
    age: number;
    lrn: string;
    grade_level: number;
};

type PersonnelRow = {
    id: number;
    last_name: string;
    first_name: string;
    role_label: string;
    sports: string;
};

type Props = {
    delegation: {
        id: number;
        school: string;
        meet: string;
        school_year: string;
        head_name: string;
        status_label: string;
    };
    athletes: AthleteRow[];
    personnel: PersonnelRow[];
    generatedAt: string;
};

export default function DelegationRoster({
    delegation,
    athletes,
    personnel,
    generatedAt,
}: Props) {
    return (
        <>
            <Head title={`Roster — ${delegation.school}`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Delegation roster"
                    description={`${delegation.school} — ${delegation.meet} (${delegation.school_year})`}
                    actions={
                        <ReportActions
                            downloadUrl={download(delegation.id).url}
                        />
                    }
                />

                <p className="text-sm text-muted-foreground">
                    Head of delegation: {delegation.head_name} · Status:{' '}
                    {delegation.status_label} · Generated {generatedAt}
                </p>

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title={`Athletes (${athletes.length})`}
                    />
                    {athletes.length === 0 ? (
                        <EmptyState
                            icon={Users}
                            title="No athletes registered"
                        />
                    ) : (
                        <div className="overflow-x-auto rounded-xl border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>#</TableHead>
                                        <TableHead>Last name</TableHead>
                                        <TableHead>First name</TableHead>
                                        <TableHead>Sex</TableHead>
                                        <TableHead>Birthdate</TableHead>
                                        <TableHead>Age</TableHead>
                                        <TableHead>LRN</TableHead>
                                        <TableHead>Grade</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {athletes.map((athlete, i) => (
                                        <TableRow key={athlete.id}>
                                            <TableCell>{i + 1}</TableCell>
                                            <TableCell className="font-medium">
                                                {athlete.last_name}
                                            </TableCell>
                                            <TableCell>
                                                {athlete.first_name}
                                            </TableCell>
                                            <TableCell>
                                                {athlete.sex_label}
                                            </TableCell>
                                            <TableCell>
                                                {athlete.birthdate}
                                            </TableCell>
                                            <TableCell>{athlete.age}</TableCell>
                                            <TableCell>{athlete.lrn}</TableCell>
                                            <TableCell>
                                                {athlete.grade_level}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </section>

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title={`Personnel (${personnel.length})`}
                    />
                    {personnel.length === 0 ? (
                        <EmptyState
                            icon={Users}
                            title="No personnel registered"
                        />
                    ) : (
                        <div className="overflow-x-auto rounded-xl border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>#</TableHead>
                                        <TableHead>Last name</TableHead>
                                        <TableHead>First name</TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead>Sports</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {personnel.map((person, i) => (
                                        <TableRow key={person.id}>
                                            <TableCell>{i + 1}</TableCell>
                                            <TableCell className="font-medium">
                                                {person.last_name}
                                            </TableCell>
                                            <TableCell>
                                                {person.first_name}
                                            </TableCell>
                                            <TableCell>
                                                {person.role_label}
                                            </TableCell>
                                            <TableCell>
                                                {person.sports || '—'}
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

DelegationRoster.layout = {
    breadcrumbs: [
        {
            title: 'Delegations',
            href: delegationsIndex(),
        },
    ],
};
