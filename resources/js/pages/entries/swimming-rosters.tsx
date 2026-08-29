import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Trash2, Waves } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Roster = {
    meet_sport_id: number;
    delegation_id: number;
    meet: string;
    delegation: string;
    level: string;
    gender: string;
    maximum: number;
    can_manage: boolean;
    members: Array<{
        id: number;
        name: string;
        eligible: boolean;
        medically_cleared: boolean;
        entry_count: number;
    }>;
    candidates: Array<{ id: number; name: string }>;
};

function AddSwimmerDialog({
    roster,
    close,
}: {
    roster: Roster;
    close: () => void;
}) {
    const { data, setData, post, processing, errors } = useForm({
        meet_sport_id: roster.meet_sport_id,
        athlete_id: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/sport-rosters/members', {
            preserveScroll: true,
            onSuccess: close,
        });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && close()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        Add swimmer — {roster.level} {roster.gender}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label>Eligible delegation athlete</Label>
                        <Select
                            value={data.athlete_id}
                            onValueChange={(value) =>
                                setData('athlete_id', value)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select athlete" />
                            </SelectTrigger>
                            <SelectContent>
                                {roster.candidates.map((athlete) => (
                                    <SelectItem
                                        key={athlete.id}
                                        value={String(athlete.id)}
                                    >
                                        {athlete.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.athlete_id} />
                    </div>
                    <DialogFooter>
                        <Button disabled={processing || !data.athlete_id}>
                            Add swimmer
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function SwimmingRosters({ rosters }: { rosters: Roster[] }) {
    const [adding, setAdding] = useState<Roster | null>(null);

    return (
        <>
            <Head title="Swimming rosters" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Swimming rosters"
                    description="Delegation rosters are managed independently for Elementary Boys, Elementary Girls, Secondary Boys, and Secondary Girls."
                />
                {rosters.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground">
                        No Swimming roster is available in your assigned scope.
                    </div>
                ) : (
                    <div className="grid gap-5 xl:grid-cols-2">
                        {rosters.map((roster) => {
                            const full =
                                roster.members.length >= roster.maximum;

                            return (
                                <Card
                                    key={`${roster.meet_sport_id}-${roster.delegation_id}-${roster.level}-${roster.gender}`}
                                >
                                    <CardHeader className="flex-row items-start justify-between">
                                        <div>
                                            <CardTitle className="flex items-center gap-2">
                                                <Waves className="size-5 text-primary" />
                                                {roster.level} {roster.gender}
                                            </CardTitle>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {roster.delegation} ·{' '}
                                                {roster.meet}
                                            </p>
                                        </div>
                                        <Badge
                                            variant={
                                                full
                                                    ? 'destructive'
                                                    : 'secondary'
                                            }
                                        >
                                            {roster.members.length} /{' '}
                                            {roster.maximum}
                                            {full ? ' · Roster full' : ''}
                                        </Badge>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <ol className="divide-y rounded-lg border">
                                            {roster.members.length === 0 && (
                                                <li className="p-4 text-sm text-muted-foreground">
                                                    No swimmers added.
                                                </li>
                                            )}
                                            {roster.members.map(
                                                (member, index) => (
                                                    <li
                                                        key={member.id}
                                                        className="flex items-center gap-3 p-3"
                                                    >
                                                        <span className="w-5 text-sm text-muted-foreground">
                                                            {index + 1}.
                                                        </span>
                                                        <div className="min-w-0 flex-1">
                                                            <p className="truncate text-sm font-medium">
                                                                {member.name}
                                                            </p>
                                                            <p className="text-xs text-muted-foreground">
                                                                {
                                                                    member.entry_count
                                                                }{' '}
                                                                event{' '}
                                                                {member.entry_count ===
                                                                1
                                                                    ? 'entry'
                                                                    : 'entries'}
                                                            </p>
                                                        </div>
                                                        <Badge
                                                            variant={
                                                                member.eligible &&
                                                                member.medically_cleared
                                                                    ? 'secondary'
                                                                    : 'outline'
                                                            }
                                                        >
                                                            {member.eligible &&
                                                            member.medically_cleared
                                                                ? 'Competition ready'
                                                                : 'Qualification pending'}
                                                        </Badge>
                                                        {roster.can_manage && (
                                                            <Button
                                                                size="icon"
                                                                variant="ghost"
                                                                aria-label={`Remove ${member.name}`}
                                                                onClick={() =>
                                                                    router.delete(
                                                                        `/sport-rosters/members/${member.id}`,
                                                                        {
                                                                            preserveScroll: true,
                                                                        },
                                                                    )
                                                                }
                                                            >
                                                                <Trash2 />
                                                            </Button>
                                                        )}
                                                    </li>
                                                ),
                                            )}
                                        </ol>
                                        {roster.can_manage && (
                                            <Button
                                                variant="outline"
                                                disabled={
                                                    full ||
                                                    roster.candidates.length ===
                                                        0
                                                }
                                                onClick={() =>
                                                    setAdding(roster)
                                                }
                                            >
                                                <Plus />
                                                Add swimmer
                                            </Button>
                                        )}
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                )}
            </div>
            {adding && (
                <AddSwimmerDialog
                    roster={adding}
                    close={() => setAdding(null)}
                />
            )}
        </>
    );
}

SwimmingRosters.layout = {
    breadcrumbs: [
        { title: 'Entries', href: '/entries' },
        { title: 'Swimming rosters', href: '/swimming/rosters' },
    ],
};
