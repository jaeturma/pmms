import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Landmark, Plus } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { PaginationControls } from '@/components/pagination-controls';
import type { Paginated } from '@/components/pagination-controls';
import { SearchBar } from '@/components/search-bar';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { pluralizeAreaLabel } from '@/lib/utils';
import {
    archive,
    destroy,
    index,
    restore,
    store,
    update,
} from '@/routes/districts';

type District = {
    id: number;
    name: string;
    nickname: string | null;
    active: boolean;
    schools_count: number;
};

type Props = {
    districts: Paginated<District>;
    filters: { search: string };
    canManage: boolean;
};

function DistrictFormDialog({
    district,
    areaLabel,
    open,
    onOpenChange,
}: {
    district: District | null;
    areaLabel: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: district?.name ?? '',
        nickname: district?.nickname ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        };

        if (district) {
            put(update(district.id).url, options);
        } else {
            post(store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {district
                            ? `Edit ${areaLabel.toLowerCase()}`
                            : `Add ${areaLabel.toLowerCase()}`}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="district-name">Name</Label>
                        <Input
                            id="district-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            autoFocus
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="district-nickname">
                            Delegation nickname
                        </Label>
                        <Input
                            id="district-nickname"
                            value={data.nickname}
                            onChange={(e) =>
                                setData('nickname', e.target.value)
                            }
                            placeholder="e.g. Tigers"
                        />
                        <InputError message={errors.nickname} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {district
                                ? 'Save changes'
                                : `Create ${areaLabel.toLowerCase()}`}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Districts({ districts, filters, canManage }: Props) {
    const { division } = usePage().props;
    const areaLabel = division.areaLabel;
    const areaLabelPlural = pluralizeAreaLabel(areaLabel);

    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<District | null>(null);

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (district: District) => {
        setEditing(district);
        setFormOpen(true);
    };

    return (
        <>
            <Head title={areaLabelPlural} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={areaLabelPlural}
                    description={`${areaLabelPlural} of the division.`}
                    actions={
                        canManage && (
                            <Button onClick={openCreate}>
                                <Plus />
                                Add {areaLabel.toLowerCase()}
                            </Button>
                        )
                    }
                />

                <SearchBar
                    initial={filters.search}
                    placeholder={`Search ${areaLabelPlural.toLowerCase()}`}
                    url={index().url}
                />

                {districts.data.length === 0 ? (
                    <EmptyState
                        icon={Landmark}
                        title={`No ${areaLabelPlural.toLowerCase()} yet`}
                        description={`${areaLabelPlural} registered for the division will appear here.`}
                        action={
                            canManage && (
                                <Button onClick={openCreate}>
                                    Add {areaLabel.toLowerCase()}
                                </Button>
                            )
                        }
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Nickname</TableHead>
                                    <TableHead>Schools</TableHead>
                                    <TableHead>Status</TableHead>
                                    {canManage && (
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {districts.data.map((district) => (
                                    <TableRow key={district.id}>
                                        <TableCell className="font-medium">
                                            {district.name}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {district.nickname ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {district.schools_count}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    district.active
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {district.active
                                                    ? 'Active'
                                                    : 'Archived'}
                                            </Badge>
                                        </TableCell>
                                        {canManage && (
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            openEdit(district)
                                                        }
                                                    >
                                                        Edit
                                                    </Button>
                                                    <ConfirmDialog
                                                        trigger={
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                            >
                                                                {district.active
                                                                    ? 'Archive'
                                                                    : 'Restore'}
                                                            </Button>
                                                        }
                                                        title={
                                                            district.active
                                                                ? `Archive ${areaLabel.toLowerCase()}?`
                                                                : `Restore ${areaLabel.toLowerCase()}?`
                                                        }
                                                        description={
                                                            district.active
                                                                ? `Archived ${areaLabelPlural.toLowerCase()} stay in records but are hidden from new registrations.`
                                                                : `The ${areaLabel.toLowerCase()} becomes available for registrations again.`
                                                        }
                                                        confirmLabel={
                                                            district.active
                                                                ? 'Archive'
                                                                : 'Restore'
                                                        }
                                                        onConfirm={() =>
                                                            router.patch(
                                                                district.active
                                                                    ? archive(
                                                                          district.id,
                                                                      ).url
                                                                    : restore(
                                                                          district.id,
                                                                      ).url,
                                                                {},
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    />
                                                    {district.schools_count ===
                                                        0 && (
                                                        <ConfirmDialog
                                                            trigger={
                                                                <Button
                                                                    variant="destructive"
                                                                    size="sm"
                                                                >
                                                                    Delete
                                                                </Button>
                                                            }
                                                            title={`Delete ${areaLabel.toLowerCase()}?`}
                                                            description={`This permanently removes the ${areaLabel.toLowerCase()}. Only ${areaLabelPlural.toLowerCase()} without schools can be deleted.`}
                                                            confirmLabel="Delete"
                                                            destructive
                                                            onConfirm={() =>
                                                                router.delete(
                                                                    destroy(
                                                                        district.id,
                                                                    ).url,
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        />
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
                    page={districts}
                    url={index().url}
                    label={areaLabelPlural.toLowerCase()}
                    params={filters.search ? { search: filters.search } : {}}
                />
            </div>

            <DistrictFormDialog
                key={editing?.id ?? 'create'}
                district={editing}
                areaLabel={areaLabel}
                open={formOpen}
                onOpenChange={setFormOpen}
            />
        </>
    );
}

Districts.layout = {
    breadcrumbs: [
        {
            title: 'Districts',
            href: index(),
        },
    ],
};
