import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, FileText, Send } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';

type DocumentRow = {
    id: number;
    label: string;
    file_name: string;
    url: string;
    status: string;
    status_label: string;
    remarks: string | null;
    verified_by: string | null;
    can_verify: boolean;
};
type Review = {
    id: number;
    status: string;
    status_label: string;
    requirements_validated: boolean;
    requirements_summary: string;
    documents: DocumentRow[];
    can_decide: boolean;
    can_notify: boolean;
    remarks: string | null;
};
type Athlete = {
    id: number;
    name: string;
    photo_url: string | null;
    sex: string;
    birthdate: string;
    age: number;
    grade_level: number;
    lrn: string;
    school: string;
    district: string | null;
    delegation: string;
    sports: string;
};

export default function EligibilityReviewPage({
    review,
    athlete,
}: {
    review: Review;
    athlete: Athlete;
}) {
    const notification = useForm({ remarks: review.remarks ?? '' });
    const updateDocument = (
        document: DocumentRow,
        status: 'verified' | 'under_review' | 'rejected',
    ) =>
        router.patch(
            `/eligibility/documents/${document.id}/status`,
            { status },
            { preserveScroll: true },
        );
    const decide = (action: 'approve' | 'return' | 'reject') =>
        router.patch(
            `/eligibility/reviews/${review.id}/${action}`,
            { remarks: notification.data.remarks },
            { preserveScroll: true },
        );

    return (
        <div className="space-y-6 p-4 sm:p-6">
            <Head title={`${athlete.name} Eligibility`} />
            <div className="flex flex-wrap items-center justify-between gap-3">
                <Button variant="outline" asChild>
                    <Link href="/eligibility">
                        <ArrowLeft />
                        Back to eligibility
                    </Link>
                </Button>
                {review.requirements_validated && (
                    <div className="flex items-center gap-3 rounded-2xl border-2 border-emerald-500 bg-emerald-50 px-6 py-3 text-xl font-black tracking-wide text-emerald-700">
                        <CheckCircle2 className="size-7" />
                        ELIGIBLE
                    </div>
                )}
            </div>

            <section className="grid gap-6 rounded-2xl border p-5 md:grid-cols-[11rem_1fr]">
                <div className="flex aspect-[4/5] w-44 items-center justify-center overflow-hidden rounded-xl border bg-muted text-4xl font-bold">
                    {athlete.photo_url ? (
                        <img
                            src={athlete.photo_url}
                            alt={`${athlete.name} profile`}
                            className="size-full object-cover"
                        />
                    ) : (
                        athlete.name.charAt(0)
                    )}
                </div>
                <div>
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-2xl font-bold">{athlete.name}</h1>
                        <Badge variant="outline">{review.status_label}</Badge>
                    </div>
                    <dl className="mt-5 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <dt className="text-muted-foreground">School</dt>
                            <dd className="font-medium">{athlete.school}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">District</dt>
                            <dd className="font-medium">
                                {athlete.district ?? 'Not assigned'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">
                                Delegation
                            </dt>
                            <dd className="font-medium">
                                {athlete.delegation}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Sex / Age</dt>
                            <dd className="font-medium">
                                {athlete.sex} · {athlete.age}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">
                                Birthdate / Grade
                            </dt>
                            <dd className="font-medium">
                                {athlete.birthdate} · Grade{' '}
                                {athlete.grade_level}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">LRN</dt>
                            <dd className="font-medium">{athlete.lrn}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Sports</dt>
                            <dd className="font-medium">
                                {athlete.sports || 'Not assigned'}
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section className="space-y-4">
                <div>
                    <h2 className="text-lg font-semibold">
                        Attached documents
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {review.requirements_summary}
                    </p>
                </div>
                {review.documents.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-8 text-center text-muted-foreground">
                        No documents attached.
                    </div>
                ) : (
                    <div className="grid gap-3 lg:grid-cols-2">
                        {review.documents.map((document) => (
                            <article
                                key={document.id}
                                className="rounded-xl border p-4"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="flex gap-2">
                                        <FileText className="size-5" />
                                        <div>
                                            <a
                                                href={document.url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="font-semibold underline"
                                            >
                                                {document.label}
                                            </a>
                                            <p className="text-xs text-muted-foreground">
                                                {document.file_name}
                                            </p>
                                        </div>
                                    </div>
                                    <Badge variant="outline">
                                        {document.status_label}
                                    </Badge>
                                </div>
                                {(document.verified_by || document.remarks) && (
                                    <p className="mt-3 text-xs text-muted-foreground">
                                        {document.verified_by
                                            ? `Checked by ${document.verified_by}`
                                            : ''}
                                        {document.remarks
                                            ? ` · ${document.remarks}`
                                            : ''}
                                    </p>
                                )}
                                {document.can_verify && (
                                    <div className="mt-4 flex flex-wrap gap-2">
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
                                            Under review
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
                                            Reject
                                        </Button>
                                    </div>
                                )}
                            </article>
                        ))}
                    </div>
                )}
            </section>

            {review.can_notify && (
                <section className="space-y-3 rounded-xl border p-5">
                    <div>
                        <h2 className="font-semibold">Remarks to Coach</h2>
                        <p className="text-sm text-muted-foreground">
                            Add a short optional note about missing, rejected,
                            or validated requirements.
                        </p>
                    </div>
                    <Textarea
                        maxLength={500}
                        rows={3}
                        value={notification.data.remarks}
                        onChange={(event) =>
                            notification.setData('remarks', event.target.value)
                        }
                        placeholder="Optional remarks for the coach"
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button
                            onClick={() =>
                                notification.post(
                                    `/eligibility/reviews/${review.id}/notify-coach`,
                                    { preserveScroll: true },
                                )
                            }
                            disabled={notification.processing}
                        >
                            <Send />
                            Send notification to Coach
                        </Button>
                        {review.can_decide && (
                            <>
                                <Button
                                    variant="outline"
                                    onClick={() => decide('return')}
                                >
                                    Return
                                </Button>
                                <Button
                                    variant="destructive"
                                    onClick={() => decide('reject')}
                                >
                                    Reject
                                </Button>
                                <Button
                                    onClick={() => decide('approve')}
                                    disabled={!review.requirements_validated}
                                >
                                    Approve eligibility
                                </Button>
                            </>
                        )}
                    </div>
                </section>
            )}
        </div>
    );
}
