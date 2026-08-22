import { Head, router, useForm } from '@inertiajs/react';
import { KeyRound, UserCheck } from 'lucide-react';
import type { FormEvent } from 'react';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
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

type RequestRow = {
    id: number;
    coach: string;
    email: string;
    meet: string;
    sport: string;
    event: string;
    team: string;
    school: string;
    status: string;
    review_notes: string | null;
};
type RegistrationRow = {
    id: number;
    coach: string;
    email: string;
    team: string | null;
    events: string;
    status: string;
    review_notes: string | null;
};
type Option = {
    meet_sport_id: number;
    event_id: number;
    delegation_id: number;
    school_id: number;
    label: string;
};
type Props = {
    registrations: RegistrationRow[];
    requests: RequestRow[];
    options: Option[];
    canRequest: boolean;
    canReview: boolean;
};

export default function CoachAssignments({
    registrations,
    requests,
    options,
    canRequest,
    canReview,
}: Props) {
    const form = useForm({
        option: '',
        meet_sport_id: '',
        event_id: '',
        delegation_id: '',
        school_id: '',
    });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/coach/assignment-requests', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };
    const selectOption = (value: string) => {
        const option = options[Number(value)];
        form.setData({
            option: value,
            meet_sport_id: String(option.meet_sport_id),
            event_id: String(option.event_id),
            delegation_id: String(option.delegation_id),
            school_id: String(option.school_id),
        });
    };

    return (
        <>
            <Head title="Coach Enrollments" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Coach Registration"
                    description="Approve coach accounts, reset passwords, and manage event enrollment requests."
                />
                {registrations.length > 0 && (
                    <div className="space-y-2">
                        <h2 className="text-lg font-semibold">
                            Account registrations
                        </h2>
                        <div className="overflow-x-auto rounded-xl border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Coach</TableHead>
                                        <TableHead>Team</TableHead>
                                        <TableHead>
                                            Requested sports/events
                                        </TableHead>
                                        <TableHead>Status</TableHead>
                                        {canReview && (
                                            <TableHead className="text-right">
                                                Actions
                                            </TableHead>
                                        )}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {registrations.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell>
                                                <div className="font-medium">
                                                    {item.coach}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {item.email}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {item.team ?? '—'}
                                            </TableCell>
                                            <TableCell className="max-w-md whitespace-normal">
                                                {item.events}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        item.status ===
                                                        'approved'
                                                            ? 'secondary'
                                                            : 'outline'
                                                    }
                                                >
                                                    {item.status}
                                                </Badge>
                                            </TableCell>
                                            {canReview && (
                                                <TableCell className="space-x-2 text-right whitespace-nowrap">
                                                    <Button
                                                        size="sm"
                                                        onClick={() =>
                                                            router.patch(
                                                                `/coach/onboarding-requests/${item.id}`,
                                                                {
                                                                    status: 'approved',
                                                                },
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        Approve
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="destructive"
                                                        onClick={() =>
                                                            router.patch(
                                                                `/coach/onboarding-requests/${item.id}`,
                                                                {
                                                                    status: 'rejected',
                                                                },
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        Reject
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => {
                                                            if (
                                                                window.confirm(
                                                                    `Reset ${item.coach}'s password to DdOPaa2026!?`,
                                                                )
                                                            )
                                                                router.post(
                                                                    `/coach/onboarding-requests/${item.id}/reset-password`,
                                                                    {},
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                );
                                                        }}
                                                    >
                                                        <KeyRound className="size-4" />
                                                        Reset password
                                                    </Button>
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                )}
                {canRequest && (
                    <form
                        onSubmit={submit}
                        className="flex max-w-3xl items-end gap-3 rounded-xl border p-4"
                    >
                        <div className="flex-1 space-y-2">
                            <Label>Sports event and team</Label>
                            <Select
                                value={form.data.option}
                                onValueChange={selectOption}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select event and team" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.map((option, index) => (
                                        <SelectItem
                                            key={`${option.event_id}-${option.delegation_id}-${option.school_id}`}
                                            value={String(index)}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError
                                message={
                                    form.errors.event_id ??
                                    form.errors.delegation_id
                                }
                            />
                        </div>
                        <Button disabled={form.processing || !form.data.option}>
                            Submit for approval
                        </Button>
                    </form>
                )}
                {requests.length === 0 ? (
                    <EmptyState
                        icon={UserCheck}
                        title="No event enrollment requests"
                        description="Enrollment requests will appear here."
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Coach</TableHead>
                                    <TableHead>Meet / Sport / Event</TableHead>
                                    <TableHead>Team</TableHead>
                                    <TableHead>Status</TableHead>
                                    {canReview && (
                                        <TableHead className="text-right">
                                            Review
                                        </TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {requests.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell>
                                            <div className="font-medium">
                                                {item.coach}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {item.email}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {item.meet} — {item.sport} /{' '}
                                            {item.event}
                                        </TableCell>
                                        <TableCell>
                                            {item.team}
                                            <div className="text-xs text-muted-foreground">
                                                {item.school}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    item.status === 'approved'
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {item.status}
                                            </Badge>
                                        </TableCell>
                                        {canReview && (
                                            <TableCell className="space-x-2 text-right whitespace-nowrap">
                                                <Button
                                                    size="sm"
                                                    onClick={() =>
                                                        router.patch(
                                                            `/coach/assignment-requests/${item.id}`,
                                                            {
                                                                status: 'approved',
                                                            },
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    Approve
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                    onClick={() =>
                                                        router.patch(
                                                            `/coach/assignment-requests/${item.id}`,
                                                            {
                                                                status: 'rejected',
                                                            },
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    Reject
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => {
                                                        if (
                                                            window.confirm(
                                                                `Reset ${item.coach}'s password to DdOPaa2026!?`,
                                                            )
                                                        ) {
                                                            router.post(
                                                                `/coach/assignment-requests/${item.id}/reset-password`,
                                                                {},
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            );
                                                        }
                                                    }}
                                                >
                                                    <KeyRound className="size-4" />
                                                    Reset password
                                                </Button>
                                            </TableCell>
                                        )}
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

CoachAssignments.layout = {
    breadcrumbs: [{ title: 'Coach', href: '/coach/assignment-requests' }],
};
