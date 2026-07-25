import { Head, router, useForm } from '@inertiajs/react';
import { Megaphone, Plus } from 'lucide-react';
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
import { Textarea } from '@/components/ui/textarea';
import {
    destroy,
    index,
    publish,
    store,
    unpublish,
    update,
} from '@/routes/announcements';

type Announcement = {
    id: number;
    meet_id: number | null;
    meet: string | null;
    title: string;
    body: string;
    is_published: boolean;
    published_at: string | null;
    author: string | null;
};

type Option = { id: number; label: string };

type Props = {
    announcements: Paginated<Announcement>;
    filters: { search: string };
    meetOptions: Option[];
};

function AnnouncementFormDialog({
    announcement,
    meetOptions,
    open,
    onOpenChange,
}: {
    announcement: Announcement | null;
    meetOptions: Option[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset, transform } =
        useForm({
            meet_id: announcement?.meet_id
                ? String(announcement.meet_id)
                : 'general',
            title: announcement?.title ?? '',
            body: announcement?.body ?? '',
        });

    transform((current) => ({
        ...current,
        meet_id: current.meet_id === 'general' ? null : current.meet_id,
    }));

    const submit = (e: FormEvent) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        };

        if (announcement) {
            put(update(announcement.id).url, options);
        } else {
            post(store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {announcement
                            ? 'Edit announcement'
                            : 'New announcement'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="announcement-meet">Meet</Label>
                        <Select
                            value={data.meet_id}
                            onValueChange={(value) => setData('meet_id', value)}
                        >
                            <SelectTrigger id="announcement-meet">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="general">
                                    General (no meet)
                                </SelectItem>
                                {meetOptions.map((option) => (
                                    <SelectItem
                                        key={option.id}
                                        value={String(option.id)}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.meet_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="announcement-title">Title</Label>
                        <Input
                            id="announcement-title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            autoFocus
                        />
                        <InputError message={errors.title} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="announcement-body">Body</Label>
                        <Textarea
                            id="announcement-body"
                            value={data.body}
                            onChange={(e) => setData('body', e.target.value)}
                            rows={6}
                            placeholder="Plain text — shown on the public portal once published."
                        />
                        <InputError message={errors.body} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {announcement ? 'Save changes' : 'Create'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Announcements({
    announcements,
    filters,
    meetOptions,
}: Props) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Announcement | null>(null);

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (announcement: Announcement) => {
        setEditing(announcement);
        setFormOpen(true);
    };

    return (
        <>
            <Head title="Announcements" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Announcements"
                    description="Public advisories — visible on the portal once published."
                    actions={
                        <Button onClick={openCreate}>
                            <Plus />
                            New announcement
                        </Button>
                    }
                />

                <SearchBar
                    initial={filters.search}
                    placeholder="Search announcements"
                    url={index().url}
                />

                {announcements.data.length === 0 ? (
                    <EmptyState
                        icon={Megaphone}
                        title="No announcements yet"
                        description="Advisories for the public portal will appear here."
                        action={
                            <Button onClick={openCreate}>
                                New announcement
                            </Button>
                        }
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Title</TableHead>
                                    <TableHead>Meet</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Published</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {announcements.data.map((announcement) => (
                                    <TableRow key={announcement.id}>
                                        <TableCell className="max-w-72">
                                            <p className="truncate font-medium">
                                                {announcement.title}
                                            </p>
                                            <p className="truncate text-sm text-muted-foreground">
                                                {announcement.body}
                                            </p>
                                        </TableCell>
                                        <TableCell>
                                            {announcement.meet ?? 'General'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    announcement.is_published
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {announcement.is_published
                                                    ? 'Published'
                                                    : 'Draft'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {announcement.published_at ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        openEdit(announcement)
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
                                                            {announcement.is_published
                                                                ? 'Unpublish'
                                                                : 'Publish'}
                                                        </Button>
                                                    }
                                                    title={
                                                        announcement.is_published
                                                            ? 'Unpublish announcement?'
                                                            : 'Publish announcement?'
                                                    }
                                                    description={
                                                        announcement.is_published
                                                            ? 'It disappears from the public portal immediately.'
                                                            : 'It becomes visible on the public portal.'
                                                    }
                                                    confirmLabel={
                                                        announcement.is_published
                                                            ? 'Unpublish'
                                                            : 'Publish'
                                                    }
                                                    onConfirm={() =>
                                                        router.patch(
                                                            announcement.is_published
                                                                ? unpublish(
                                                                      announcement.id,
                                                                  ).url
                                                                : publish(
                                                                      announcement.id,
                                                                  ).url,
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                />
                                                <ConfirmDialog
                                                    trigger={
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                        >
                                                            Delete
                                                        </Button>
                                                    }
                                                    title="Delete announcement?"
                                                    description="This permanently removes the announcement."
                                                    confirmLabel="Delete"
                                                    destructive
                                                    onConfirm={() =>
                                                        router.delete(
                                                            destroy(
                                                                announcement.id,
                                                            ).url,
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                />
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <PaginationControls
                    page={announcements}
                    url={index().url}
                    label="announcements"
                    params={filters.search ? { search: filters.search } : {}}
                />
            </div>

            <AnnouncementFormDialog
                key={editing?.id ?? 'create'}
                announcement={editing}
                meetOptions={meetOptions}
                open={formOpen}
                onOpenChange={setFormOpen}
            />
        </>
    );
}

Announcements.layout = {
    breadcrumbs: [
        {
            title: 'Announcements',
            href: index(),
        },
    ],
};
