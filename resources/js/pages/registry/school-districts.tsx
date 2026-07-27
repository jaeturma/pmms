import { Head, router, useForm } from '@inertiajs/react';
import { Milestone, Plus } from 'lucide-react';
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
import {
    archive,
    destroy,
    index,
    restore,
    store,
    update,
} from '@/routes/school-districts';

type Municipality = { id: number; name: string };

type SchoolDistrict = {
    id: number;
    district_id: number;
    name: string;
    nickname: string | null;
    active: boolean;
    schools_count: number;
    municipality: Municipality;
};

type Props = {
    schoolDistricts: Paginated<SchoolDistrict>;
    filters: { search: string };
    municipalities: Municipality[];
    canManage: boolean;
};

function SchoolDistrictFormDialog({
    schoolDistrict,
    municipalities,
    open,
    onOpenChange,
}: {
    schoolDistrict: SchoolDistrict | null;
    municipalities: Municipality[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        district_id: schoolDistrict
            ? String(schoolDistrict.district_id)
            : '',
        name: schoolDistrict?.name ?? '',
        nickname: schoolDistrict?.nickname ?? '',
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

        if (schoolDistrict) {
            put(update(schoolDistrict.id).url, options);
        } else {
            post(store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {schoolDistrict ? 'Edit district' : 'Add district'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="school-district-municipality">
                            Municipality
                        </Label>
                        <Select
                            value={data.district_id}
                            onValueChange={(value) =>
                                setData('district_id', value)
                            }
                        >
                            <SelectTrigger id="school-district-municipality">
                                <SelectValue placeholder="Select a municipality" />
                            </SelectTrigger>
                            <SelectContent>
                                {municipalities.map((municipality) => (
                                    <SelectItem
                                        key={municipality.id}
                                        value={String(municipality.id)}
                                    >
                                        {municipality.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.district_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="school-district-name">Name</Label>
                        <Input
                            id="school-district-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="e.g. Laak North"
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="school-district-nickname">
                            Nickname (optional)
                        </Label>
                        <Input
                            id="school-district-nickname"
                            value={data.nickname}
                            onChange={(e) =>
                                setData('nickname', e.target.value)
                            }
                        />
                        <InputError message={errors.nickname} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {schoolDistrict
                                ? 'Save changes'
                                : 'Create district'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function SchoolDistricts({
    schoolDistricts,
    filters,
    municipalities,
    canManage,
}: Props) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<SchoolDistrict | null>(null);

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (schoolDistrict: SchoolDistrict) => {
        setEditing(schoolDistrict);
        setFormOpen(true);
    };

    return (
        <>
            <Head title="Districts" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Districts"
                    description="School districts within each municipality — the sub-unit a school actually belongs to."
                    actions={
                        canManage && (
                            <Button
                                onClick={openCreate}
                                disabled={municipalities.length === 0}
                            >
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

                {schoolDistricts.data.length === 0 ? (
                    <EmptyState
                        icon={Milestone}
                        title="No districts yet"
                        description="A municipality with no districts falls back to its own name in the medal tally's School standings. Add districts here only for municipalities that actually have more than one (e.g. Laak North / Laak South)."
                        action={
                            canManage && (
                                <Button
                                    onClick={openCreate}
                                    disabled={municipalities.length === 0}
                                >
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
                                    <TableHead>Municipality</TableHead>
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
                                {schoolDistricts.data.map((schoolDistrict) => (
                                    <TableRow key={schoolDistrict.id}>
                                        <TableCell className="font-medium">
                                            {schoolDistrict.name}
                                        </TableCell>
                                        <TableCell>
                                            {schoolDistrict.municipality.name}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {schoolDistrict.nickname ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {schoolDistrict.schools_count}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    schoolDistrict.active
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {schoolDistrict.active
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
                                                            openEdit(
                                                                schoolDistrict,
                                                            )
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
                                                                {schoolDistrict.active
                                                                    ? 'Archive'
                                                                    : 'Restore'}
                                                            </Button>
                                                        }
                                                        title={
                                                            schoolDistrict.active
                                                                ? 'Archive district?'
                                                                : 'Restore district?'
                                                        }
                                                        description={
                                                            schoolDistrict.active
                                                                ? 'Archived districts stay in records but are hidden from new registrations.'
                                                                : 'The district becomes available for registrations again.'
                                                        }
                                                        confirmLabel={
                                                            schoolDistrict.active
                                                                ? 'Archive'
                                                                : 'Restore'
                                                        }
                                                        onConfirm={() =>
                                                            router.patch(
                                                                schoolDistrict.active
                                                                    ? archive(
                                                                          schoolDistrict.id,
                                                                      ).url
                                                                    : restore(
                                                                          schoolDistrict.id,
                                                                      ).url,
                                                                {},
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    />
                                                    {schoolDistrict.schools_count ===
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
                                                                        schoolDistrict.id,
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
                    page={schoolDistricts}
                    url={index().url}
                    label="districts"
                    params={filters.search ? { search: filters.search } : {}}
                />
            </div>

            <SchoolDistrictFormDialog
                key={editing?.id ?? 'create'}
                schoolDistrict={editing}
                municipalities={municipalities}
                open={formOpen}
                onOpenChange={setFormOpen}
            />
        </>
    );
}

SchoolDistricts.layout = {
    breadcrumbs: [
        {
            title: 'Districts',
            href: index(),
        },
    ],
};
