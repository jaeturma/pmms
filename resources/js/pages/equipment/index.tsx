import { Head, router, useForm } from '@inertiajs/react';
import { Boxes, Plus, Trash2 } from 'lucide-react';
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
import { index } from '@/routes/equipment';
import {
    destroy as destroyCategory,
    store as storeCategory,
    update as updateCategory,
} from '@/routes/equipment-categories';
import { store as storeIssue } from '@/routes/equipment-issues';
import {
    destroy as destroyItem,
    store as storeItem,
} from '@/routes/equipment-items';
import { store as storeReturn } from '@/routes/equipment-returns';
import { store as storeTransfer } from '@/routes/equipment-transfers';
import { store as storeAdjustment } from '@/routes/inventory-adjustments';

type OpenIssue = {
    id: number;
    venue: string;
    quantity: number;
    outstanding: number;
    custodian_name: string | null;
    status: string;
    status_label: string;
};

type Item = {
    id: number;
    venue: string | null;
    venue_id: number | null;
    quantity: number;
    available: number;
    condition: string | null;
    condition_label: string | null;
    notes: string | null;
    open_issues: OpenIssue[];
};

type Category = {
    id: number;
    name: string;
    description: string | null;
    is_consumable: boolean;
    items: Item[];
};

type Option = { id: number; label: string };
type ValueLabel = { value: string; label: string };

type Props = {
    categories: Category[];
    venueOptions: Option[];
    conditionOptions: ValueLabel[];
    adjustmentTypeOptions: ValueLabel[];
};

function CreateCategoryDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        name: string;
        description: string;
        is_consumable: boolean;
    }>({
        name: '',
        description: '',
        is_consumable: false,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeCategory().url, {
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
                    <DialogTitle>Add category</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="category-name">Name</Label>
                        <Input
                            id="category-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="e.g. Basketballs"
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="category-description">
                            Description (optional)
                        </Label>
                        <Textarea
                            id="category-description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                        />
                        <InputError message={errors.description} />
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="category-consumable"
                            checked={data.is_consumable}
                            onCheckedChange={(checked) =>
                                setData('is_consumable', checked === true)
                            }
                        />
                        <Label
                            htmlFor="category-consumable"
                            className="font-normal"
                        >
                            Consumable (used up when issued, never returned)
                        </Label>
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Add category
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function EditCategoryDialog({
    category,
    open,
    onOpenChange,
}: {
    category: Category;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, put, processing, errors } = useForm<{
        name: string;
        description: string;
        is_consumable: boolean;
    }>({
        name: category.name,
        description: category.description ?? '',
        is_consumable: category.is_consumable,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(updateCategory(category.id).url, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit {category.name}</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="edit-category-name">Name</Label>
                        <Input
                            id="edit-category-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="edit-category-description">
                            Description (optional)
                        </Label>
                        <Textarea
                            id="edit-category-description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                        />
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="edit-category-consumable"
                            checked={data.is_consumable}
                            onCheckedChange={(checked) =>
                                setData('is_consumable', checked === true)
                            }
                        />
                        <Label
                            htmlFor="edit-category-consumable"
                            className="font-normal"
                        >
                            Consumable (used up when issued, never returned)
                        </Label>
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

function AddItemDialog({
    categoryId,
    open,
    onOpenChange,
    venueOptions,
    conditionOptions,
}: {
    categoryId: number;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    venueOptions: Option[];
    conditionOptions: ValueLabel[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        equipment_category_id: string;
        venue_id: string;
        quantity: string;
        condition: string;
        notes: string;
    }>({
        equipment_category_id: String(categoryId),
        venue_id: '',
        quantity: '',
        condition: '',
        notes: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeItem().url, {
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
                    <DialogTitle>Add item</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="item-venue">
                            Venue (optional — leave blank for general storage)
                        </Label>
                        <Select
                            value={data.venue_id}
                            onValueChange={(value) =>
                                setData('venue_id', value)
                            }
                        >
                            <SelectTrigger id="item-venue">
                                <SelectValue placeholder="General storage" />
                            </SelectTrigger>
                            <SelectContent>
                                {venueOptions.map((venue) => (
                                    <SelectItem
                                        key={venue.id}
                                        value={String(venue.id)}
                                    >
                                        {venue.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.venue_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="item-quantity">Quantity</Label>
                        <Input
                            id="item-quantity"
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
                        <Label htmlFor="item-condition">
                            Condition (optional)
                        </Label>
                        <Select
                            value={data.condition}
                            onValueChange={(value) =>
                                setData('condition', value)
                            }
                        >
                            <SelectTrigger id="item-condition">
                                <SelectValue placeholder="Select a condition" />
                            </SelectTrigger>
                            <SelectContent>
                                {conditionOptions.map((condition) => (
                                    <SelectItem
                                        key={condition.value}
                                        value={condition.value}
                                    >
                                        {condition.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.condition} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="item-notes">Notes (optional)</Label>
                        <Textarea
                            id="item-notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                        />
                        <InputError message={errors.notes} />
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

function IssueDialog({
    item,
    open,
    onOpenChange,
    venueOptions,
}: {
    item: Item;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    venueOptions: Option[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        equipment_item_id: string;
        venue_id: string;
        quantity: string;
        custodian_name: string;
        purpose: string;
    }>({
        equipment_item_id: String(item.id),
        venue_id: '',
        quantity: '',
        custodian_name: '',
        purpose: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeIssue().url, {
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
                    <DialogTitle>
                        Issue equipment ({item.available} available)
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="issue-venue">Venue</Label>
                        <Select
                            value={data.venue_id}
                            onValueChange={(value) =>
                                setData('venue_id', value)
                            }
                        >
                            <SelectTrigger id="issue-venue">
                                <SelectValue placeholder="Select a venue" />
                            </SelectTrigger>
                            <SelectContent>
                                {venueOptions.map((venue) => (
                                    <SelectItem
                                        key={venue.id}
                                        value={String(venue.id)}
                                    >
                                        {venue.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.venue_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="issue-quantity">Quantity</Label>
                        <Input
                            id="issue-quantity"
                            type="number"
                            min={1}
                            max={item.available}
                            value={data.quantity}
                            onChange={(e) =>
                                setData('quantity', e.target.value)
                            }
                        />
                        <InputError message={errors.quantity} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="issue-custodian">
                            Custodian (optional)
                        </Label>
                        <Input
                            id="issue-custodian"
                            value={data.custodian_name}
                            onChange={(e) =>
                                setData('custodian_name', e.target.value)
                            }
                            placeholder="Who is taking custody"
                        />
                        <InputError message={errors.custodian_name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="issue-purpose">
                            Purpose (optional)
                        </Label>
                        <Textarea
                            id="issue-purpose"
                            value={data.purpose}
                            onChange={(e) => setData('purpose', e.target.value)}
                        />
                        <InputError message={errors.purpose} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Issue
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function ReturnDialog({
    issue,
    open,
    onOpenChange,
    conditionOptions,
}: {
    issue: OpenIssue;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    conditionOptions: ValueLabel[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        equipment_issue_id: string;
        quantity: string;
        condition_on_return: string;
        notes: string;
    }>({
        equipment_issue_id: String(issue.id),
        quantity: '',
        condition_on_return: '',
        notes: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeReturn().url, {
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
                    <DialogTitle>
                        Return equipment ({issue.outstanding} outstanding)
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="return-quantity">Quantity</Label>
                        <Input
                            id="return-quantity"
                            type="number"
                            min={1}
                            max={issue.outstanding}
                            value={data.quantity}
                            onChange={(e) =>
                                setData('quantity', e.target.value)
                            }
                        />
                        <InputError message={errors.quantity} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="return-condition">
                            Condition on return (optional)
                        </Label>
                        <Select
                            value={data.condition_on_return}
                            onValueChange={(value) =>
                                setData('condition_on_return', value)
                            }
                        >
                            <SelectTrigger id="return-condition">
                                <SelectValue placeholder="Select a condition" />
                            </SelectTrigger>
                            <SelectContent>
                                {conditionOptions.map((condition) => (
                                    <SelectItem
                                        key={condition.value}
                                        value={condition.value}
                                    >
                                        {condition.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.condition_on_return} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="return-notes">Notes (optional)</Label>
                        <Textarea
                            id="return-notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                        />
                        <InputError message={errors.notes} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Return
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function TransferDialog({
    item,
    open,
    onOpenChange,
    venueOptions,
}: {
    item: Item;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    venueOptions: Option[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        equipment_item_id: string;
        to_venue_id: string;
        quantity: string;
        reason: string;
    }>({
        equipment_item_id: String(item.id),
        to_venue_id: '',
        quantity: '',
        reason: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeTransfer().url, {
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
                    <DialogTitle>
                        Transfer equipment ({item.available} available)
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="transfer-venue">To venue</Label>
                        <Select
                            value={data.to_venue_id}
                            onValueChange={(value) =>
                                setData('to_venue_id', value)
                            }
                        >
                            <SelectTrigger id="transfer-venue">
                                <SelectValue placeholder="Select a venue" />
                            </SelectTrigger>
                            <SelectContent>
                                {venueOptions.map((venue) => (
                                    <SelectItem
                                        key={venue.id}
                                        value={String(venue.id)}
                                    >
                                        {venue.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.to_venue_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="transfer-quantity">Quantity</Label>
                        <Input
                            id="transfer-quantity"
                            type="number"
                            min={1}
                            max={item.available}
                            value={data.quantity}
                            onChange={(e) =>
                                setData('quantity', e.target.value)
                            }
                        />
                        <InputError message={errors.quantity} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="transfer-reason">
                            Reason (optional)
                        </Label>
                        <Textarea
                            id="transfer-reason"
                            value={data.reason}
                            onChange={(e) => setData('reason', e.target.value)}
                        />
                        <InputError message={errors.reason} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Transfer
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function AdjustDialog({
    item,
    open,
    onOpenChange,
    adjustmentTypeOptions,
}: {
    item: Item;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    adjustmentTypeOptions: ValueLabel[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        equipment_item_id: string;
        type: string;
        quantity_delta: string;
        reason: string;
    }>({
        equipment_item_id: String(item.id),
        type: '',
        quantity_delta: '',
        reason: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeAdjustment().url, {
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
                    <DialogTitle>
                        Adjust inventory ({item.quantity} on hand)
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="adjust-type">Type</Label>
                        <Select
                            value={data.type}
                            onValueChange={(value) => setData('type', value)}
                        >
                            <SelectTrigger id="adjust-type">
                                <SelectValue placeholder="Select a type" />
                            </SelectTrigger>
                            <SelectContent>
                                {adjustmentTypeOptions.map((type) => (
                                    <SelectItem
                                        key={type.value}
                                        value={type.value}
                                    >
                                        {type.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.type} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="adjust-delta">
                            Quantity change (negative for damage/loss, positive
                            for found)
                        </Label>
                        <Input
                            id="adjust-delta"
                            type="number"
                            value={data.quantity_delta}
                            onChange={(e) =>
                                setData('quantity_delta', e.target.value)
                            }
                        />
                        <InputError message={errors.quantity_delta} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="adjust-reason">Reason</Label>
                        <Textarea
                            id="adjust-reason"
                            value={data.reason}
                            onChange={(e) => setData('reason', e.target.value)}
                        />
                        <InputError message={errors.reason} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Adjust
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function ItemRow({
    item,
    isConsumable,
    venueOptions,
    conditionOptions,
    adjustmentTypeOptions,
}: {
    item: Item;
    isConsumable: boolean;
    venueOptions: Option[];
    conditionOptions: ValueLabel[];
    adjustmentTypeOptions: ValueLabel[];
}) {
    const [issueOpen, setIssueOpen] = useState(false);
    const [transferOpen, setTransferOpen] = useState(false);
    const [adjustOpen, setAdjustOpen] = useState(false);
    const [returningIssue, setReturningIssue] = useState<OpenIssue | null>(
        null,
    );

    return (
        <li className="space-y-2 rounded-lg border p-3 text-sm">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <span className="font-medium">
                        {item.venue ?? 'General storage'}
                    </span>{' '}
                    <span className="text-muted-foreground">
                        — {item.quantity} on hand, {item.available} available
                    </span>
                    {item.condition_label && (
                        <Badge variant="outline" className="ml-2">
                            {item.condition_label}
                        </Badge>
                    )}
                    {item.notes && (
                        <p className="text-muted-foreground">{item.notes}</p>
                    )}
                </div>
                <div className="flex shrink-0 gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setIssueOpen(true)}
                    >
                        Issue
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setTransferOpen(true)}
                    >
                        Transfer
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setAdjustOpen(true)}
                    >
                        Adjust
                    </Button>
                    <ConfirmDialog
                        trigger={
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Remove item"
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        }
                        title="Remove item?"
                        description="This stock line and its history will be removed."
                        confirmLabel="Remove"
                        destructive
                        onConfirm={() =>
                            router.delete(destroyItem(item.id).url, {
                                preserveScroll: true,
                            })
                        }
                    />
                </div>
            </div>

            {item.open_issues.length > 0 && (
                <ul className="space-y-1 border-t pt-2">
                    {item.open_issues.map((issue) => (
                        <li
                            key={issue.id}
                            className="flex flex-wrap items-center justify-between gap-2"
                        >
                            <span className="text-muted-foreground">
                                {issue.outstanding} at {issue.venue}
                                {issue.custodian_name &&
                                    ` — ${issue.custodian_name}`}{' '}
                                <Badge variant="outline">
                                    {issue.status_label}
                                </Badge>
                            </span>
                            {!isConsumable && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setReturningIssue(issue)}
                                >
                                    Return
                                </Button>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            <IssueDialog
                item={item}
                open={issueOpen}
                onOpenChange={setIssueOpen}
                venueOptions={venueOptions}
            />
            <TransferDialog
                item={item}
                open={transferOpen}
                onOpenChange={setTransferOpen}
                venueOptions={venueOptions}
            />
            <AdjustDialog
                item={item}
                open={adjustOpen}
                onOpenChange={setAdjustOpen}
                adjustmentTypeOptions={adjustmentTypeOptions}
            />
            {returningIssue && (
                <ReturnDialog
                    issue={returningIssue}
                    open={returningIssue !== null}
                    onOpenChange={(next) => {
                        if (!next) {
                            setReturningIssue(null);
                        }
                    }}
                    conditionOptions={conditionOptions}
                />
            )}
        </li>
    );
}

function CategoryCard({
    category,
    venueOptions,
    conditionOptions,
    adjustmentTypeOptions,
}: {
    category: Category;
    venueOptions: Option[];
    conditionOptions: ValueLabel[];
    adjustmentTypeOptions: ValueLabel[];
}) {
    const [editOpen, setEditOpen] = useState(false);
    const [addItemOpen, setAddItemOpen] = useState(false);

    return (
        <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-4">
                <div>
                    <CardTitle className="flex items-center gap-2">
                        {category.name}
                        {category.is_consumable && (
                            <Badge variant="outline">Consumable</Badge>
                        )}
                    </CardTitle>
                    {category.description && (
                        <p className="mt-1 text-sm text-muted-foreground">
                            {category.description}
                        </p>
                    )}
                </div>
                <div className="flex shrink-0 gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setEditOpen(true)}
                    >
                        Edit
                    </Button>
                    <ConfirmDialog
                        trigger={
                            <Button variant="ghost" size="icon">
                                <Trash2 className="size-4" />
                            </Button>
                        }
                        title="Remove category?"
                        description={`${category.name} and its ${category.items.length} item(s) will be removed.`}
                        confirmLabel="Remove"
                        destructive
                        onConfirm={() =>
                            router.delete(destroyCategory(category.id).url, {
                                preserveScroll: true,
                            })
                        }
                    />
                </div>
            </CardHeader>
            <CardContent>
                {category.items.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No items yet.
                    </p>
                ) : (
                    <ul className="space-y-2">
                        {category.items.map((item) => (
                            <ItemRow
                                key={item.id}
                                item={item}
                                isConsumable={category.is_consumable}
                                venueOptions={venueOptions}
                                conditionOptions={conditionOptions}
                                adjustmentTypeOptions={adjustmentTypeOptions}
                            />
                        ))}
                    </ul>
                )}
                <Button
                    variant="outline"
                    size="sm"
                    className="mt-3"
                    onClick={() => setAddItemOpen(true)}
                >
                    <Plus aria-hidden="true" />
                    Add item
                </Button>
            </CardContent>

            <EditCategoryDialog
                category={category}
                open={editOpen}
                onOpenChange={setEditOpen}
            />
            <AddItemDialog
                categoryId={category.id}
                open={addItemOpen}
                onOpenChange={setAddItemOpen}
                venueOptions={venueOptions}
                conditionOptions={conditionOptions}
            />
        </Card>
    );
}

export default function Equipment({
    categories,
    venueOptions,
    conditionOptions,
    adjustmentTypeOptions,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);

    return (
        <>
            <Head title="Equipment" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Equipment"
                    description="Supply Team equipment catalog — categories, stock, issues, returns, transfers, and inventory adjustments per meet."
                    actions={
                        <Button onClick={() => setCreateOpen(true)}>
                            <Plus aria-hidden="true" />
                            Add category
                        </Button>
                    }
                />

                {categories.length === 0 ? (
                    <EmptyState
                        icon={Boxes}
                        title="No equipment categories yet"
                        description="Add a category (Basketballs, First Aid Kits, Tables and Chairs, and so on) to a meet."
                    />
                ) : (
                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        {categories.map((category) => (
                            <CategoryCard
                                key={category.id}
                                category={category}
                                venueOptions={venueOptions}
                                conditionOptions={conditionOptions}
                                adjustmentTypeOptions={adjustmentTypeOptions}
                            />
                        ))}
                    </div>
                )}
            </div>

            <CreateCategoryDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
            />
        </>
    );
}

Equipment.layout = {
    breadcrumbs: [
        {
            title: 'Equipment',
            href: index(),
        },
    ],
};
