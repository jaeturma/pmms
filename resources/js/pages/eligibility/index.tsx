import { Head, router, useForm } from '@inertiajs/react';
import { FileCheck, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { PaginationControls } from '@/components/pagination-controls';
import type { Paginated } from '@/components/pagination-controls';
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
import { approve, index, returnMethod } from '@/routes/eligibility';
import {
    destroy as destroyDocument,
    store as storeDocument,
} from '@/routes/eligibility/documents';

type DocumentRow = {
    id: number;
    label: string;
    file_name: string;
    url: string;
    can_delete: boolean;
};

type ReviewRow = {
    id: number;
    athlete: string;
    school: string;
    meet: string;
    status: string;
    status_label: string;
    remarks: string | null;
    reviewer: string | null;
    decided_at: string | null;
    documents: DocumentRow[];
    can_decide: boolean;
};

type Props = {
    reviews: Paginated<ReviewRow>;
    filters: { status: string | null };
    athleteOptions: Array<{ id: number; label: string }>;
    documentTypeOptions: Array<{ value: string; label: string }>;
};

const statusVariants: Record<string, 'default' | 'secondary' | 'outline'> = {
    pending: 'default',
    approved: 'secondary',
    returned: 'outline',
};

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
    mode: 'approve' | 'return';
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [remarks, setRemarks] = useState('');
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const save = () => {
        if (mode === 'return' && remarks.trim() === '') {
            setError('Remarks are required when returning a review.');

            return;
        }

        setProcessing(true);
        router.patch(
            mode === 'approve'
                ? approve(review.id).url
                : returnMethod(review.id).url,
            { remarks },
            {
                preserveScroll: true,
                onSuccess: () => onOpenChange(false),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {mode === 'approve'
                            ? `Approve eligibility for ${review.athlete}?`
                            : `Return ${review.athlete}'s documents?`}
                    </DialogTitle>
                </DialogHeader>
                <div className="space-y-2">
                    <Label htmlFor="decision-remarks">
                        Remarks{' '}
                        {mode === 'return' ? '(required)' : '(optional)'}
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
                                : 'Optional note'
                        }
                    />
                    <InputError message={error ?? undefined} />
                </div>
                <DialogFooter>
                    <Button
                        onClick={save}
                        disabled={processing}
                        variant={mode === 'return' ? 'destructive' : 'default'}
                    >
                        {mode === 'approve'
                            ? 'Approve'
                            : 'Return for correction'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function Eligibility({
    reviews,
    filters,
    athleteOptions,
    documentTypeOptions,
}: Props) {
    const [uploadOpen, setUploadOpen] = useState(false);
    const [decision, setDecision] = useState<{
        review: ReviewRow;
        mode: 'approve' | 'return';
    } | null>(null);

    const applyStatusFilter = (value: string) => {
        router.get(index().url, value === 'all' ? {} : { status: value }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Eligibility" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Eligibility"
                    description="Document submission and manual review. Decisions are always made by a person."
                    actions={
                        athleteOptions.length > 0 && (
                            <Button onClick={() => setUploadOpen(true)}>
                                <Plus />
                                Upload document
                            </Button>
                        )
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
                        <SelectItem value="pending">Pending Review</SelectItem>
                        <SelectItem value="approved">Approved</SelectItem>
                        <SelectItem value="returned">Returned</SelectItem>
                    </SelectContent>
                </Select>

                {reviews.data.length === 0 ? (
                    <EmptyState
                        icon={FileCheck}
                        title="No eligibility records"
                        description="Uploaded documents create a review record per athlete."
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
                                                    <ul className="space-y-1">
                                                        {review.documents.map(
                                                            (doc) => (
                                                                <li
                                                                    key={doc.id}
                                                                    className="flex items-center gap-1"
                                                                >
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
                                                        statusVariants[
                                                            review.status
                                                        ] ?? 'outline'
                                                    }
                                                >
                                                    {review.status_label}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="max-w-48 truncate text-sm text-muted-foreground">
                                                {review.remarks ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {review.can_decide && (
                                                    <div className="flex justify-end gap-2">
                                                        <Button
                                                            size="sm"
                                                            onClick={() =>
                                                                setDecision({
                                                                    review,
                                                                    mode: 'approve',
                                                                })
                                                            }
                                                        >
                                                            Approve
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                setDecision({
                                                                    review,
                                                                    mode: 'return',
                                                                })
                                                            }
                                                        >
                                                            Return
                                                        </Button>
                                                    </div>
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
                            params={
                                filters.status ? { status: filters.status } : {}
                            }
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
