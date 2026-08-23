import { Head, router, useForm } from '@inertiajs/react';
import { ClipboardList, Plus, Trash2 } from 'lucide-react';
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
    destroy as destroyMember,
    status as updateMemberStatus,
    store as storeMember,
} from '@/routes/management-team-members';
import {
    destroy as destroyTeam,
    index,
    store as storeTeam,
    update as updateTeam,
} from '@/routes/management-teams';

type Member = {
    id: number;
    user: string;
    user_email: string;
    role_title: string | null;
    is_head: boolean;
    responsibilities: string | null;
    status: string;
    status_label: string;
};

type Team = {
    id: number;
    team_type: string;
    team_type_label: string;
    name: string;
    description: string | null;
    status: string;
    status_label: string;
    members: Member[];
};

type Option = { id: number; label: string };
type ValueLabel = { value: string; label: string };

type Props = {
    teams: Paginated<Team>;
    teamTypeOptions: ValueLabel[];
    teamStatusOptions: ValueLabel[];
    memberStatusOptions: ValueLabel[];
    userOptions: Option[];
    canManage: boolean;
};

const memberStatusVariant: Record<
    string,
    'default' | 'outline' | 'destructive'
> = {
    pending: 'outline',
    active: 'default',
    declined: 'destructive',
    ended: 'outline',
};

function CreateTeamDialog({
    open,
    onOpenChange,
    teamTypeOptions,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    teamTypeOptions: ValueLabel[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        team_type: string;
        name: string;
        description: string;
    }>({
        team_type: '',
        name: '',
        description: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeTeam().url, {
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
                    <DialogTitle>Add team</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="team-type">Team type</Label>
                        <Select
                            value={data.team_type}
                            onValueChange={(value) => {
                                setData('team_type', value);
                                const match = teamTypeOptions.find(
                                    (option) => option.value === value,
                                );

                                if (match && data.name === '') {
                                    setData('name', match.label);
                                }
                            }}
                        >
                            <SelectTrigger id="team-type">
                                <SelectValue placeholder="Select a team type" />
                            </SelectTrigger>
                            <SelectContent>
                                {teamTypeOptions.map((type) => (
                                    <SelectItem
                                        key={type.value}
                                        value={type.value}
                                    >
                                        {type.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.team_type} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="team-name">Name</Label>
                        <Input
                            id="team-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="team-description">
                            Description (optional)
                        </Label>
                        <Textarea
                            id="team-description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                        />
                        <InputError message={errors.description} />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Add team
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function EditTeamDialog({
    team,
    open,
    onOpenChange,
    teamStatusOptions,
}: {
    team: Team;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    teamStatusOptions: ValueLabel[];
}) {
    const { data, setData, put, processing, errors } = useForm<{
        name: string;
        description: string;
        status: string;
    }>({
        name: team.name,
        description: team.description ?? '',
        status: team.status,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(updateTeam(team.id).url, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit {team.team_type_label}</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="edit-team-name">Name</Label>
                        <Input
                            id="edit-team-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="edit-team-description">
                            Description (optional)
                        </Label>
                        <Textarea
                            id="edit-team-description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="edit-team-status">Status</Label>
                        <Select
                            value={data.status}
                            onValueChange={(value) => setData('status', value)}
                        >
                            <SelectTrigger id="edit-team-status">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {teamStatusOptions.map((status) => (
                                    <SelectItem
                                        key={status.value}
                                        value={status.value}
                                    >
                                        {status.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
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

function AddMemberDialog({
    teamId,
    open,
    onOpenChange,
    userOptions,
}: {
    teamId: number;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    userOptions: Option[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        management_team_id: string;
        user_id: string;
        role_title: string;
        is_head: boolean;
        responsibilities: string;
    }>({
        management_team_id: String(teamId),
        user_id: '',
        role_title: '',
        is_head: false,
        responsibilities: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(storeMember().url, {
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
                    <DialogTitle>Add member</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="member-user">Person</Label>
                        <Select
                            value={data.user_id}
                            onValueChange={(value) => setData('user_id', value)}
                        >
                            <SelectTrigger id="member-user">
                                <SelectValue placeholder="Select a person" />
                            </SelectTrigger>
                            <SelectContent>
                                {userOptions.map((user) => (
                                    <SelectItem
                                        key={user.id}
                                        value={String(user.id)}
                                    >
                                        {user.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.user_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="member-role-title">
                            Role title (optional)
                        </Label>
                        <Input
                            id="member-role-title"
                            value={data.role_title}
                            onChange={(e) =>
                                setData('role_title', e.target.value)
                            }
                            placeholder="e.g. Logistics Coordinator"
                        />
                        <InputError message={errors.role_title} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="member-responsibilities">
                            Responsibilities (optional)
                        </Label>
                        <Textarea
                            id="member-responsibilities"
                            value={data.responsibilities}
                            onChange={(e) =>
                                setData('responsibilities', e.target.value)
                            }
                        />
                        <InputError message={errors.responsibilities} />
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="member-head"
                            checked={data.is_head}
                            onCheckedChange={(checked) =>
                                setData('is_head', checked === true)
                            }
                        />
                        <Label htmlFor="member-head" className="font-normal">
                            This person heads the team
                        </Label>
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Add member
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function TeamCard({
    team,
    canManage,
    memberStatusOptions,
    teamStatusOptions,
    userOptions,
}: {
    team: Team;
    canManage: boolean;
    memberStatusOptions: ValueLabel[];
    teamStatusOptions: ValueLabel[];
    userOptions: Option[];
}) {
    const [editOpen, setEditOpen] = useState(false);
    const [addMemberOpen, setAddMemberOpen] = useState(false);

    return (
        <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-4">
                <div>
                    <CardTitle className="flex items-center gap-2">
                        {team.name}
                        <Badge variant="outline">{team.status_label}</Badge>
                    </CardTitle>
                    <p className="text-sm text-muted-foreground">
                        {team.team_type_label}
                    </p>
                    {team.description && (
                        <p className="mt-1 text-sm text-muted-foreground">
                            {team.description}
                        </p>
                    )}
                </div>
                {canManage && (
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
                            title="Remove team?"
                            description={`${team.name} and its ${team.members.length} member(s) will be removed.`}
                            confirmLabel="Remove"
                            destructive
                            onConfirm={() =>
                                router.delete(destroyTeam(team.id).url, {
                                    preserveScroll: true,
                                })
                            }
                        />
                    </div>
                )}
            </CardHeader>
            <CardContent>
                {team.members.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No members yet.
                    </p>
                ) : (
                    <ul className="space-y-2">
                        {team.members.map((member) => (
                            <li
                                key={member.id}
                                className="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-2 text-sm"
                            >
                                <div>
                                    <span className="font-medium">
                                        {member.user}
                                    </span>{' '}
                                    <span className="text-muted-foreground">
                                        ({member.user_email})
                                    </span>
                                    {member.role_title && (
                                        <span className="text-muted-foreground">
                                            {' '}
                                            — {member.role_title}
                                        </span>
                                    )}
                                    {member.is_head && (
                                        <Badge
                                            variant="outline"
                                            className="ml-2"
                                        >
                                            Head
                                        </Badge>
                                    )}
                                </div>
                                <div className="flex items-center gap-2">
                                    {canManage ? (
                                        <Select
                                            value={member.status}
                                            onValueChange={(value) =>
                                                router.patch(
                                                    updateMemberStatus(
                                                        member.id,
                                                    ).url,
                                                    { status: value },
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <SelectTrigger
                                                className="w-28"
                                                aria-label={`Status for ${member.user}`}
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {memberStatusOptions.map(
                                                    (status) => (
                                                        <SelectItem
                                                            key={status.value}
                                                            value={status.value}
                                                        >
                                                            {status.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    ) : (
                                        <Badge
                                            variant={
                                                memberStatusVariant[
                                                    member.status
                                                ] ?? 'outline'
                                            }
                                        >
                                            {member.status_label}
                                        </Badge>
                                    )}
                                    {canManage && (
                                        <ConfirmDialog
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={`Remove ${member.user}`}
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            }
                                            title="Remove member?"
                                            description={`${member.user} will be removed from ${team.name}.`}
                                            confirmLabel="Remove"
                                            destructive
                                            onConfirm={() =>
                                                router.delete(
                                                    destroyMember(member.id)
                                                        .url,
                                                    { preserveScroll: true },
                                                )
                                            }
                                        />
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
                {canManage && (
                    <Button
                        variant="outline"
                        size="sm"
                        className="mt-3"
                        onClick={() => setAddMemberOpen(true)}
                    >
                        <Plus aria-hidden="true" />
                        Add member
                    </Button>
                )}
            </CardContent>

            {canManage && (
                <>
                    <EditTeamDialog
                        team={team}
                        open={editOpen}
                        onOpenChange={setEditOpen}
                        teamStatusOptions={teamStatusOptions}
                    />
                    <AddMemberDialog
                        teamId={team.id}
                        open={addMemberOpen}
                        onOpenChange={setAddMemberOpen}
                        userOptions={userOptions}
                    />
                </>
            )}
        </Card>
    );
}

export default function ManagementTeams({
    teams,
    teamTypeOptions,
    teamStatusOptions,
    memberStatusOptions,
    userOptions,
    canManage,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);

    return (
        <>
            <Head title="Management Teams" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Management Teams"
                    description="Top Management, Meet Management, Results Committee, DSAC, ICT, Supply, Food, Billeting, Transport, Medical, and DRRM team membership."
                    actions={
                        canManage && (
                            <Button onClick={() => setCreateOpen(true)}>
                                <Plus aria-hidden="true" />
                                Add team
                            </Button>
                        )
                    }
                />

                {teams.data.length === 0 ? (
                    <EmptyState
                        icon={ClipboardList}
                        title="No teams yet"
                        description="Add a management team (Top Management, ICT, Medical, and so on) to a meet."
                    />
                ) : (
                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        {teams.data.map((team) => (
                            <TeamCard
                                key={team.id}
                                team={team}
                                canManage={canManage}
                                memberStatusOptions={memberStatusOptions}
                                teamStatusOptions={teamStatusOptions}
                                userOptions={userOptions}
                            />
                        ))}
                    </div>
                )}
                <PaginationControls
                    page={teams}
                    url={index().url}
                    label="management teams"
                />
            </div>

            <CreateTeamDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
                teamTypeOptions={teamTypeOptions}
            />
        </>
    );
}

ManagementTeams.layout = {
    breadcrumbs: [
        {
            title: 'Management Teams',
            href: index(),
        },
    ],
};
