import { Head, router, useForm } from '@inertiajs/react';
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
    open,
    onOpenChange,
}: {
    district: District | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: district?.name ?? '',
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
                        {district ? 'Edit district' : 'Add district'}
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
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {district ? 'Save changes' : 'Create district'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Districts({ districts, filters, canManage }: Props) {
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
            <Head title="Districts" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Districts"
                    description="School districts of the division."
                    actions={
                        canManage && (
                            <Button onClick={openCreate}>
                                <Plus />
                                Add district
                            </Button>
                        )
                    }
                />

                <SearchBar
                    initial={filters.search}
                    placeholder="Search districts"
                    url={index().url}
                />

                {districts.data.length === 0 ? (
                    <EmptyState
                        icon={Landmark}
                        title="No districts yet"
                        description="Districts registered for the division will appear here."
                        action={
                            canManage && (
                                <Button onClick={openCreate}>
                                    Add district
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
                                                                ? 'Archive district?'
                                                                : 'Restore district?'
                                                        }
                                                        description={
                                                            district.active
                                                                ? 'Archived districts stay in records but are hidden from new registrations.'
                                                                : 'The district becomes available for registrations again.'
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
                                                            title="Delete district?"
                                                            description="This permanently removes the district. Only districts without schools can be deleted."
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
                    label="districts"
                    params={filters.search ? { search: filters.search } : {}}
                />
            </div>

            <DistrictFormDialog
                key={editing?.id ?? 'create'}
                district={editing}
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
