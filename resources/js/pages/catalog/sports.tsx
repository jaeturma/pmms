import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Trophy } from 'lucide-react';
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
} from '@/routes/sports';

type Sport = {
    id: number;
    name: string;
    active: boolean;
    events_count: number;
};

type Props = {
    sports: Paginated<Sport>;
    filters: { search: string };
    canManage: boolean;
};

function SportFormDialog({
    sport,
    open,
    onOpenChange,
}: {
    sport: Sport | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: sport?.name ?? '',
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

        if (sport) {
            put(update(sport.id).url, options);
        } else {
            post(store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {sport ? 'Edit sport' : 'Add sport'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="sport-name">Name</Label>
                        <Input
                            id="sport-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            autoFocus
                        />
                        <InputError message={errors.name} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {sport ? 'Save changes' : 'Create sport'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Sports({ sports, filters, canManage }: Props) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Sport | null>(null);

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (sport: Sport) => {
        setEditing(sport);
        setFormOpen(true);
    };

    return (
        <>
            <Head title="Sports" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Sports"
                    description="Sports available for provincial meets."
                    actions={
                        canManage && (
                            <Button onClick={openCreate}>
                                <Plus />
                                Add sport
                            </Button>
                        )
                    }
                />

                <SearchBar
                    initial={filters.search}
                    placeholder="Search sports"
                    url={index().url}
                />

                {sports.data.length === 0 ? (
                    <EmptyState
                        icon={Trophy}
                        title="No sports yet"
                        description="Sports configured for the division will appear here."
                        action={
                            canManage && (
                                <Button onClick={openCreate}>Add sport</Button>
                            )
                        }
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Events</TableHead>
                                    <TableHead>Status</TableHead>
                                    {canManage && (
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {sports.data.map((sport) => (
                                    <TableRow key={sport.id}>
                                        <TableCell className="font-medium">
                                            {sport.name}
                                        </TableCell>
                                        <TableCell>
                                            {sport.events_count}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    sport.active
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {sport.active
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
                                                            openEdit(sport)
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
                                                                {sport.active
                                                                    ? 'Archive'
                                                                    : 'Restore'}
                                                            </Button>
                                                        }
                                                        title={
                                                            sport.active
                                                                ? 'Archive sport?'
                                                                : 'Restore sport?'
                                                        }
                                                        description={
                                                            sport.active
                                                                ? 'Archived sports stay in records but are hidden from new meets.'
                                                                : 'The sport becomes available for meets again.'
                                                        }
                                                        confirmLabel={
                                                            sport.active
                                                                ? 'Archive'
                                                                : 'Restore'
                                                        }
                                                        onConfirm={() =>
                                                            router.patch(
                                                                sport.active
                                                                    ? archive(
                                                                          sport.id,
                                                                      ).url
                                                                    : restore(
                                                                          sport.id,
                                                                      ).url,
                                                                {},
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    />
                                                    {sport.events_count ===
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
                                                            title="Delete sport?"
                                                            description="This permanently removes the sport. Only sports without events can be deleted."
                                                            confirmLabel="Delete"
                                                            destructive
                                                            onConfirm={() =>
                                                                router.delete(
                                                                    destroy(
                                                                        sport.id,
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
                    page={sports}
                    url={index().url}
                    label="sports"
                    params={filters.search ? { search: filters.search } : {}}
                />
            </div>

            <SportFormDialog
                key={editing?.id ?? 'create'}
                sport={editing}
                open={formOpen}
                onOpenChange={setFormOpen}
            />
        </>
    );
}

Sports.layout = {
    breadcrumbs: [
        {
            title: 'Sports',
            href: index(),
        },
    ],
};
