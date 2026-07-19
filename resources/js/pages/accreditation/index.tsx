import { Head, Link, router } from '@inertiajs/react';
import { IdCard, Printer, Users } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { card, cards, destroy, store } from '@/routes/accreditation';
import { index as delegationsIndex } from '@/routes/delegations';

type AccreditationRef = {
    id: number;
    number: string | null;
};

type AthleteRow = {
    id: number;
    name: string;
    grade_level: number;
    division_label: string;
    eligibility_approved: boolean;
    accreditation: AccreditationRef | null;
    can_accredit: boolean;
};

type PersonnelRow = {
    id: number;
    name: string;
    role_label: string;
    accreditation: AccreditationRef | null;
    can_accredit: boolean;
};

type Props = {
    delegation: {
        id: number;
        school: string;
        meet: string;
        school_year: string;
        status_label: string;
        approved: boolean;
    };
    athletes: AthleteRow[];
    personnel: PersonnelRow[];
    accreditedCount: number;
    canManage: boolean;
};

function accredit(payload: { athlete_id?: number; personnel_id?: number }) {
    router.post(store().url, payload, { preserveScroll: true });
}

function AccreditationActions({
    accreditation,
    canAccredit,
    canManage,
    onAccredit,
    personLabel,
}: {
    accreditation: AccreditationRef | null;
    canAccredit: boolean;
    canManage: boolean;
    onAccredit: () => void;
    personLabel: string;
}) {
    return (
        <div className="flex justify-end gap-2">
            {accreditation && (
                <Button variant="outline" size="sm" asChild>
                    <Link href={card(accreditation.id)}>
                        <IdCard />
                        Card
                    </Link>
                </Button>
            )}
            {canManage && canAccredit && (
                <Button size="sm" onClick={onAccredit}>
                    Accredit
                </Button>
            )}
            {canManage && accreditation && (
                <ConfirmDialog
                    trigger={
                        <Button variant="destructive" size="sm">
                            Revoke
                        </Button>
                    }
                    title="Revoke accreditation?"
                    description={`${personLabel} loses meet accreditation and the card number is retired. Re-accrediting issues a new number.`}
                    confirmLabel="Revoke"
                    destructive
                    onConfirm={() =>
                        router.delete(destroy(accreditation.id).url, {
                            preserveScroll: true,
                        })
                    }
                />
            )}
        </div>
    );
}

export default function AccreditationIndex({
    delegation,
    athletes,
    personnel,
    accreditedCount,
    canManage,
}: Props) {
    return (
        <>
            <Head title={`Accreditation — ${delegation.school}`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Accreditation"
                    description={`${delegation.school} — ${delegation.meet} (${delegation.school_year})`}
                    actions={
                        accreditedCount > 0 && (
                            <Button variant="outline" asChild>
                                <Link href={cards(delegation.id)}>
                                    <Printer />
                                    Print ID cards
                                </Link>
                            </Button>
                        )
                    }
                />

                {!delegation.approved && (
                    <p className="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
                        This delegation is {delegation.status_label} — only
                        members of an approved delegation can be accredited.
                    </p>
                )}

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
                                        <TableHead>Name</TableHead>
                                        <TableHead>Grade / Division</TableHead>
                                        <TableHead>Eligibility</TableHead>
                                        <TableHead>Accreditation</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {athletes.map((athlete) => (
                                        <TableRow key={athlete.id}>
                                            <TableCell className="font-medium">
                                                {athlete.name}
                                            </TableCell>
                                            <TableCell>
                                                Grade {athlete.grade_level} —{' '}
                                                {athlete.division_label}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        athlete.eligibility_approved
                                                            ? 'secondary'
                                                            : 'outline'
                                                    }
                                                >
                                                    {athlete.eligibility_approved
                                                        ? 'Approved'
                                                        : 'Not approved'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {athlete.accreditation ? (
                                                    <span className="font-mono text-sm">
                                                        {
                                                            athlete
                                                                .accreditation
                                                                .number
                                                        }
                                                    </span>
                                                ) : (
                                                    <Badge variant="outline">
                                                        Not accredited
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <AccreditationActions
                                                    accreditation={
                                                        athlete.accreditation
                                                    }
                                                    canAccredit={
                                                        athlete.can_accredit
                                                    }
                                                    canManage={canManage}
                                                    onAccredit={() =>
                                                        accredit({
                                                            athlete_id:
                                                                athlete.id,
                                                        })
                                                    }
                                                    personLabel={athlete.name}
                                                />
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
                                        <TableHead>Name</TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead>Accreditation</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {personnel.map((person) => (
                                        <TableRow key={person.id}>
                                            <TableCell className="font-medium">
                                                {person.name}
                                            </TableCell>
                                            <TableCell>
                                                {person.role_label}
                                            </TableCell>
                                            <TableCell>
                                                {person.accreditation ? (
                                                    <span className="font-mono text-sm">
                                                        {
                                                            person.accreditation
                                                                .number
                                                        }
                                                    </span>
                                                ) : (
                                                    <Badge variant="outline">
                                                        Not accredited
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <AccreditationActions
                                                    accreditation={
                                                        person.accreditation
                                                    }
                                                    canAccredit={
                                                        person.can_accredit
                                                    }
                                                    canManage={canManage}
                                                    onAccredit={() =>
                                                        accredit({
                                                            personnel_id:
                                                                person.id,
                                                        })
                                                    }
                                                    personLabel={person.name}
                                                />
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

AccreditationIndex.layout = {
    breadcrumbs: [
        {
            title: 'Delegations',
            href: delegationsIndex(),
        },
    ],
};
