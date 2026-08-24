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
    technicalOfficials as syncTechnicalOfficials,
    tournamentManager as syncTournamentManager,
    update,
} from '@/routes/sports';

const MAX_SPORT_PHOTO_SIZE_BYTES = 4 * 1024 * 1024;

type TechnicalOfficial = {
    id: number;
    name: string;
};

type TournamentManager = {
    id: number;
    name: string;
};

type Sport = {
    id: number;
    name: string;
    active: boolean;
    events_count: number;
    photo_url: string | null;
    technical_officials: TechnicalOfficial[];
    tournament_manager: TournamentManager | null;
};

type TechnicalOfficialOption = {
    id: number;
    name: string;
    email: string;
};

type TournamentManagerOption = {
    id: number;
    name: string;
    email: string;
};

type Props = {
    sports: Paginated<Sport>;
    filters: { search: string };
    technicalOfficialOptions: TechnicalOfficialOption[];
    tournamentManagerOptions: TournamentManagerOption[];
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
    const { data, setData, post, processing, errors, reset, setError, clearErrors } = useForm<{
        _method?: string;
        name: string;
        photo: File | null;
        remove_photo: boolean;
    }>({
        ...(sport ? { _method: 'put' } : {}),
        name: sport?.name ?? '',
        photo: null,
        remove_photo: false,
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
            post(update(sport.id).url, options);
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
                    <div className="space-y-2">
                        <Label htmlFor="sport-photo">Card background (optional, max 4MB)</Label>
                        <p className="text-xs text-muted-foreground">Use a wide sports photo. It appears lightly behind public sport cards.</p>
                        {sport?.photo_url && !data.photo && !data.remove_photo && (
                            <div className="relative overflow-hidden rounded-lg border">
                                <img src={sport.photo_url} alt={`${sport.name} card background`} className="h-28 w-full object-cover" />
                                <Button type="button" variant="secondary" size="sm" className="absolute right-2 bottom-2" onClick={() => setData('remove_photo', true)}>Remove</Button>
                            </div>
                        )}
                        {data.remove_photo && (
                            <p className="text-sm text-muted-foreground">The current photo will be removed on save. <button type="button" className="underline" onClick={() => setData('remove_photo', false)}>Undo</button></p>
                        )}
                        <Input
                            id="sport-photo"
                            type="file"
                            accept="image/*"
                            onChange={(e) => {
                                const file = e.target.files?.[0] ?? null;
                                if (file && file.size > MAX_SPORT_PHOTO_SIZE_BYTES) {
                                    setError('photo', 'The photo must not be larger than 4MB.');
                                    e.target.value = '';
                                    return;
                                }
                                clearErrors('photo');
                                setData((current) => ({ ...current, photo: file, remove_photo: false }));
                            }}
                        />
                        <InputError message={errors.photo} />
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

function TechnicalOfficialsDialog({
    sport,
    technicalOfficialOptions,
    open,
    onOpenChange,
}: {
    sport: Sport;
    technicalOfficialOptions: TechnicalOfficialOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [selected, setSelected] = useState<number[]>(
        sport.technical_officials.map((official) => official.id),
    );
    const [processing, setProcessing] = useState(false);

    const toggle = (id: number, checked: boolean) => {
        setSelected((current) =>
            checked
                ? [...current, id]
                : current.filter((value) => value !== id),
        );
    };

    const save = () => {
        setProcessing(true);
        router.put(
            syncTechnicalOfficials(sport.id).url,
            { user_ids: selected },
            {
                preserveScroll: true,
                onSuccess: () => onOpenChange(false),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        Technical officials for {sport.name}
                    </DialogTitle>
                </DialogHeader>
                {technicalOfficialOptions.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No user accounts have the Technical Official role yet.
                    </p>
                ) : (
                    <div className="max-h-72 space-y-2 overflow-y-auto pr-2">
                        {technicalOfficialOptions.map((option) => (
                            <div
                                key={option.id}
                                className="flex items-center gap-2"
                            >
                                <Checkbox
                                    id={`technical-official-${option.id}`}
                                    checked={selected.includes(option.id)}
                                    onCheckedChange={(checked) =>
                                        toggle(option.id, checked === true)
                                    }
                                />
                                <Label
                                    htmlFor={`technical-official-${option.id}`}
                                    className="font-normal"
                                >
                                    {option.name}
                                    <span className="text-muted-foreground">
                                        {' '}
                                        ({option.email})
                                    </span>
                                </Label>
                            </div>
                        ))}
                    </div>
                )}
                <DialogFooter>
                    <Button
                        onClick={save}
                        disabled={
                            processing || technicalOfficialOptions.length === 0
                        }
                    >
                        Save technical officials
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function TournamentManagerDialog({
    sport,
    tournamentManagerOptions,
    open,
    onOpenChange,
}: {
    sport: Sport;
    tournamentManagerOptions: TournamentManagerOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, put, processing, errors, reset } = useForm({
        user_id: sport.tournament_manager
            ? String(sport.tournament_manager.id)
            : '',
    });

    const save = () => {
        put(syncTournamentManager(sport.id).url, {
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
                    <DialogTitle>
                        Tournament manager for {sport.name}
                    </DialogTitle>
                </DialogHeader>
                {tournamentManagerOptions.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No user accounts have the Tournament Manager role yet.
                    </p>
                ) : (
                    <div className="space-y-2">
                        <Label htmlFor="tournament-manager-select">
                            Tournament manager
                        </Label>
                        <Select
                            value={data.user_id || 'none'}
                            onValueChange={(value) =>
                                setData(
                                    'user_id',
                                    value === 'none' ? '' : value,
                                )
                            }
                        >
                            <SelectTrigger id="tournament-manager-select">
                                <SelectValue placeholder="None" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">None</SelectItem>
                                {tournamentManagerOptions.map((option) => (
                                    <SelectItem
                                        key={option.id}
                                        value={String(option.id)}
                                    >
                                        {option.name} ({option.email})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.user_id} />
                    </div>
                )}
                <DialogFooter>
                    <Button onClick={save} disabled={processing}>
                        Save tournament manager
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function Sports({
    sports,
    filters,
    technicalOfficialOptions,
    tournamentManagerOptions,
    canManage,
}: Props) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Sport | null>(null);
    const [officialsSport, setOfficialsSport] = useState<Sport | null>(null);
    const [managerSport, setManagerSport] = useState<Sport | null>(null);

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
                                    <TableHead>Technical officials</TableHead>
                                    <TableHead>Tournament manager</TableHead>
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
                                        <TableCell>
                                            {canManage ? (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        setOfficialsSport(sport)
                                                    }
                                                >
                                                    {sport.technical_officials
                                                        .length === 0
                                                        ? 'Assign'
                                                        : `${sport.technical_officials.length} assigned`}
                                                </Button>
                                            ) : sport.technical_officials
                                                  .length === 0 ? (
                                                <span className="text-muted-foreground">
                                                    None assigned
                                                </span>
                                            ) : (
                                                sport.technical_officials
                                                    .map(
                                                        (official) =>
                                                            official.name,
                                                    )
                                                    .join(', ')
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {canManage ? (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        setManagerSport(sport)
                                                    }
                                                >
                                                    {sport.tournament_manager
                                                        ? sport
                                                              .tournament_manager
                                                              .name
                                                        : 'Assign'}
                                                </Button>
                                            ) : sport.tournament_manager ? (
                                                sport.tournament_manager.name
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    None assigned
                                                </span>
                                            )}
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

            {officialsSport && (
                <TechnicalOfficialsDialog
                    key={officialsSport.id}
                    sport={officialsSport}
                    technicalOfficialOptions={technicalOfficialOptions}
                    open={officialsSport !== null}
                    onOpenChange={(open) => !open && setOfficialsSport(null)}
                />
            )}

            {managerSport && (
                <TournamentManagerDialog
                    key={managerSport.id}
                    sport={managerSport}
                    tournamentManagerOptions={tournamentManagerOptions}
                    open={managerSport !== null}
                    onOpenChange={(open) => !open && setManagerSport(null)}
                />
            )}
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
