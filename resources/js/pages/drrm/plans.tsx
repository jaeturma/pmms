import { Head, router, useForm } from '@inertiajs/react';
import { LifeBuoy, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
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
import {
    destroy as destroyEquipment,
    store as storeEquipment,
} from '@/routes/drrm-equipment';
import {
    destroy as destroyPlan,
    store as storePlan,
} from '@/routes/drrm-plans';
import { index } from '@/routes/drrm-plans';
import {
    destroy as destroyContact,
    store as storeContact,
} from '@/routes/emergency-contacts';
import {
    destroy as destroyRoute,
    store as storeRoute,
} from '@/routes/evacuation-routes';
import {
    destroy as destroyChecklist,
    status as updateChecklistStatus,
    store as storeChecklist,
} from '@/routes/readiness-checklists';
import {
    destroy as destroyVenuePlan,
    store as storeVenuePlan,
} from '@/routes/venue-emergency-plans';

type Plan = {
    id: number;
    meet_id: number;
    meet: string;
    category: string;
    category_label: string;
    title: string;
    description: string;
};
type VenuePlan = {
    id: number;
    meet_id: number;
    meet: string;
    venue_id: number;
    venue: string;
    plan_detail: string;
};
type Route = {
    id: number;
    venue_id: number;
    venue: string;
    name: string;
    description: string;
};
type Contact = {
    id: number;
    meet_id: number;
    meet: string;
    name: string;
    role: string | null;
    phone: string;
    category: string | null;
    category_label: string | null;
};
type EquipmentItem = {
    id: number;
    meet_id: number;
    meet: string;
    name: string;
    quantity: number;
    venue: string | null;
    notes: string | null;
};
type Checklist = {
    id: number;
    meet_id: number;
    meet: string;
    category: string;
    category_label: string;
    item: string;
    is_complete: boolean;
    completed_by: string | null;
};

type Option = { id: number; label: string };
type ValueLabel = { value: string; label: string };

type Props = {
    plans: Plan[];
    venuePlans: VenuePlan[];
    evacuationRoutes: Route[];
    emergencyContacts: Contact[];
    equipment: EquipmentItem[];
    readinessChecklists: Checklist[];
    filters: { meet_id: number | null };
    meetOptions: Option[];
    venueOptions: Option[];
    categoryOptions: ValueLabel[];
};

function AddPlanDialog({
    open,
    onOpenChange,
    meetOptions,
    categoryOptions,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    meetOptions: Option[];
    categoryOptions: ValueLabel[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        meet_id: string;
        category: string;
        title: string;
        description: string;
    }>({
        meet_id: '',
        category: '',
        title: '',
        description: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storePlan().url, {
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
                    <DialogTitle>Add DRRM plan</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="plan-meet">Meet</Label>
                        <Select
                            value={data.meet_id}
                            onValueChange={(v) => setData('meet_id', v)}
                        >
                            <SelectTrigger id="plan-meet">
                                <SelectValue placeholder="Select a meet" />
                            </SelectTrigger>
                            <SelectContent>
                                {meetOptions.map((m) => (
                                    <SelectItem key={m.id} value={String(m.id)}>
                                        {m.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.meet_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="plan-category">Category</Label>
                        <Select
                            value={data.category}
                            onValueChange={(v) => setData('category', v)}
                        >
                            <SelectTrigger id="plan-category">
                                <SelectValue placeholder="Select a category" />
                            </SelectTrigger>
                            <SelectContent>
                                {categoryOptions.map((c) => (
                                    <SelectItem key={c.value} value={c.value}>
                                        {c.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.category} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="plan-title">Title</Label>
                        <Input
                            id="plan-title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                        />
                        <InputError message={errors.title} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="plan-description">Description</Label>
                        <Textarea
                            id="plan-description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                        />
                        <InputError message={errors.description} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Add plan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function AddVenuePlanDialog({
    open,
    onOpenChange,
    meetOptions,
    venueOptions,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    meetOptions: Option[];
    venueOptions: Option[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        meet_id: string;
        venue_id: string;
        plan_detail: string;
    }>({ meet_id: '', venue_id: '', plan_detail: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeVenuePlan().url, {
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
                    <DialogTitle>Add venue emergency plan</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="vplan-meet">Meet</Label>
                        <Select
                            value={data.meet_id}
                            onValueChange={(v) => setData('meet_id', v)}
                        >
                            <SelectTrigger id="vplan-meet">
                                <SelectValue placeholder="Select a meet" />
                            </SelectTrigger>
                            <SelectContent>
                                {meetOptions.map((m) => (
                                    <SelectItem key={m.id} value={String(m.id)}>
                                        {m.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.meet_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="vplan-venue">Venue</Label>
                        <Select
                            value={data.venue_id}
                            onValueChange={(v) => setData('venue_id', v)}
                        >
                            <SelectTrigger id="vplan-venue">
                                <SelectValue placeholder="Select a venue" />
                            </SelectTrigger>
                            <SelectContent>
                                {venueOptions.map((v) => (
                                    <SelectItem key={v.id} value={String(v.id)}>
                                        {v.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.venue_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="vplan-detail">Plan detail</Label>
                        <Textarea
                            id="vplan-detail"
                            value={data.plan_detail}
                            onChange={(e) =>
                                setData('plan_detail', e.target.value)
                            }
                        />
                        <InputError message={errors.plan_detail} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Add plan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function AddRouteDialog({
    open,
    onOpenChange,
    meetOptions,
    venueOptions,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    meetOptions: Option[];
    venueOptions: Option[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        meet_id: string;
        venue_id: string;
        name: string;
        description: string;
    }>({ meet_id: '', venue_id: '', name: '', description: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeRoute().url, {
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
                    <DialogTitle>Add evacuation route</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="route-meet">Meet</Label>
                        <Select
                            value={data.meet_id}
                            onValueChange={(v) => setData('meet_id', v)}
                        >
                            <SelectTrigger id="route-meet">
                                <SelectValue placeholder="Select a meet" />
                            </SelectTrigger>
                            <SelectContent>
                                {meetOptions.map((m) => (
                                    <SelectItem key={m.id} value={String(m.id)}>
                                        {m.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.meet_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="route-venue">Venue</Label>
                        <Select
                            value={data.venue_id}
                            onValueChange={(v) => setData('venue_id', v)}
                        >
                            <SelectTrigger id="route-venue">
                                <SelectValue placeholder="Select a venue" />
                            </SelectTrigger>
                            <SelectContent>
                                {venueOptions.map((v) => (
                                    <SelectItem key={v.id} value={String(v.id)}>
                                        {v.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.venue_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="route-name">Name</Label>
                        <Input
                            id="route-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="route-description">Description</Label>
                        <Textarea
                            id="route-description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                        />
                        <InputError message={errors.description} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Add route
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function AddContactDialog({
    open,
    onOpenChange,
    meetOptions,
    categoryOptions,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    meetOptions: Option[];
    categoryOptions: ValueLabel[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        meet_id: string;
        name: string;
        role: string;
        phone: string;
        category: string;
    }>({ meet_id: '', name: '', role: '', phone: '', category: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeContact().url, {
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
                    <DialogTitle>Add emergency contact</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="contact-meet">Meet</Label>
                        <Select
                            value={data.meet_id}
                            onValueChange={(v) => setData('meet_id', v)}
                        >
                            <SelectTrigger id="contact-meet">
                                <SelectValue placeholder="Select a meet" />
                            </SelectTrigger>
                            <SelectContent>
                                {meetOptions.map((m) => (
                                    <SelectItem key={m.id} value={String(m.id)}>
                                        {m.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.meet_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="contact-name">Name</Label>
                        <Input
                            id="contact-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="contact-role">Role (optional)</Label>
                        <Input
                            id="contact-role"
                            value={data.role}
                            onChange={(e) => setData('role', e.target.value)}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="contact-phone">Phone</Label>
                        <Input
                            id="contact-phone"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                        />
                        <InputError message={errors.phone} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="contact-category">
                            Category (optional)
                        </Label>
                        <Select
                            value={data.category}
                            onValueChange={(v) => setData('category', v)}
                        >
                            <SelectTrigger id="contact-category">
                                <SelectValue placeholder="Select a category" />
                            </SelectTrigger>
                            <SelectContent>
                                {categoryOptions.map((c) => (
                                    <SelectItem key={c.value} value={c.value}>
                                        {c.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Add contact
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function AddEquipmentDialog({
    open,
    onOpenChange,
    meetOptions,
    venueOptions,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    meetOptions: Option[];
    venueOptions: Option[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        meet_id: string;
        name: string;
        quantity: string;
        venue_id: string;
        notes: string;
    }>({ meet_id: '', name: '', quantity: '', venue_id: '', notes: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeEquipment().url, {
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
                    <DialogTitle>Add DRRM equipment</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="equip-meet">Meet</Label>
                        <Select
                            value={data.meet_id}
                            onValueChange={(v) => setData('meet_id', v)}
                        >
                            <SelectTrigger id="equip-meet">
                                <SelectValue placeholder="Select a meet" />
                            </SelectTrigger>
                            <SelectContent>
                                {meetOptions.map((m) => (
                                    <SelectItem key={m.id} value={String(m.id)}>
                                        {m.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.meet_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="equip-name">Name</Label>
                        <Input
                            id="equip-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="equip-quantity">Quantity</Label>
                        <Input
                            id="equip-quantity"
                            type="number"
                            min={1}
                            value={data.quantity}
                            onChange={(e) =>
                                setData('quantity', e.target.value)
                            }
                        />
                        <InputError message={errors.quantity} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="equip-venue">Venue (optional)</Label>
                        <Select
                            value={data.venue_id}
                            onValueChange={(v) => setData('venue_id', v)}
                        >
                            <SelectTrigger id="equip-venue">
                                <SelectValue placeholder="Select a venue" />
                            </SelectTrigger>
                            <SelectContent>
                                {venueOptions.map((v) => (
                                    <SelectItem key={v.id} value={String(v.id)}>
                                        {v.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="equip-notes">Notes (optional)</Label>
                        <Textarea
                            id="equip-notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                        />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Add equipment
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function AddChecklistDialog({
    open,
    onOpenChange,
    meetOptions,
    categoryOptions,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    meetOptions: Option[];
    categoryOptions: ValueLabel[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        meet_id: string;
        category: string;
        item: string;
    }>({ meet_id: '', category: '', item: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeChecklist().url, {
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
                    <DialogTitle>Add checklist item</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="check-meet">Meet</Label>
                        <Select
                            value={data.meet_id}
                            onValueChange={(v) => setData('meet_id', v)}
                        >
                            <SelectTrigger id="check-meet">
                                <SelectValue placeholder="Select a meet" />
                            </SelectTrigger>
                            <SelectContent>
                                {meetOptions.map((m) => (
                                    <SelectItem key={m.id} value={String(m.id)}>
                                        {m.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.meet_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="check-category">Category</Label>
                        <Select
                            value={data.category}
                            onValueChange={(v) => setData('category', v)}
                        >
                            <SelectTrigger id="check-category">
                                <SelectValue placeholder="Select a category" />
                            </SelectTrigger>
                            <SelectContent>
                                {categoryOptions.map((c) => (
                                    <SelectItem key={c.value} value={c.value}>
                                        {c.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.category} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="check-item">Item</Label>
                        <Input
                            id="check-item"
                            value={data.item}
                            onChange={(e) => setData('item', e.target.value)}
                        />
                        <InputError message={errors.item} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Add item
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function DrrmPlans({
    plans,
    venuePlans,
    evacuationRoutes,
    emergencyContacts,
    equipment,
    readinessChecklists,
    filters,
    meetOptions,
    venueOptions,
    categoryOptions,
}: Props) {
    const [openDialog, setOpenDialog] = useState<
        | null
        | 'plan'
        | 'venuePlan'
        | 'route'
        | 'contact'
        | 'equipment'
        | 'checklist'
    >(null);

    const applyMeetFilter = (value: string) => {
        router.get(index().url, value === 'all' ? {} : { meet_id: value }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="DRRM Plans" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="DRRM Plans"
                    description="Disaster risk reduction and management: plans, venue readiness, and equipment per meet."
                />

                <Select
                    value={filters.meet_id ? String(filters.meet_id) : 'all'}
                    onValueChange={applyMeetFilter}
                >
                    <SelectTrigger className="w-64" aria-label="Filter by meet">
                        <SelectValue placeholder="All meets" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All meets</SelectItem>
                        {meetOptions.map((m) => (
                            <SelectItem key={m.id} value={String(m.id)}>
                                {m.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Plans</CardTitle>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setOpenDialog('plan')}
                        >
                            <Plus aria-hidden="true" />
                            Add plan
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {plans.length === 0 ? (
                            <EmptyState
                                icon={LifeBuoy}
                                title="No DRRM plans yet"
                                description="Add a weather, medical, or security plan for a meet."
                            />
                        ) : (
                            <ul className="space-y-2">
                                {plans.map((plan) => (
                                    <li
                                        key={plan.id}
                                        className="flex flex-wrap items-start justify-between gap-2 rounded-lg border p-2 text-sm"
                                    >
                                        <div>
                                            <span className="font-medium">
                                                {plan.title}
                                            </span>{' '}
                                            <Badge variant="outline">
                                                {plan.category_label}
                                            </Badge>{' '}
                                            <span className="text-muted-foreground">
                                                — {plan.meet}
                                            </span>
                                            <p className="text-muted-foreground">
                                                {plan.description}
                                            </p>
                                        </div>
                                        <ConfirmDialog
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            }
                                            title="Remove plan?"
                                            description={`"${plan.title}" will be removed.`}
                                            confirmLabel="Remove"
                                            destructive
                                            onConfirm={() =>
                                                router.delete(
                                                    destroyPlan(plan.id).url,
                                                    { preserveScroll: true },
                                                )
                                            }
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Venue emergency plans</CardTitle>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setOpenDialog('venuePlan')}
                        >
                            <Plus aria-hidden="true" />
                            Add
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {venuePlans.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                None yet.
                            </p>
                        ) : (
                            <ul className="space-y-2">
                                {venuePlans.map((vp) => (
                                    <li
                                        key={vp.id}
                                        className="flex flex-wrap items-start justify-between gap-2 rounded-lg border p-2 text-sm"
                                    >
                                        <div>
                                            <span className="font-medium">
                                                {vp.venue}
                                            </span>{' '}
                                            <span className="text-muted-foreground">
                                                — {vp.meet}
                                            </span>
                                            <p className="text-muted-foreground">
                                                {vp.plan_detail}
                                            </p>
                                        </div>
                                        <ConfirmDialog
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            }
                                            title="Remove venue plan?"
                                            description={`The plan for ${vp.venue} will be removed.`}
                                            confirmLabel="Remove"
                                            destructive
                                            onConfirm={() =>
                                                router.delete(
                                                    destroyVenuePlan(vp.id).url,
                                                    { preserveScroll: true },
                                                )
                                            }
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Evacuation routes</CardTitle>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setOpenDialog('route')}
                        >
                            <Plus aria-hidden="true" />
                            Add
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {evacuationRoutes.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                None yet.
                            </p>
                        ) : (
                            <ul className="space-y-2">
                                {evacuationRoutes.map((r) => (
                                    <li
                                        key={r.id}
                                        className="flex flex-wrap items-start justify-between gap-2 rounded-lg border p-2 text-sm"
                                    >
                                        <div>
                                            <span className="font-medium">
                                                {r.name}
                                            </span>{' '}
                                            <span className="text-muted-foreground">
                                                — {r.venue}
                                            </span>
                                            <p className="text-muted-foreground">
                                                {r.description}
                                            </p>
                                        </div>
                                        <ConfirmDialog
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            }
                                            title="Remove route?"
                                            description={`"${r.name}" will be removed.`}
                                            confirmLabel="Remove"
                                            destructive
                                            onConfirm={() =>
                                                router.delete(
                                                    destroyRoute(r.id).url,
                                                    {
                                                        preserveScroll: true,
                                                        data: {
                                                            meet_id:
                                                                filters.meet_id ??
                                                                meetOptions[0]
                                                                    ?.id,
                                                        },
                                                    },
                                                )
                                            }
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Emergency contacts</CardTitle>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setOpenDialog('contact')}
                        >
                            <Plus aria-hidden="true" />
                            Add
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {emergencyContacts.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                None yet.
                            </p>
                        ) : (
                            <ul className="space-y-2">
                                {emergencyContacts.map((c) => (
                                    <li
                                        key={c.id}
                                        className="flex flex-wrap items-start justify-between gap-2 rounded-lg border p-2 text-sm"
                                    >
                                        <div>
                                            <span className="font-medium">
                                                {c.name}
                                            </span>
                                            {c.category_label && (
                                                <Badge
                                                    variant="outline"
                                                    className="ml-2"
                                                >
                                                    {c.category_label}
                                                </Badge>
                                            )}{' '}
                                            <span className="text-muted-foreground">
                                                — {c.role ? `${c.role}, ` : ''}
                                                {c.phone} — {c.meet}
                                            </span>
                                        </div>
                                        <ConfirmDialog
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            }
                                            title="Remove contact?"
                                            description={`"${c.name}" will be removed.`}
                                            confirmLabel="Remove"
                                            destructive
                                            onConfirm={() =>
                                                router.delete(
                                                    destroyContact(c.id).url,
                                                    { preserveScroll: true },
                                                )
                                            }
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Equipment</CardTitle>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setOpenDialog('equipment')}
                        >
                            <Plus aria-hidden="true" />
                            Add
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {equipment.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                None yet.
                            </p>
                        ) : (
                            <ul className="space-y-2">
                                {equipment.map((eq) => (
                                    <li
                                        key={eq.id}
                                        className="flex flex-wrap items-start justify-between gap-2 rounded-lg border p-2 text-sm"
                                    >
                                        <div>
                                            <span className="font-medium">
                                                {eq.name}
                                            </span>{' '}
                                            <span className="text-muted-foreground">
                                                — qty {eq.quantity}
                                                {eq.venue &&
                                                    ` — ${eq.venue}`} —{' '}
                                                {eq.meet}
                                            </span>
                                            {eq.notes && (
                                                <p className="text-muted-foreground">
                                                    {eq.notes}
                                                </p>
                                            )}
                                        </div>
                                        <ConfirmDialog
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            }
                                            title="Remove equipment?"
                                            description={`"${eq.name}" will be removed.`}
                                            confirmLabel="Remove"
                                            destructive
                                            onConfirm={() =>
                                                router.delete(
                                                    destroyEquipment(eq.id).url,
                                                    { preserveScroll: true },
                                                )
                                            }
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Readiness checklist</CardTitle>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setOpenDialog('checklist')}
                        >
                            <Plus aria-hidden="true" />
                            Add
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {readinessChecklists.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                None yet.
                            </p>
                        ) : (
                            <ul className="space-y-2">
                                {readinessChecklists.map((item) => (
                                    <li
                                        key={item.id}
                                        className="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-2 text-sm"
                                    >
                                        <div className="flex items-center gap-2">
                                            <Checkbox
                                                checked={item.is_complete}
                                                onCheckedChange={(checked) =>
                                                    router.patch(
                                                        updateChecklistStatus(
                                                            item.id,
                                                        ).url,
                                                        {
                                                            is_complete:
                                                                checked ===
                                                                true,
                                                        },
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            />
                                            <span
                                                className={
                                                    item.is_complete
                                                        ? 'text-muted-foreground line-through'
                                                        : ''
                                                }
                                            >
                                                {item.item}
                                            </span>
                                            <Badge variant="outline">
                                                {item.category_label}
                                            </Badge>
                                            <span className="text-muted-foreground">
                                                — {item.meet}
                                            </span>
                                        </div>
                                        <ConfirmDialog
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            }
                                            title="Remove item?"
                                            description={`"${item.item}" will be removed.`}
                                            confirmLabel="Remove"
                                            destructive
                                            onConfirm={() =>
                                                router.delete(
                                                    destroyChecklist(item.id)
                                                        .url,
                                                    { preserveScroll: true },
                                                )
                                            }
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>

            <AddPlanDialog
                open={openDialog === 'plan'}
                onOpenChange={(o) => setOpenDialog(o ? 'plan' : null)}
                meetOptions={meetOptions}
                categoryOptions={categoryOptions}
            />
            <AddVenuePlanDialog
                open={openDialog === 'venuePlan'}
                onOpenChange={(o) => setOpenDialog(o ? 'venuePlan' : null)}
                meetOptions={meetOptions}
                venueOptions={venueOptions}
            />
            <AddRouteDialog
                open={openDialog === 'route'}
                onOpenChange={(o) => setOpenDialog(o ? 'route' : null)}
                meetOptions={meetOptions}
                venueOptions={venueOptions}
            />
            <AddContactDialog
                open={openDialog === 'contact'}
                onOpenChange={(o) => setOpenDialog(o ? 'contact' : null)}
                meetOptions={meetOptions}
                categoryOptions={categoryOptions}
            />
            <AddEquipmentDialog
                open={openDialog === 'equipment'}
                onOpenChange={(o) => setOpenDialog(o ? 'equipment' : null)}
                meetOptions={meetOptions}
                venueOptions={venueOptions}
            />
            <AddChecklistDialog
                open={openDialog === 'checklist'}
                onOpenChange={(o) => setOpenDialog(o ? 'checklist' : null)}
                meetOptions={meetOptions}
                categoryOptions={categoryOptions}
            />
        </>
    );
}

DrrmPlans.layout = {
    breadcrumbs: [
        {
            title: 'DRRM Plans',
            href: index(),
        },
    ],
};
