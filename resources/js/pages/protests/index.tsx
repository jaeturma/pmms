import { Head, router, useForm } from '@inertiajs/react';
import { Gavel, Plus } from 'lucide-react';
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
import { decide, index, review, store } from '@/routes/protests';
import { correct } from '@/routes/results';

type Protest = {
    id: number;
    delegation: string;
    target: string;
    grounds: string;
    status: string;
    status_label: string;
    filed_by: string | null;
    filed_at: string | null;
    decided_by: string | null;
    decided_at: string | null;
    remarks: string | null;
    can_review: boolean;
    can_decide: boolean;
    correctable_result_id: number | null;
    correction_reason: string;
};

type StatusOption = { value: string; label: string };

type DelegationOption = { id: number; meet_id: number; label: string };

type TargetOption = { id: number; meet_id: number; label: string };

type Props = {
    protests: Paginated<Protest>;
    filters: { status: string | null };
    statusOptions: StatusOption[];
    delegationOptions: DelegationOption[];
    resultOptions: TargetOption[];
    matchOptions: TargetOption[];
    canManage: boolean;
};

const statusVariants: Record<
    string,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    filed: 'default',
    under_review: 'outline',
    upheld: 'secondary',
    dismissed: 'outline',
};

function FileProtestDialog({
    delegationOptions,
    resultOptions,
    matchOptions,
    open,
    onOpenChange,
}: {
    delegationOptions: DelegationOption[];
    resultOptions: TargetOption[];
    matchOptions: TargetOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [targetType, setTargetType] = useState<'result' | 'match'>('result');

    const { data, setData, post, processing, errors, reset, transform } =
        useForm({
            delegation_id: '',
            target_id: '',
            grounds: '',
        });

    transform((current) => ({
        delegation_id: current.delegation_id,
        event_result_id: targetType === 'result' ? current.target_id : null,
        match_id: targetType === 'match' ? current.target_id : null,
        grounds: current.grounds,
    }));

    const delegation = delegationOptions.find(
        (option) => String(option.id) === data.delegation_id,
    );

    const targetOptions = (
        targetType === 'result' ? resultOptions : matchOptions
    ).filter((option) => option.meet_id === delegation?.meet_id);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(store().url, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>File protest</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="protest-delegation">Delegation</Label>
                        <Select
                            value={data.delegation_id}
                            onValueChange={(value) => {
                                setData('delegation_id', value);
                                setData('target_id', '');
                            }}
                        >
                            <SelectTrigger id="protest-delegation">
                                <SelectValue placeholder="Select a delegation" />
                            </SelectTrigger>
                            <SelectContent>
                                {delegationOptions.map((option) => (
                                    <SelectItem
                                        key={option.id}
                                        value={String(option.id)}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.delegation_id} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-[8rem_1fr]">
                        <div className="space-y-2">
                            <Label htmlFor="protest-type">Against</Label>
                            <Select
                                value={targetType}
                                onValueChange={(value) => {
                                    setTargetType(value as 'result' | 'match');
                                    setData('target_id', '');
                                }}
                            >
                                <SelectTrigger id="protest-type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="result">
                                        Result
                                    </SelectItem>
                                    <SelectItem value="match">Match</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="protest-target">Target</Label>
                            <Select
                                value={data.target_id}
                                onValueChange={(value) =>
                                    setData('target_id', value)
                                }
                                disabled={!data.delegation_id}
                            >
                                <SelectTrigger id="protest-target">
                                    <SelectValue
                                        placeholder={
                                            data.delegation_id
                                                ? 'Select target'
                                                : 'Select a delegation first'
                                        }
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {targetOptions.map((option) => (
                                        <SelectItem
                                            key={option.id}
                                            value={String(option.id)}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError
                                message={
                                    (errors as Record<string, string>)
                                        .event_result_id ??
                                    (errors as Record<string, string>).match_id
                                }
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="protest-grounds">Grounds</Label>
                        <Input
                            id="protest-grounds"
                            value={data.grounds}
                            onChange={(e) => setData('grounds', e.target.value)}
                            placeholder="What happened and why it is protested"
                        />
                        <InputError message={errors.grounds} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            File protest
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DecideDialog({
    protest,
    open,
    onOpenChange,
}: {
    protest: Protest;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, patch, processing, errors, reset } = useForm({
        decision: 'upheld',
        remarks: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        patch(decide(protest.id).url, {
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
                    <DialogTitle>Decide protest #{protest.id}</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <p className="text-sm text-muted-foreground">
                        Upholding a protest never changes a result by itself —
                        apply the correction afterwards through the results
                        workflow.
                    </p>
                    <div className="space-y-2">
                        <Label htmlFor="protest-decision">Decision</Label>
                        <Select
                            value={data.decision}
                            onValueChange={(value) =>
                                setData('decision', value)
                            }
                        >
                            <SelectTrigger id="protest-decision">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="upheld">Uphold</SelectItem>
                                <SelectItem value="dismissed">
                                    Dismiss
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.decision} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="protest-remarks">Remarks</Label>
                        <Input
                            id="protest-remarks"
                            value={data.remarks}
                            onChange={(e) => setData('remarks', e.target.value)}
                            placeholder="Basis of the decision"
                        />
                        <InputError message={errors.remarks} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Record decision
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function CorrectFromProtestDialog({
    protest,
    open,
    onOpenChange,
}: {
    protest: Protest;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, patch, processing, errors } = useForm({
        reason: protest.correction_reason,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        if (protest.correctable_result_id === null) {
            return;
        }

        patch(correct(protest.correctable_result_id).url, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Correct result</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <p className="text-sm text-muted-foreground">
                        This reopens the protested result for re-encoding and a
                        fresh validation — the single result-change path. The
                        current standing is preserved in the audit trail.
                    </p>
                    <div className="space-y-2">
                        <Label htmlFor="protest-correction-reason">
                            Reason for correction
                        </Label>
                        <Input
                            id="protest-correction-reason"
                            value={data.reason}
                            onChange={(e) => setData('reason', e.target.value)}
                        />
                        <InputError message={errors.reason} />
                    </div>
                    <DialogFooter>
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={processing}
                        >
                            Reopen result
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Protests({
    protests,
    filters,
    statusOptions,
    delegationOptions,
    resultOptions,
    matchOptions,
    canManage,
}: Props) {
    const [fileOpen, setFileOpen] = useState(false);
    const [deciding, setDeciding] = useState<Protest | null>(null);
    const [correcting, setCorrecting] = useState<Protest | null>(null);

    const applyStatus = (status: string) => {
        router.get(index().url, status !== 'all' ? { status } : {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Protests" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Protests"
                    description="Protests against results and matches, decided by meet management."
                    actions={
                        delegationOptions.length > 0 && (
                            <Button onClick={() => setFileOpen(true)}>
                                <Plus />
                                File protest
                            </Button>
                        )
                    }
                />

                <Select
                    value={filters.status ?? 'all'}
                    onValueChange={applyStatus}
                >
                    <SelectTrigger
                        className="w-56"
                        aria-label="Filter by status"
                    >
                        <SelectValue placeholder="All statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All statuses</SelectItem>
                        {statusOptions.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                {protests.data.length === 0 ? (
                    <EmptyState
                        icon={Gavel}
                        title="No protests found"
                        description="Protests filed by delegations will appear here."
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Target</TableHead>
                                    <TableHead>Delegation</TableHead>
                                    <TableHead>Grounds</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Decision</TableHead>
                                    {canManage && (
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {protests.data.map((protest) => (
                                    <TableRow key={protest.id}>
                                        <TableCell className="max-w-64">
                                            <p className="truncate font-medium">
                                                {protest.target}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                #{protest.id} ·{' '}
                                                {protest.filed_by ?? '—'} ·{' '}
                                                {protest.filed_at}
                                            </p>
                                        </TableCell>
                                        <TableCell className="max-w-48 truncate">
                                            {protest.delegation}
                                        </TableCell>
                                        <TableCell className="max-w-64 truncate">
                                            {protest.grounds}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariants[
                                                        protest.status
                                                    ] ?? 'outline'
                                                }
                                            >
                                                {protest.status_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="max-w-56">
                                            {protest.decided_by ? (
                                                <>
                                                    <p className="truncate text-sm">
                                                        {protest.remarks}
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {protest.decided_by} ·{' '}
                                                        {protest.decided_at}
                                                    </p>
                                                </>
                                            ) : (
                                                '—'
                                            )}
                                        </TableCell>
                                        {canManage && (
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    {protest.can_review && (
                                                        <ConfirmDialog
                                                            trigger={
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                >
                                                                    Review
                                                                </Button>
                                                            }
                                                            title="Take under review?"
                                                            description="Marks the protest as being reviewed by meet management."
                                                            confirmLabel="Review"
                                                            onConfirm={() =>
                                                                router.patch(
                                                                    review(
                                                                        protest.id,
                                                                    ).url,
                                                                    {},
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        />
                                                    )}
                                                    {protest.can_decide && (
                                                        <Button
                                                            size="sm"
                                                            onClick={() =>
                                                                setDeciding(
                                                                    protest,
                                                                )
                                                            }
                                                        >
                                                            Decide
                                                        </Button>
                                                    )}
                                                    {protest.correctable_result_id !==
                                                        null && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                setCorrecting(
                                                                    protest,
                                                                )
                                                            }
                                                        >
                                                            Correct result
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        )}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <PaginationControls
                    page={protests}
                    url={index().url}
                    label="protests"
                    params={filters.status ? { status: filters.status } : {}}
                />
            </div>

            <FileProtestDialog
                delegationOptions={delegationOptions}
                resultOptions={resultOptions}
                matchOptions={matchOptions}
                open={fileOpen}
                onOpenChange={setFileOpen}
            />

            {deciding && (
                <DecideDialog
                    key={deciding.id}
                    protest={deciding}
                    open={deciding !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setDeciding(null);
                        }
                    }}
                />
            )}

            {correcting && (
                <CorrectFromProtestDialog
                    key={correcting.id}
                    protest={correcting}
                    open={correcting !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setCorrecting(null);
                        }
                    }}
                />
            )}
        </>
    );
}

Protests.layout = {
    breadcrumbs: [
        {
            title: 'Protests',
            href: index(),
        },
    ],
};
