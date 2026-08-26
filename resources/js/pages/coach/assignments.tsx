import { Head, router, useForm } from '@inertiajs/react';
import { Eye, KeyRound, Maximize2, Minus, Plus, UserCheck } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
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
    documents_complete: boolean;
    registered_athletes: Array<{
        id: number;
        name: string;
        school: string;
        events: string;
        profile_url: string;
    }>;
};
type Option = {
    meet_sport_id: number;
    event_id: number;
    delegation_id: number;
    label: string;
};
type Props = {
    registrations: RegistrationRow[];
    requests: RequestRow[];
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
                    if (!document) return;
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
                    <InputError
                        message={
                            form.errors.event_ids ??
                            (form.errors as Record<string, string>).profile
                        }
                    />
                    {!registration.profile_url && (
                        <p className="text-sm text-destructive">
                            Upload a profile photo before approval.
                        </p>
                    )}
                    <Button
                        className="w-full"
                        disabled={
                            form.processing ||
                            form.data.event_ids.length === 0 ||
                            !registration.profile_url
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
    const canAccredit = registrations.some((item) => item.can_accredit);
    const [viewedDocument, setViewedDocument] = useState<ViewedDocument | null>(
        null,
    );
    const [documentZoom, setDocumentZoom] = useState(1);
    const [approving, setApproving] = useState<RegistrationRow | null>(null);
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
            <Head title="Coach Sports Events" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Coach Sports Events"
                    description="Approve coach accounts and assign or update the sports events they coach."
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
                                            Applied sport / assignments
                                        </TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Documents</TableHead>
                                        <TableHead>Registered athletes</TableHead>
                                        {(canReview || canAccredit) && (
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
                                                            )
                                                                return;
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
                                                        <div className="font-medium">
                                                            {item.coach}
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {item.email}
                                                        </div>
                                                        {item.can_update_attachments && (
                                                            <div className="mt-1">
                                                                <CoachDocumentUpload
                                                                    registrationId={
                                                                        item.id
                                                                    }
                                                                    type="profile"
                                                                />
                                                            </div>
                                                        )}
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
                                                <div className="text-xs text-muted-foreground">
                                                    {item.school ?? 'No school'}
                                                    {item.events
                                                        ? ` · Assigned: ${item.events}`
                                                        : ' · Assignment pending'}
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
                                                {item.accreditation_number && (
                                                    <div className="mt-1 text-xs font-medium">
                                                        {
                                                            item.accreditation_number
                                                        }
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell className="space-x-2 whitespace-nowrap">
                                                {!item.certification_url && (
                                                    <span className="mr-2 text-xs text-muted-foreground">
                                                        No certificate (optional)
                                                    </span>
                                                )}
                                                {item.certification_url && (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => {
                                                            setDocumentZoom(1);
                                                            setViewedDocument({
                                                                title: `${item.coach} — Coaching certification`,
                                                                url: item.certification_url!,
                                                                mimeType:
                                                                    item.certification_mime_type,
                                                            });
                                                        }}
                                                    >
                                                        <Eye className="size-4" />
                                                        Certification
                                                    </Button>
                                                )}
                                                {item.can_update_attachments && (
                                                    <CoachDocumentUpload
                                                        registrationId={item.id}
                                                        type="certification"
                                                    />
                                                )}
                                            </TableCell>
                                            <TableCell className="max-w-sm whitespace-normal">
                                                {item.registered_athletes.length === 0 ? (
                                                    <span className="text-xs text-muted-foreground">
                                                        No athletes registered
                                                    </span>
                                                ) : (
                                                    <div className="space-y-2">
                                                        {item.registered_athletes.map(
                                                            (athlete) => (
                                                                <a
                                                                    key={athlete.id}
                                                                    href={athlete.profile_url}
                                                                    className="block rounded border p-2 text-xs hover:bg-muted/50"
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
                                            </TableCell>
                                            {(canReview ||
                                                item.can_accredit) && (
                                                <TableCell className="space-x-2 text-right whitespace-nowrap">
                                                    {canReview && (
                                                        <>
                                                            <Button
                                                                size="sm"
                                                                onClick={() =>
                                                                    setApproving(
                                                                        item,
                                                                    )
                                                                }
                                                            >
                                                                {item.status ===
                                                                'approved'
                                                                    ? 'Manage assignments'
                                                                    : 'Approve'}
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
                                                    {canReview && (
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
                                                                Accredit coach
                                                            </Button>
                                                        )}
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
                        <Button disabled={form.processing || !form.data.option}>
                            Assign sports event
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
            {approving && (
                <ApprovalDialog
                    registration={approving}
                    close={() => setApproving(null)}
                />
            )}
        </>
    );
}

CoachAssignments.layout = {
    breadcrumbs: [
        { title: 'Coach Sports Events', href: '/coach/assignment-requests' },
    ],
};
