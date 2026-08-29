import { Head, router, useForm } from '@inertiajs/react';
import { Eye, KeyRound, Maximize2, Minus, Plus, UserCheck } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { PaginationControls } from '@/components/pagination-controls';
import type { Paginated } from '@/components/pagination-controls';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
    sport: string;
    event: string;
    team: string;
    school: string | null;
    status: string;
    review_notes: string | null;
    can_reset_password: boolean;
};
type RegistrationRow = {
    id: number;
    coach: string;
    email: string;
    team: string | null;
    school: string | null;
    sport: string;
    events: string;
    assignment_options: Array<{ id: number; label: string }>;
    status: string;
    review_notes: string | null;
    profile_url: string | null;
    profile_mime_type: string | null;
    certification_url: string | null;
    certification_mime_type: string | null;
    can_update_attachments: boolean;
    can_accredit: boolean;
    accreditation_number: string | null;
    accreditation_label: string | null;
    documents_complete: boolean;
    registered_athletes: Array<{
        id: number;
        name: string;
        school: string;
        events: string;
        profile_url: string;
    }>;
    assignment_url: string;
    can_manage_assignments: boolean;
    can_reset_password: boolean;
};
type Option = {
    meet_sport_id: number;
    event_id: number;
    delegation_id: number;
    label: string;
};
type Props = {
    registrations: Paginated<RegistrationRow>;
    requests: Paginated<RequestRow>;
    options: Option[];
    canRequest: boolean;
    canReview: boolean;
};

function CoachDocumentUpload({
    registrationId,
    type,
}: {
    registrationId: number;
    type: 'profile' | 'certification';
}) {
    return (
        <label className="inline-flex cursor-pointer items-center rounded-md border px-3 py-1.5 text-xs font-medium hover:bg-muted">
            Upload {type === 'profile' ? 'profile' : 'certification'}
            <input
                type="file"
                className="sr-only"
                accept={
                    type === 'profile'
                        ? 'image/jpeg,image/png,image/webp'
                        : '.pdf,image/jpeg,image/png'
                }
                onChange={(event) => {
                    const document = event.target.files?.[0];

                    if (!document) {
                        return;
                    }

                    router.post(
                        `/coach/onboarding-requests/${registrationId}/documents/${type}`,
                        { document },
                        { forceFormData: true, preserveScroll: true },
                    );
                    event.target.value = '';
                }}
            />
        </label>
    );
}

type ViewedDocument = {
    title: string;
    url: string;
    mimeType: string | null;
};

function ApprovalDialog({
    registration,
    close,
}: {
    registration: RegistrationRow;
    close: () => void;
}) {
    const form = useForm({
        status: 'approved',
        event_ids: [] as number[],
        review_notes: '',
    });

    return (
        <Dialog open onOpenChange={(open) => !open && close()}>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        Assign scope and approve {registration.coach}
                    </DialogTitle>
                </DialogHeader>
                <div className="space-y-3">
                    <p className="text-sm text-muted-foreground">
                        {registration.team} · {registration.school} ·{' '}
                        {registration.sport}
                    </p>
                    <Label>Active event assignments</Label>
                    <div className="max-h-72 space-y-1 overflow-y-auto rounded-md border p-3">
                        {registration.assignment_options.map((option) => (
                            <label
                                key={option.id}
                                className="flex items-center gap-3 rounded p-2 text-sm hover:bg-muted/50"
                            >
                                <Checkbox
                                    checked={form.data.event_ids.includes(
                                        option.id,
                                    )}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'event_ids',
                                            checked === true
                                                ? [
                                                      ...form.data.event_ids,
                                                      option.id,
                                                  ]
                                                : form.data.event_ids.filter(
                                                      (id) => id !== option.id,
                                                  ),
                                        )
                                    }
                                />
                                {option.label}
                            </label>
                        ))}
                    </div>
                    <InputError message={form.errors.event_ids} />
                    <Button
                        className="w-full"
                        disabled={
                            form.processing || form.data.event_ids.length === 0
                        }
                        onClick={() =>
                            form.patch(
                                `/coach/onboarding-requests/${registration.id}`,
                                { preserveScroll: true, onSuccess: close },
                            )
                        }
                    >
                        Approve with {form.data.event_ids.length} assignment
                        {form.data.event_ids.length === 1 ? '' : 's'}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}

export default function CoachAssignments({
    registrations,
    requests,
    options,
    canRequest,
    canReview,
}: Props) {
    const canAccredit = registrations.data.some((item) => item.can_accredit);
    const canManageAny = registrations.data.some(
        (item) => item.can_manage_assignments,
    );
    const [viewedDocument, setViewedDocument] = useState<ViewedDocument | null>(
        null,
    );
    const [documentZoom, setDocumentZoom] = useState(1);
    const [viewedCoach, setViewedCoach] = useState<RegistrationRow | null>(
        null,
    );
    const closeViewedCoach = () => setViewedCoach(null);
    const form = useForm({
        option: '',
        meet_sport_id: '',
        event_id: '',
        delegation_id: '',
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
        });
    };

    return (
        <>
            <Head title="Coaches" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Coaches"
                    description="Review coach accounts and their applied sports."
                />
                {registrations.data.length > 0 && (
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
                                        <TableHead>Applied Sports</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="hidden">
                                            Registered athletes
                                        </TableHead>
                                        {(canReview ||
                                            canAccredit ||
                                            canManageAny) && (
                                            <TableHead className="text-right">
                                                Manage Coach
                                            </TableHead>
                                        )}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {registrations.data.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    <button
                                                        type="button"
                                                        className="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-full border bg-muted text-sm font-semibold"
                                                        aria-label={
                                                            item.profile_url
                                                                ? `View ${item.coach}'s profile photo`
                                                                : `${item.coach} has no profile photo`
                                                        }
                                                        disabled={
                                                            !item.profile_url
                                                        }
                                                        onClick={() => {
                                                            if (
                                                                !item.profile_url
                                                            ) {
                                                                return;
                                                            }

                                                            setDocumentZoom(1);
                                                            setViewedDocument({
                                                                title: `${item.coach} — Profile photo`,
                                                                url: item.profile_url,
                                                                mimeType:
                                                                    item.profile_mime_type,
                                                            });
                                                        }}
                                                    >
                                                        {item.profile_url ? (
                                                            <img
                                                                src={
                                                                    item.profile_url
                                                                }
                                                                alt=""
                                                                className="size-full object-cover"
                                                            />
                                                        ) : (
                                                            item.coach
                                                                .split(' ')
                                                                .map(
                                                                    (name) =>
                                                                        name[0],
                                                                )
                                                                .slice(0, 2)
                                                                .join('')
                                                        )}
                                                    </button>
                                                    <div className="min-w-0">
                                                        <button
                                                            type="button"
                                                            className="font-medium hover:underline"
                                                            onClick={() =>
                                                                setViewedCoach(
                                                                    item,
                                                                )
                                                            }
                                                        >
                                                            {item.coach}
                                                        </button>
                                                        <div className="text-xs text-muted-foreground">
                                                            {item.email}
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {item.team ?? '—'}
                                            </TableCell>
                                            <TableCell className="max-w-md whitespace-normal">
                                                <div className="font-medium">
                                                    {item.sport}
                                                </div>
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
                                                {item.accreditation_label && (
                                                    <div className="mt-1 text-xs font-medium">
                                                        {
                                                            item.accreditation_label
                                                        }
                                                        {item.accreditation_number && (
                                                            <span className="block text-muted-foreground">
                                                                {
                                                                    item.accreditation_number
                                                                }
                                                            </span>
                                                        )}
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell className="hidden max-w-sm whitespace-normal">
                                                {item.registered_athletes
                                                    .length === 0 ? (
                                                    <span className="text-xs text-muted-foreground">
                                                        No athletes registered
                                                    </span>
                                                ) : (
                                                    <div className="space-y-2">
                                                        {item.registered_athletes.map(
                                                            (athlete) => (
                                                                <a
                                                                    key={
                                                                        athlete.id
                                                                    }
                                                                    href={
                                                                        athlete.profile_url
                                                                    }
                                                                    className="block rounded border p-2 text-xs hover:bg-muted/50"
                                                                >
                                                                    <span className="font-medium">
                                                                        {
                                                                            athlete.name
                                                                        }
                                                                    </span>
                                                                    <span className="block text-muted-foreground">
                                                                        {
                                                                            athlete.school
                                                                        }
                                                                        {athlete.events
                                                                            ? ` · ${athlete.events}`
                                                                            : ' · Entry pending'}
                                                                    </span>
                                                                </a>
                                                            ),
                                                        )}
                                                    </div>
                                                )}
                                            </TableCell>
                                            {(canReview ||
                                                item.can_accredit ||
                                                item.can_manage_assignments) && (
                                                <>
                                                    <TableCell className="hidden">
                                                        {item.profile_url && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => {
                                                                    setDocumentZoom(
                                                                        1,
                                                                    );
                                                                    setViewedDocument(
                                                                        {
                                                                            title: `${item.coach} — Profile photo`,
                                                                            url: item.profile_url!,
                                                                            mimeType:
                                                                                item.profile_mime_type,
                                                                        },
                                                                    );
                                                                }}
                                                            >
                                                                <Eye className="size-4" />
                                                                Profile photo
                                                            </Button>
                                                        )}
                                                        {item.certification_url && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => {
                                                                    setDocumentZoom(
                                                                        1,
                                                                    );
                                                                    setViewedDocument(
                                                                        {
                                                                            title: `${item.coach} — Coaching certification`,
                                                                            url: item.certification_url!,
                                                                            mimeType:
                                                                                item.certification_mime_type,
                                                                        },
                                                                    );
                                                                }}
                                                            >
                                                                <Eye className="size-4" />
                                                                Certification
                                                            </Button>
                                                        )}
                                                        {item.can_update_attachments && (
                                                            <>
                                                                <CoachDocumentUpload
                                                                    registrationId={
                                                                        item.id
                                                                    }
                                                                    type="profile"
                                                                />
                                                                <CoachDocumentUpload
                                                                    registrationId={
                                                                        item.id
                                                                    }
                                                                    type="certification"
                                                                />
                                                            </>
                                                        )}
                                                        {item.can_manage_assignments && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                asChild
                                                            >
                                                                <a
                                                                    href={
                                                                        item.assignment_url
                                                                    }
                                                                >
                                                                    Manage Coach
                                                                </a>
                                                            </Button>
                                                        )}
                                                        {canReview &&
                                                            item.status !==
                                                                'approved' && (
                                                                <>
                                                                    <Button
                                                                        size="sm"
                                                                        disabled={
                                                                            !item.events
                                                                        }
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
                                                                        account
                                                                    </Button>
                                                                    <Button
                                                                        size="sm"
                                                                        variant="outline"
                                                                        onClick={() =>
                                                                            router.patch(
                                                                                `/coach/onboarding-requests/${item.id}`,
                                                                                {
                                                                                    status: 'returned',
                                                                                },
                                                                                {
                                                                                    preserveScroll: true,
                                                                                },
                                                                            )
                                                                        }
                                                                    >
                                                                        Return
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
                                                                </>
                                                            )}
                                                        {item.can_reset_password && (
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
                                                                            `/coach/onboarding-requests/${item.id}/reset-password`,
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
                                                        )}
                                                        {item.can_accredit &&
                                                            !item.accreditation_number && (
                                                                <Button
                                                                    size="sm"
                                                                    variant="secondary"
                                                                    onClick={() =>
                                                                        router.post(
                                                                            `/coach/onboarding-requests/${item.id}/accredit`,
                                                                            {},
                                                                            {
                                                                                preserveScroll: true,
                                                                            },
                                                                        )
                                                                    }
                                                                >
                                                                    Accredit
                                                                    coach
                                                                </Button>
                                                            )}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                setViewedCoach(
                                                                    item,
                                                                )
                                                            }
                                                        >
                                                            <Eye className="size-4" />
                                                            View / update
                                                        </Button>
                                                    </TableCell>
                                                </>
                                            )}
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        <PaginationControls
                            page={registrations}
                            url="/coach/assignment-requests"
                            pageName="registrations_page"
                            label="coach registrations"
                        />
                    </div>
                )}
                {false && (
                    <>
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
                                                    key={`${option.event_id}-${option.delegation_id}`}
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
                                <Button
                                    disabled={
                                        form.processing || !form.data.option
                                    }
                                >
                                    Assign sports event
                                </Button>
                            </form>
                        )}
                        {requests.data.length === 0 ? (
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
                                            <TableHead>Sport / Event</TableHead>
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
                                        {requests.data.map((item) => (
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
                                                    {item.sport} / {item.event}
                                                </TableCell>
                                                <TableCell>
                                                    {item.team}
                                                    {item.school && (
                                                        <div className="text-xs text-muted-foreground">
                                                            {item.school}
                                                        </div>
                                                    )}
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
                                                        {item.can_reset_password && (
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
                                                        )}
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
                        <PaginationControls
                            page={requests}
                            url="/coach/assignment-requests"
                            pageName="requests_page"
                            label="coach assignments"
                        />
                    </>
                )}
                <Dialog
                    open={viewedCoach !== null}
                    onOpenChange={(open) => !open && setViewedCoach(null)}
                >
                    <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                        <DialogHeader>
                            <DialogTitle>
                                {viewedCoach?.coach ?? 'Coach profile'}
                            </DialogTitle>
                        </DialogHeader>
                        {viewedCoach && (
                            <div className="space-y-5">
                                <div className="grid gap-4 sm:grid-cols-[8rem_1fr]">
                                    <div className="flex size-32 items-center justify-center overflow-hidden rounded-xl border bg-muted text-xl font-semibold">
                                        {viewedCoach.profile_url ? (
                                            <img
                                                src={viewedCoach.profile_url}
                                                alt={`${viewedCoach.coach} profile`}
                                                className="size-full object-cover"
                                            />
                                        ) : (
                                            viewedCoach.coach
                                                .split(' ')
                                                .map((part) => part[0])
                                                .slice(0, 2)
                                                .join('')
                                        )}
                                    </div>
                                    <dl className="grid content-start gap-3 text-sm sm:grid-cols-2">
                                        <div>
                                            <dt className="text-muted-foreground">
                                                Email
                                            </dt>
                                            <dd className="font-medium">
                                                {viewedCoach.email}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-muted-foreground">
                                                Team / Delegation
                                            </dt>
                                            <dd className="font-medium">
                                                {viewedCoach.team ??
                                                    'Not assigned'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-muted-foreground">
                                                Sports
                                            </dt>
                                            <dd className="font-medium">
                                                {viewedCoach.sport}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-muted-foreground">
                                                Events
                                            </dt>
                                            <dd className="font-medium">
                                                {viewedCoach.events ||
                                                    'Pending assignment'}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                                <section className="space-y-3 rounded-lg border p-4">
                                    <h3 className="font-semibold">
                                        Coach documents and account
                                    </h3>
                                    <div className="flex flex-wrap gap-2">
                                        {viewedCoach.profile_url && (
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => {
                                                    closeViewedCoach();
                                                    setDocumentZoom(1);
                                                    setViewedDocument({
                                                        title: `${viewedCoach.coach} — Profile photo`,
                                                        url: viewedCoach.profile_url!,
                                                        mimeType:
                                                            viewedCoach.profile_mime_type,
                                                    });
                                                }}
                                            >
                                                <Eye className="size-4" />
                                                Profile photo
                                            </Button>
                                        )}
                                        {viewedCoach.certification_url && (
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => {
                                                    closeViewedCoach();
                                                    setDocumentZoom(1);
                                                    setViewedDocument({
                                                        title: `${viewedCoach.coach} — Coaching certification`,
                                                        url: viewedCoach.certification_url!,
                                                        mimeType:
                                                            viewedCoach.certification_mime_type,
                                                    });
                                                }}
                                            >
                                                <Eye className="size-4" />
                                                Certificate
                                            </Button>
                                        )}
                                        {viewedCoach.can_update_attachments && (
                                            <>
                                                <CoachDocumentUpload
                                                    registrationId={
                                                        viewedCoach.id
                                                    }
                                                    type="profile"
                                                />
                                                <CoachDocumentUpload
                                                    registrationId={
                                                        viewedCoach.id
                                                    }
                                                    type="certification"
                                                />
                                            </>
                                        )}
                                        {viewedCoach.can_manage_assignments && (
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                asChild
                                            >
                                                <a
                                                    href={
                                                        viewedCoach.assignment_url
                                                    }
                                                >
                                                    Manage assignments
                                                </a>
                                            </Button>
                                        )}
                                    </div>
                                    <div className="flex flex-wrap gap-2 border-t pt-3">
                                        {canReview &&
                                            viewedCoach.status !==
                                                'approved' && (
                                                <>
                                                    <Button
                                                        size="sm"
                                                        disabled={
                                                            !viewedCoach.events
                                                        }
                                                        onClick={() =>
                                                            router.patch(
                                                                `/coach/onboarding-requests/${viewedCoach.id}`,
                                                                {
                                                                    status: 'approved',
                                                                },
                                                                {
                                                                    preserveScroll: true,
                                                                    onSuccess:
                                                                        closeViewedCoach,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        Approve account
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() =>
                                                            router.patch(
                                                                `/coach/onboarding-requests/${viewedCoach.id}`,
                                                                {
                                                                    status: 'returned',
                                                                },
                                                                {
                                                                    preserveScroll: true,
                                                                    onSuccess:
                                                                        closeViewedCoach,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        Return
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="destructive"
                                                        onClick={() =>
                                                            router.patch(
                                                                `/coach/onboarding-requests/${viewedCoach.id}`,
                                                                {
                                                                    status: 'rejected',
                                                                },
                                                                {
                                                                    preserveScroll: true,
                                                                    onSuccess:
                                                                        closeViewedCoach,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        Reject
                                                    </Button>
                                                </>
                                            )}
                                        {viewedCoach.can_reset_password && (
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => {
                                                    if (
                                                        window.confirm(
                                                            `Reset ${viewedCoach.coach}'s password to DdOPaa2026!?`,
                                                        )
                                                    ) {
                                                        router.post(
                                                            `/coach/onboarding-requests/${viewedCoach.id}/reset-password`,
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
                                        )}
                                        {viewedCoach.can_accredit &&
                                            !viewedCoach.accreditation_number && (
                                                <Button
                                                    size="sm"
                                                    variant="secondary"
                                                    onClick={() =>
                                                        router.post(
                                                            `/coach/onboarding-requests/${viewedCoach.id}/accredit`,
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                                onSuccess:
                                                                    closeViewedCoach,
                                                            },
                                                        )
                                                    }
                                                >
                                                    Accredit coach
                                                </Button>
                                            )}
                                    </div>
                                </section>
                                <section className="space-y-2">
                                    <h3 className="font-semibold">
                                        Registered athletes
                                    </h3>
                                    {viewedCoach.registered_athletes.length ===
                                    0 ? (
                                        <p className="text-sm text-muted-foreground">
                                            No athletes registered.
                                        </p>
                                    ) : (
                                        <div className="grid gap-2 sm:grid-cols-2">
                                            {viewedCoach.registered_athletes.map(
                                                (athlete) => (
                                                    <a
                                                        key={athlete.id}
                                                        href={
                                                            athlete.profile_url
                                                        }
                                                        className="rounded-lg border p-3 text-sm hover:bg-muted/50"
                                                    >
                                                        <span className="font-medium">
                                                            {athlete.name}
                                                        </span>
                                                        <span className="block text-muted-foreground">
                                                            {athlete.school}
                                                            {athlete.events
                                                                ? ` · ${athlete.events}`
                                                                : ' · Entry pending'}
                                                        </span>
                                                    </a>
                                                ),
                                            )}
                                        </div>
                                    )}
                                </section>
                            </div>
                        )}
                    </DialogContent>
                </Dialog>
                <Dialog
                    open={viewedDocument !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setViewedDocument(null);
                            setDocumentZoom(1);
                        }
                    }}
                >
                    <DialogContent className="flex h-[95vh] w-[80vw] max-w-none flex-col gap-3 p-4 sm:max-w-none">
                        <DialogHeader className="shrink-0 pr-10">
                            <DialogTitle>
                                {viewedDocument?.title ?? 'Coach attachment'}
                            </DialogTitle>
                        </DialogHeader>
                        {viewedDocument &&
                            (viewedDocument.mimeType === 'application/pdf' ? (
                                <iframe
                                    src={viewedDocument.url}
                                    title={viewedDocument.title}
                                    className="min-h-0 flex-1 rounded-md border bg-white"
                                />
                            ) : (
                                <>
                                    <div className="flex shrink-0 justify-end gap-1 rounded-md border bg-muted/40 px-3 py-2">
                                        <Button
                                            type="button"
                                            size="icon"
                                            variant="outline"
                                            aria-label="Zoom out"
                                            disabled={documentZoom <= 0.5}
                                            onClick={() =>
                                                setDocumentZoom((zoom) =>
                                                    Math.max(0.5, zoom - 0.25),
                                                )
                                            }
                                        >
                                            <Minus className="size-4" />
                                        </Button>
                                        <span className="w-14 self-center text-center text-sm tabular-nums">
                                            {Math.round(documentZoom * 100)}%
                                        </span>
                                        <Button
                                            type="button"
                                            size="icon"
                                            variant="outline"
                                            aria-label="Zoom in"
                                            disabled={documentZoom >= 3}
                                            onClick={() =>
                                                setDocumentZoom((zoom) =>
                                                    Math.min(3, zoom + 0.25),
                                                )
                                            }
                                        >
                                            <Plus className="size-4" />
                                        </Button>
                                        <Button
                                            type="button"
                                            size="icon"
                                            variant="outline"
                                            aria-label="Fit attachment to window"
                                            onClick={() => setDocumentZoom(1)}
                                        >
                                            <Maximize2 className="size-4" />
                                        </Button>
                                    </div>
                                    <div className="min-h-0 flex-1 overflow-auto rounded-md border bg-black/5">
                                        <div
                                            className="relative min-h-full min-w-full"
                                            style={{
                                                width: `${documentZoom * 100}%`,
                                                height: `${documentZoom * 100}%`,
                                            }}
                                        >
                                            <img
                                                src={viewedDocument.url}
                                                alt={viewedDocument.title}
                                                className="absolute inset-0 size-full object-contain"
                                            />
                                        </div>
                                    </div>
                                </>
                            ))}
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

CoachAssignments.layout = {
    breadcrumbs: [{ title: 'Coaches', href: '/coach/assignment-requests' }],
};
