import { Head, Link, router, useForm } from '@inertiajs/react';
import { FileCheck, FileText, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { PaginationControls } from '@/components/pagination-controls';
import type { Paginated } from '@/components/pagination-controls';
import { SearchBar } from '@/components/search-bar';
import { StatCard } from '@/components/stat-card';
import type { StatCardTone } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
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
import { approve, index, reject, returnMethod } from '@/routes/eligibility';
import {
    destroy as destroyDocument,
    store as storeDocument,
} from '@/routes/eligibility/documents';

type DocumentRow = {
    id: number;
    label: string;
    file_name: string;
    uploaded_at: string | null;
    url: string;
    can_delete: boolean;
    status: string;
    status_label: string;
    remarks: string | null;
    verified_by: string | null;
    verified_at: string | null;
    can_verify: boolean;
};

type ReviewRow = {
    id: number;
    athlete_id: number;
    athlete: string;
    school: string;
    meet: string;
    status: string;
    status_label: string;
    remarks: string | null;
    reviewer: string | null;
    decided_at: string | null;
    documents: DocumentRow[];
    requirement_checklist: Array<{
        type: string;
        label: string;
        status: string;
        status_label: string;
    }>;
    requirements_validated: boolean;
    requirements_summary: string;
    can_review: boolean;
    can_decide: boolean;
    can_accredit: boolean;
};

function ReviewDialog({
    review,
    open,
    onOpenChange,
    onDecision,
}: {
    review: ReviewRow;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onDecision: (mode: 'approve' | 'return' | 'reject') => void;
}) {
    const updateDocument = (
        document: DocumentRow,
        status: 'verified' | 'rejected' | 'under_review',
    ) => {
        router.patch(
            `/eligibility/documents/${document.id}/status`,
            { status },
            { preserveScroll: true },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Review athlete requirements</DialogTitle>
                </DialogHeader>
                <div className="space-y-1">
                    <p className="font-medium">{review.athlete}</p>
                    <p className="text-sm text-muted-foreground">
                        {review.school} · {review.meet}
                    </p>
                    <Badge
                        variant={
                            review.requirements_validated
                                ? 'outline'
                                : 'destructive'
                        }
                        className={
                            review.requirements_validated
                                ? statusBadgeClasses.approved
                                : undefined
                        }
                    >
                        {review.requirements_summary}
                    </Badge>
                </div>

                <div className="space-y-3">
                    <div className="grid gap-2 sm:grid-cols-2">
                        {review.requirement_checklist.map((requirement) => (
                            <div
                                key={requirement.type}
                                className="flex items-center justify-between gap-3 rounded-lg border p-3 text-sm"
                            >
                                <span className="font-medium">
                                    {requirement.label}
                                </span>
                                <Badge
                                    variant={
                                        requirement.status === 'verified'
                                            ? 'outline'
                                            : 'destructive'
                                    }
                                >
                                    {requirement.status_label}
                                </Badge>
                            </div>
                        ))}
                    </div>
                    {review.documents.length === 0 ? (
                        <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                            No requirements have been uploaded.
                        </p>
                    ) : (
                        review.documents.map((document) => (
                            <div
                                key={document.id}
                                className="rounded-lg border p-3"
                            >
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <a
                                            href={document.url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="font-medium underline underline-offset-2"
                                        >
                                            {document.label}
                                        </a>
                                        <p className="text-xs text-muted-foreground">
                                            {document.file_name}
                                        </p>
                                    </div>
                                    <Badge variant="outline">
                                        {document.status_label}
                                    </Badge>
                                </div>
                                {(document.verified_by || document.remarks) && (
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        {document.verified_by
                                            ? `Checked by ${document.verified_by}${document.verified_at ? ` ${document.verified_at}` : ''}`
                                            : ''}
                                        {document.remarks
                                            ? ` · ${document.remarks}`
                                            : ''}
                                    </p>
                                )}
                                {document.can_verify && (
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        <Button
                                            size="sm"
                                            onClick={() =>
                                                updateDocument(
                                                    document,
                                                    'verified',
                                                )
                                            }
                                        >
                                            Validate
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() =>
                                                updateDocument(
                                                    document,
                                                    'under_review',
                                                )
                                            }
                                        >
                                            Mark under review
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            onClick={() =>
                                                updateDocument(
                                                    document,
                                                    'rejected',
                                                )
                                            }
                                        >
                                            Reject requirement
                                        </Button>
                                    </div>
                                )}
                            </div>
                        ))
                    )}
                </div>

                {review.can_decide && (
                    <DialogFooter className="flex-wrap">
                        <Button
                            variant="outline"
                            onClick={() => onDecision('return')}
                        >
                            Return
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => onDecision('reject')}
                        >
                            Reject
                        </Button>
                        <Button
                            disabled={!review.requirements_validated}
                            onClick={() => onDecision('approve')}
                        >
                            Mark athlete qualified
                        </Button>
                    </DialogFooter>
                )}
            </DialogContent>
        </Dialog>
    );
}

function AccreditDialog({
    review,
    open,
    onOpenChange,
}: {
    review: ReviewRow;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [processing, setProcessing] = useState(false);

    const accredit = () => {
        setProcessing(true);
        router.post(
            '/accreditations',
            { athlete_id: review.athlete_id },
            {
                preserveScroll: true,
                onSuccess: () => onOpenChange(false),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Accredit athlete</DialogTitle>
                </DialogHeader>
                <div className="space-y-3">
                    <div>
                        <p className="font-medium">{review.athlete}</p>
                        <p className="text-sm text-muted-foreground">
                            {review.school} · {review.meet}
                        </p>
                    </div>
                    <div className="rounded-lg border p-3 text-sm">
                        <p>{review.requirements_summary}</p>
                        <p>Final DSAC review: {review.status_label}</p>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        Accreditation confirms the athlete and any submitted
                        competition entries. This action is audited.
                    </p>
                </div>
                <DialogFooter>
                    <Button onClick={accredit} disabled={processing}>
                        Accredit athlete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

type Counts = {
    pending: number;
    approved: number;
    returned: number;
    rejected: number;
};

type Props = {
    reviews: Paginated<ReviewRow>;
    counts: Counts;
    filters: { status: string | null; search: string | null };
    athleteOptions: Array<{ id: number; label: string }>;
    documentTypeOptions: Array<{ value: string; label: string }>;
};

const statusBadgeClasses: Record<string, string> = {
    pending: 'border-warning/30 bg-warning/10 text-warning',
    approved: 'border-success/30 bg-success/10 text-success',
};

const destructiveStatuses = new Set(['returned', 'rejected']);

function UploadDialog({
    athleteOptions,
    documentTypeOptions,
    open,
    onOpenChange,
}: {
    athleteOptions: Array<{ id: number; label: string }>;
    documentTypeOptions: Array<{ value: string; label: string }>;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        athlete_id: string;
        document_type: string;
        file: File | null;
    }>({
        athlete_id: '',
        document_type: '',
        file: null,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeDocument().url, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Upload eligibility document</DialogTitle>
                </DialogHeader>
                {athleteOptions.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No athletes are available for document upload right now.
                    </p>
                ) : (
                    <form onSubmit={submit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="eligibility-athlete">Athlete</Label>
                            <Select
                                value={data.athlete_id}
                                onValueChange={(value) =>
                                    setData('athlete_id', value)
                                }
                            >
                                <SelectTrigger id="eligibility-athlete">
                                    <SelectValue placeholder="Select an athlete" />
                                </SelectTrigger>
                                <SelectContent>
                                    {athleteOptions.map((athlete) => (
                                        <SelectItem
                                            key={athlete.id}
                                            value={String(athlete.id)}
                                        >
                                            {athlete.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.athlete_id} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="eligibility-type">
                                Document type
                            </Label>
                            <Select
                                value={data.document_type}
                                onValueChange={(value) =>
                                    setData('document_type', value)
                                }
                            >
                                <SelectTrigger id="eligibility-type">
                                    <SelectValue placeholder="Select a type" />
                                </SelectTrigger>
                                <SelectContent>
                                    {documentTypeOptions.map((type) => (
                                        <SelectItem
                                            key={type.value}
                                            value={type.value}
                                        >
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.document_type} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="eligibility-file">
                                File (PDF or image, max 10 MB)
                            </Label>
                            <Input
                                id="eligibility-file"
                                type="file"
                                accept=".pdf,image/*"
                                onChange={(e) =>
                                    setData('file', e.target.files?.[0] ?? null)
                                }
                            />
                            <InputError message={errors.file} />
                        </div>
                        <DialogFooter>
                            <Button type="submit" disabled={processing}>
                                Upload
                            </Button>
                        </DialogFooter>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}

function DecisionDialog({
    review,
    mode,
    open,
    onOpenChange,
}: {
    review: ReviewRow;
    mode: 'approve' | 'return' | 'reject';
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [remarks, setRemarks] = useState('');
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const remarksRequired = mode === 'return' || mode === 'reject';

    const save = () => {
        if (remarksRequired && remarks.trim() === '') {
            setError(
                mode === 'return'
                    ? 'Remarks are required when returning a review.'
                    : 'A reason is required when rejecting a review.',
            );

            return;
        }

        setProcessing(true);
        const url = {
            approve: approve(review.id).url,
            return: returnMethod(review.id).url,
            reject: reject(review.id).url,
        }[mode];

        router.patch(
            url,
            { remarks },
            {
                preserveScroll: true,
                onSuccess: () => onOpenChange(false),
                onFinish: () => setProcessing(false),
            },
        );
    };

    const title = {
        approve: `Approve eligibility for ${review.athlete}?`,
        return: `Return ${review.athlete}'s documents?`,
        reject: `Reject eligibility for ${review.athlete}?`,
    }[mode];

    const confirmLabel = {
        approve: 'Approve',
        return: 'Return for correction',
        reject: 'Reject',
    }[mode];

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                </DialogHeader>
                <div className="space-y-2">
                    <Label htmlFor="decision-remarks">
                        Remarks {remarksRequired ? '(required)' : '(optional)'}
                    </Label>
                    <Input
                        id="decision-remarks"
                        value={remarks}
                        onChange={(e) => {
                            setRemarks(e.target.value);
                            setError(null);
                        }}
                        placeholder={
                            mode === 'return'
                                ? 'What must be corrected?'
                                : mode === 'reject'
                                  ? 'Why is this being rejected?'
                                  : 'Optional note'
                        }
                    />
                    <InputError message={error ?? undefined} />
                </div>
                <DialogFooter>
                    <Button
                        onClick={save}
                        disabled={processing}
                        variant={mode === 'approve' ? 'default' : 'destructive'}
                    >
                        {confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function Eligibility({
    reviews,
    counts,
    filters,
    athleteOptions,
    documentTypeOptions,
}: Props) {
    const [uploadOpen, setUploadOpen] = useState(false);
    const [decision, setDecision] = useState<{
        review: ReviewRow;
        mode: 'approve' | 'return' | 'reject';
    } | null>(null);
    const [reviewing, setReviewing] = useState<ReviewRow | null>(null);
    const [accrediting, setAccrediting] = useState<ReviewRow | null>(null);

    const applyStatusFilter = (value: string) => {
        router.get(
            index().url,
            {
                ...(filters.search ? { search: filters.search } : {}),
                ...(value === 'all' ? {} : { status: value }),
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const countCards: Array<{
        label: string;
        value: number;
        tone?: StatCardTone;
    }> = [
        { label: 'Pending review', value: counts.pending, tone: 'warning' },
        { label: 'Approved', value: counts.approved, tone: 'success' },
        { label: 'Returned', value: counts.returned },
        { label: 'Rejected', value: counts.rejected, tone: 'destructive' },
    ];

    return (
        <>
            <Head title="Eligibility" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Eligibility Review"
                    description="Document submission and manual review. Decisions are always made by a person."
                    actions={
                        athleteOptions.length > 0 && (
                            <Button onClick={() => setUploadOpen(true)}>
                                <Plus aria-hidden="true" />
                                Upload document
                            </Button>
                        )
                    }
                />

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {countCards.map((card) => (
                        <StatCard
                            key={card.label}
                            label={card.label}
                            value={card.value}
                            icon={FileCheck}
                            tone={card.tone}
                        />
                    ))}
                </div>

                <div className="flex flex-wrap gap-2">
                    <SearchBar
                        initial={filters.search ?? ''}
                        placeholder="Search athlete name"
                        url={index().url}
                        extraParams={
                            filters.status ? { status: filters.status } : {}
                        }
                    />
                    <Select
                        value={filters.status ?? 'all'}
                        onValueChange={applyStatusFilter}
                    >
                        <SelectTrigger
                            className="w-56"
                            aria-label="Filter by status"
                        >
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            <SelectItem value="pending">
                                Pending Review
                            </SelectItem>
                            <SelectItem value="approved">Approved</SelectItem>
                            <SelectItem value="returned">Returned</SelectItem>
                            <SelectItem value="rejected">Rejected</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {reviews.data.length === 0 ? (
                    <EmptyState
                        icon={FileCheck}
                        title="No eligibility records"
                        description={
                            filters.search
                                ? 'No records match your search.'
                                : 'Uploaded documents create a review record per athlete.'
                        }
                    />
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-xl border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Athlete</TableHead>
                                        <TableHead>School</TableHead>
                                        <TableHead>Documents</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Remarks</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {reviews.data.map((review) => (
                                        <TableRow key={review.id}>
                                            <TableCell className="font-medium">
                                                {review.athlete}
                                            </TableCell>
                                            <TableCell>
                                                {review.school}
                                            </TableCell>
                                            <TableCell>
                                                {review.documents.length ===
                                                0 ? (
                                                    '—'
                                                ) : (
                                                    <ul className="space-y-1.5">
                                                        {review.documents.map(
                                                            (doc) => (
                                                                <li
                                                                    key={doc.id}
                                                                    className="flex items-center gap-1.5"
                                                                >
                                                                    <FileText
                                                                        aria-hidden="true"
                                                                        className="size-3.5 shrink-0 text-muted-foreground"
                                                                    />
                                                                    <a
                                                                        href={
                                                                            doc.url
                                                                        }
                                                                        target="_blank"
                                                                        rel="noreferrer"
                                                                        className="text-sm underline underline-offset-2"
                                                                    >
                                                                        {
                                                                            doc.label
                                                                        }
                                                                    </a>
                                                                    {doc.uploaded_at && (
                                                                        <span className="text-xs text-muted-foreground">
                                                                            (
                                                                            {
                                                                                doc.uploaded_at
                                                                            }
                                                                            )
                                                                        </span>
                                                                    )}
                                                                    {doc.can_delete && (
                                                                        <ConfirmDialog
                                                                            trigger={
                                                                                <Button
                                                                                    variant="ghost"
                                                                                    size="icon"
                                                                                    className="size-6"
                                                                                    aria-label={`Remove ${doc.label}`}
                                                                                >
                                                                                    <Trash2 className="size-3" />
                                                                                </Button>
                                                                            }
                                                                            title="Remove document?"
                                                                            description={`${doc.label} — ${doc.file_name}`}
                                                                            confirmLabel="Remove"
                                                                            destructive
                                                                            onConfirm={() =>
                                                                                router.delete(
                                                                                    destroyDocument(
                                                                                        doc.id,
                                                                                    )
                                                                                        .url,
                                                                                    {
                                                                                        preserveScroll: true,
                                                                                    },
                                                                                )
                                                                            }
                                                                        />
                                                                    )}
                                                                </li>
                                                            ),
                                                        )}
                                                    </ul>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        destructiveStatuses.has(
                                                            review.status,
                                                        )
                                                            ? 'destructive'
                                                            : 'outline'
                                                    }
                                                    className={
                                                        statusBadgeClasses[
                                                            review.status
                                                        ]
                                                    }
                                                >
                                                    {review.status_label}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="max-w-48 truncate text-sm text-muted-foreground">
                                                {review.remarks ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {review.can_review && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link href={`/eligibility/reviews/${review.id}`}>Open athlete</Link>
                                                    </Button>
                                                )}
                                                {review.can_decide && (
                                                    <span className="ml-2 text-xs text-muted-foreground">
                                                        Ready for final review
                                                    </span>
                                                )}
                                                {review.can_accredit && (
                                                    <Button
                                                        size="sm"
                                                        className="ml-2"
                                                        onClick={() =>
                                                            setAccrediting(
                                                                review,
                                                            )
                                                        }
                                                    >
                                                        Accredit
                                                    </Button>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <PaginationControls
                            page={reviews}
                            url={index().url}
                            label="records"
                            params={{
                                ...(filters.status
                                    ? { status: filters.status }
                                    : {}),
                                ...(filters.search
                                    ? { search: filters.search }
                                    : {}),
                            }}
                        />
                    </>
                )}
            </div>

            <UploadDialog
                athleteOptions={athleteOptions}
                documentTypeOptions={documentTypeOptions}
                open={uploadOpen}
                onOpenChange={setUploadOpen}
            />

            {decision && (
                <DecisionDialog
                    key={`${decision.review.id}-${decision.mode}`}
                    review={decision.review}
                    mode={decision.mode}
                    open={decision !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setDecision(null);
                        }
                    }}
                />
            )}

            {reviewing && (
                <ReviewDialog
                    key={reviewing.id}
                    review={reviewing}
                    open={reviewing !== null}
                    onOpenChange={(open) => {
                        if (!open) setReviewing(null);
                    }}
                    onDecision={(mode) => {
                        setDecision({ review: reviewing, mode });
                        setReviewing(null);
                    }}
                />
            )}

            {accrediting && (
                <AccreditDialog
                    key={accrediting.id}
                    review={accrediting}
                    open={accrediting !== null}
                    onOpenChange={(open) => {
                        if (!open) setAccrediting(null);
                    }}
                />
            )}
        </>
    );
}

Eligibility.layout = {
    breadcrumbs: [
        {
            title: 'Eligibility',
            href: index(),
        },
    ],
};
