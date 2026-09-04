import { Head, router, useForm } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, Plus, Stethoscope } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Textarea } from '@/components/ui/textarea';
import { index } from '@/routes/medical';
import {
    store as requestAccess,
    review as reviewAccess,
} from '@/routes/medical-access';
import {
    store as storeClearance,
    update as updateClearance,
} from '@/routes/medical-clearances';

type Clearance = {
    id: number;
    person: string;
    person_type: 'athlete' | 'personnel';
    status: string;
    status_label: string;
    can_view_detail: boolean;
    conditions: string | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    consent_confirmed: boolean | null;
    notes: string | null;
};

type PendingAccessLog = {
    id: number;
    person: string;
    meet: string;
    accessed_by: string;
    reason: string;
    accessed_at: string;
};

type Option = { id: number; label: string };
type ValueLabel = { value: string; label: string };

type EmergencyAccessReveal = {
    clearance_id: number;
    person: string;
    conditions: string | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    notes: string | null;
};

type Props = {
    clearances: Clearance[];
    canManage: boolean;
    athleteOptions: Option[];
    personnelOptions: Option[];
    statusOptions: ValueLabel[];
    canRequestEmergencyAccess: boolean;
    pendingAccessLogs: PendingAccessLog[];
};

function CreateClearanceDialog({
    open,
    onOpenChange,
    athleteOptions,
    personnelOptions,
    statusOptions,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    athleteOptions: Option[];
    personnelOptions: Option[];
    statusOptions: ValueLabel[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        person_type: 'athlete' | 'personnel';
        athlete_id: string;
        personnel_id: string;
        status: string;
        conditions: string;
        emergency_contact_name: string;
        emergency_contact_phone: string;
        consent_confirmed: boolean;
        notes: string;
    }>({
        person_type: 'athlete',
        athlete_id: '',
        personnel_id: '',
        status: 'pending',
        conditions: '',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        consent_confirmed: false,
        notes: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeClearance().url, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                if (!next) {
                    reset();
                }

                onOpenChange(next);
            }}
        >
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add clearance record</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="clearance-person-type">
                            Person type
                        </Label>
                        <Select
                            value={data.person_type}
                            onValueChange={(value) =>
                                setData(
                                    'person_type',
                                    value as 'athlete' | 'personnel',
                                )
                            }
                        >
                            <SelectTrigger id="clearance-person-type">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="athlete">Athlete</SelectItem>
                                <SelectItem value="personnel">
                                    Personnel
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    {data.person_type === 'athlete' ? (
                        <div className="space-y-2">
                            <Label htmlFor="clearance-athlete">Athlete</Label>
                            <Select
                                value={data.athlete_id}
                                onValueChange={(value) =>
                                    setData('athlete_id', value)
                                }
                            >
                                <SelectTrigger id="clearance-athlete">
                                    <SelectValue placeholder="Select an athlete" />
                                </SelectTrigger>
                                <SelectContent>
                                    {athleteOptions.map((a) => (
                                        <SelectItem
                                            key={a.id}
                                            value={String(a.id)}
                                        >
                                            {a.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.athlete_id} />
                        </div>
                    ) : (
                        <div className="space-y-2">
                            <Label htmlFor="clearance-personnel">
                                Personnel
                            </Label>
                            <Select
                                value={data.personnel_id}
                                onValueChange={(value) =>
                                    setData('personnel_id', value)
                                }
                            >
                                <SelectTrigger id="clearance-personnel">
                                    <SelectValue placeholder="Select a personnel record" />
                                </SelectTrigger>
                                <SelectContent>
                                    {personnelOptions.map((p) => (
                                        <SelectItem
                                            key={p.id}
                                            value={String(p.id)}
                                        >
                                            {p.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.personnel_id} />
                        </div>
                    )}
                    <div className="space-y-2">
                        <Label htmlFor="clearance-status">Status</Label>
                        <Select
                            value={data.status}
                            onValueChange={(value) => setData('status', value)}
                        >
                            <SelectTrigger id="clearance-status">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {statusOptions.map((s) => (
                                    <SelectItem key={s.value} value={s.value}>
                                        {s.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="clearance-conditions">
                            Known conditions/allergies (optional)
                        </Label>
                        <Textarea
                            id="clearance-conditions"
                            value={data.conditions}
                            onChange={(e) =>
                                setData('conditions', e.target.value)
                            }
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="clearance-contact-name">
                                Emergency contact name
                            </Label>
                            <Input
                                id="clearance-contact-name"
                                value={data.emergency_contact_name}
                                onChange={(e) =>
                                    setData(
                                        'emergency_contact_name',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="clearance-contact-phone">
                                Emergency contact phone
                            </Label>
                            <Input
                                id="clearance-contact-phone"
                                value={data.emergency_contact_phone}
                                onChange={(e) =>
                                    setData(
                                        'emergency_contact_phone',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="clearance-consent"
                            checked={data.consent_confirmed}
                            onCheckedChange={(checked) =>
                                setData('consent_confirmed', checked === true)
                            }
                        />
                        <Label
                            htmlFor="clearance-consent"
                            className="font-normal"
                        >
                            Parent/guardian consent confirmed
                        </Label>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="clearance-notes">
                            Notes (optional)
                        </Label>
                        <Textarea
                            id="clearance-notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                        />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Add record
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function EditClearanceDialog({
    clearance,
    open,
    onOpenChange,
    statusOptions,
}: {
    clearance: Clearance;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    statusOptions: ValueLabel[];
}) {
    const { data, setData, put, processing, errors } = useForm<{
        status: string;
        conditions: string;
        emergency_contact_name: string;
        emergency_contact_phone: string;
        consent_confirmed: boolean;
        notes: string;
    }>({
        status: clearance.status,
        conditions: clearance.conditions ?? '',
        emergency_contact_name: clearance.emergency_contact_name ?? '',
        emergency_contact_phone: clearance.emergency_contact_phone ?? '',
        consent_confirmed: clearance.consent_confirmed ?? false,
        notes: clearance.notes ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(updateClearance(clearance.id).url, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        Edit clearance — {clearance.person}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="edit-clearance-status">Status</Label>
                        <Select
                            value={data.status}
                            onValueChange={(value) => setData('status', value)}
                        >
                            <SelectTrigger id="edit-clearance-status">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {statusOptions.map((s) => (
                                    <SelectItem key={s.value} value={s.value}>
                                        {s.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.status} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="edit-clearance-conditions">
                            Known conditions/allergies (optional)
                        </Label>
                        <Textarea
                            id="edit-clearance-conditions"
                            value={data.conditions}
                            onChange={(e) =>
                                setData('conditions', e.target.value)
                            }
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="edit-clearance-contact-name">
                                Emergency contact name
                            </Label>
                            <Input
                                id="edit-clearance-contact-name"
                                value={data.emergency_contact_name}
                                onChange={(e) =>
                                    setData(
                                        'emergency_contact_name',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="edit-clearance-contact-phone">
                                Emergency contact phone
                            </Label>
                            <Input
                                id="edit-clearance-contact-phone"
                                value={data.emergency_contact_phone}
                                onChange={(e) =>
                                    setData(
                                        'emergency_contact_phone',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="edit-clearance-consent"
                            checked={data.consent_confirmed}
                            onCheckedChange={(checked) =>
                                setData('consent_confirmed', checked === true)
                            }
                        />
                        <Label
                            htmlFor="edit-clearance-consent"
                            className="font-normal"
                        >
                            Parent/guardian consent confirmed
                        </Label>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="edit-clearance-notes">
                            Notes (optional)
                        </Label>
                        <Textarea
                            id="edit-clearance-notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                        />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Save changes
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function RequestAccessDialog({
    clearanceId,
    open,
    onOpenChange,
}: {
    clearanceId: number;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        medical_clearance_id: string;
        reason: string;
    }>({
        medical_clearance_id: String(clearanceId),
        reason: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(requestAccess().url, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                if (!next) {
                    reset();
                }

                onOpenChange(next);
            }}
        >
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Request emergency access</DialogTitle>
                </DialogHeader>
                <p className="text-sm text-muted-foreground">
                    This will reveal medical detail for this person and is
                    logged for mandatory review. Use only for a genuine
                    emergency.
                </p>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="access-reason">Reason</Label>
                        <Textarea
                            id="access-reason"
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
                            Request access
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function ClearanceRow({
    clearance,
    canRequestEmergencyAccess,
    canManage,
    selected,
    onSelectedChange,
    statusOptions,
}: {
    clearance: Clearance;
    canRequestEmergencyAccess: boolean;
    canManage: boolean;
    selected: boolean;
    onSelectedChange: (selected: boolean) => void;
    statusOptions: ValueLabel[];
}) {
    const [editOpen, setEditOpen] = useState(false);
    const [requestOpen, setRequestOpen] = useState(false);

    return (
        <li className="space-y-2 rounded-lg border p-3 text-sm">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                    {canManage && (
                        <Checkbox
                            className="mr-2"
                            aria-label={`Select ${clearance.person}`}
                            checked={selected}
                            onCheckedChange={(checked) =>
                                onSelectedChange(checked === true)
                            }
                        />
                    )}
                    <span className="font-medium">{clearance.person}</span>{' '}
                    <Badge variant="outline">{clearance.status_label}</Badge>
                </div>
                <div className="flex gap-2">
                    {canManage && clearance.status !== 'cleared' && (
                        <Button
                            size="sm"
                            onClick={() =>
                                router.patch(
                                    `/medical-clearances/${clearance.id}/clear`,
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <CheckCircle2 aria-hidden="true" />
                            Accept / clear
                        </Button>
                    )}
                    {canManage && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setEditOpen(true)}
                        >
                            Edit
                        </Button>
                    )}
                    {!clearance.can_view_detail &&
                        canRequestEmergencyAccess && (
                            <Button
                                variant="destructive"
                                size="sm"
                                onClick={() => setRequestOpen(true)}
                            >
                                <AlertTriangle aria-hidden="true" />
                                Emergency access
                            </Button>
                        )}
                </div>
            </div>
            {clearance.can_view_detail && (
                <div className="space-y-1 border-t pt-2 text-muted-foreground">
                    {clearance.conditions && (
                        <p>Conditions: {clearance.conditions}</p>
                    )}
                    {(clearance.emergency_contact_name ||
                        clearance.emergency_contact_phone) && (
                        <p>
                            Emergency contact:{' '}
                            {[
                                clearance.emergency_contact_name,
                                clearance.emergency_contact_phone,
                            ]
                                .filter(Boolean)
                                .join(' — ')}
                        </p>
                    )}
                    <p>
                        Consent:{' '}
                        {clearance.consent_confirmed
                            ? 'Confirmed'
                            : 'Not yet confirmed'}
                    </p>
                    {clearance.notes && <p>Notes: {clearance.notes}</p>}
                </div>
            )}
            {canManage && (
                <EditClearanceDialog
                    clearance={clearance}
                    open={editOpen}
                    onOpenChange={setEditOpen}
                    statusOptions={statusOptions}
                />
            )}
            <RequestAccessDialog
                clearanceId={clearance.id}
                open={requestOpen}
                onOpenChange={setRequestOpen}
            />
        </li>
    );
}

export default function Medical({
    clearances,
    canManage,
    athleteOptions,
    personnelOptions,
    statusOptions,
    canRequestEmergencyAccess,
    pendingAccessLogs,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [reveal, setReveal] = useState<EmergencyAccessReveal | null>(null);
    const [selectedIds, setSelectedIds] = useState<number[]>([]);

    const clearSelected = () => {
        if (selectedIds.length === 0) {
            return;
        }

        router.patch(
            '/medical-clearances/bulk-clear',
            { clearance_ids: selectedIds },
            {
                preserveScroll: true,
                onSuccess: () => setSelectedIds([]),
            },
        );
    };

    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;
            const data = flash?.emergencyAccess as
                EmergencyAccessReveal | undefined;

            if (data) {
                setReveal(data);
            }
        });
    }, []);

    return (
        <>
            <Head title="Medical" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Medical"
                    description="Medical clearance roster — known conditions, emergency contacts, and consent."
                    actions={
                        canManage && (
                            <div className="flex flex-wrap gap-2">
                                <Button
                                    variant="outline"
                                    disabled={selectedIds.length === 0}
                                    onClick={clearSelected}
                                >
                                    <CheckCircle2 aria-hidden="true" />
                                    Accept selected ({selectedIds.length})
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        router.patch(
                                            '/medical-clearances/bulk-clear',
                                            { all_pending: true },
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    Accept all pending
                                </Button>
                                <Button onClick={() => setCreateOpen(true)}>
                                    <Plus aria-hidden="true" />
                                    Add clearance record
                                </Button>
                            </div>
                        )
                    }
                />

                {pendingAccessLogs.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                Pending emergency-access reviews
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul className="space-y-2">
                                {pendingAccessLogs.map((log) => (
                                    <li
                                        key={log.id}
                                        className="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-2 text-sm"
                                    >
                                        <div>
                                            <span className="font-medium">
                                                {log.person}
                                            </span>{' '}
                                            <span className="text-muted-foreground">
                                                — {log.meet} — accessed by{' '}
                                                {log.accessed_by} (
                                                {log.accessed_at}): {log.reason}
                                            </span>
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                router.patch(
                                                    reviewAccess(log.id).url,
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            Mark reviewed
                                        </Button>
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                )}

                {clearances.length === 0 ? (
                    <EmptyState
                        icon={Stethoscope}
                        title="No clearance records yet"
                        description="Add a medical clearance record for an athlete or personnel."
                    />
                ) : (
                    <ul className="space-y-2">
                        {clearances.map((clearance) => (
                            <ClearanceRow
                                key={clearance.id}
                                clearance={clearance}
                                canRequestEmergencyAccess={
                                    canRequestEmergencyAccess
                                }
                                canManage={canManage}
                                selected={selectedIds.includes(clearance.id)}
                                onSelectedChange={(selected) =>
                                    setSelectedIds((current) =>
                                        selected
                                            ? [...current, clearance.id]
                                            : current.filter(
                                                  (id) => id !== clearance.id,
                                              ),
                                    )
                                }
                                statusOptions={statusOptions}
                            />
                        ))}
                    </ul>
                )}
            </div>

            <CreateClearanceDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
                athleteOptions={athleteOptions}
                personnelOptions={personnelOptions}
                statusOptions={statusOptions}
            />

            <Dialog
                open={reveal !== null}
                onOpenChange={(next) => !next && setReveal(null)}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            Emergency access — {reveal?.person}
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-2 text-sm">
                        {reveal?.conditions && (
                            <p>Conditions: {reveal.conditions}</p>
                        )}
                        {(reveal?.emergency_contact_name ||
                            reveal?.emergency_contact_phone) && (
                            <p>
                                Emergency contact:{' '}
                                {[
                                    reveal?.emergency_contact_name,
                                    reveal?.emergency_contact_phone,
                                ]
                                    .filter(Boolean)
                                    .join(' — ')}
                            </p>
                        )}
                        {reveal?.notes && <p>Notes: {reveal.notes}</p>}
                    </div>
                    <DialogFooter>
                        <Button onClick={() => setReveal(null)}>Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

Medical.layout = {
    breadcrumbs: [
        {
            title: 'Medical',
            href: index(),
        },
    ],
};
