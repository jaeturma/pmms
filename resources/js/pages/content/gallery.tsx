import { Head, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Item = {
    id: number;
    caption: string | null;
    capture_date: string;
    status: string;
    sport: string | null;
    event: string | null;
    uploader: string | null;
    image_url: string;
    rejection_reason: string | null;
};
type Scope = { id: number; name: string };
type Props = {
    items: { data: Item[] };
    meetSports: Scope[];
    canReview: boolean;
    limits: { daily_candidate_max: number; daily_public_max: number };
};

export default function GalleryManagement({
    items,
    meetSports,
    canReview,
    limits,
}: Props) {
    const [selected, setSelected] = useState<number[]>([]);
    const form = useForm<{
        meet_sport_id: string;
        capture_date: string;
        caption: string;
        photos: File[];
    }>({
        meet_sport_id: meetSports[0] ? String(meetSports[0].id) : '',
        capture_date: new Date().toISOString().slice(0, 10),
        caption: '',
        photos: [],
    });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post('/content/gallery', {
            forceFormData: true,
            onSuccess: () => form.reset('caption', 'photos'),
        });
    };
    const toggle = (id: number) =>
        setSelected((current) =>
            current.includes(id)
                ? current.filter((value) => value !== id)
                : [...current, id],
        );
    return (
        <>
            <Head title="Gallery Management" />
            <div className="space-y-6">
                <PageHeader
                    title="Gallery"
                    description={`Candidate guidance: up to ${limits.daily_candidate_max} uploads; public selection: up to ${limits.daily_public_max} per sport/day.`}
                />
                <form
                    onSubmit={submit}
                    className="grid gap-4 rounded-xl border bg-card p-5 sm:grid-cols-2"
                >
                    <div>
                        <Label>Assigned sport</Label>
                        <select
                            className="mt-2 h-10 w-full rounded-md border bg-background px-3"
                            value={form.data.meet_sport_id}
                            onChange={(e) =>
                                form.setData('meet_sport_id', e.target.value)
                            }
                            required
                        >
                            {meetSports.map((scope) => (
                                <option key={scope.id} value={scope.id}>
                                    {scope.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label>Capture date</Label>
                        <Input
                            className="mt-2"
                            type="date"
                            value={form.data.capture_date}
                            onChange={(e) =>
                                form.setData('capture_date', e.target.value)
                            }
                            required
                        />
                    </div>
                    <div className="sm:col-span-2">
                        <Label>Suggested caption</Label>
                        <Input
                            className="mt-2"
                            value={form.data.caption}
                            onChange={(e) =>
                                form.setData('caption', e.target.value)
                            }
                        />
                    </div>
                    <div className="sm:col-span-2">
                        <Label>Photos</Label>
                        <Input
                            className="mt-2"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            onChange={(e) =>
                                form.setData(
                                    'photos',
                                    Array.from(e.target.files ?? []),
                                )
                            }
                            required
                        />
                    </div>
                    <Button disabled={form.processing || !meetSports.length}>
                        Upload candidates
                    </Button>
                </form>
                {canReview && (
                    <div className="flex items-center justify-between rounded-xl border bg-card p-4">
                        <span className="text-sm font-medium">
                            Selected: {selected.length} /{' '}
                            {limits.daily_public_max}
                        </span>
                        <Button
                            disabled={!selected.length}
                            onClick={() =>
                                router.patch(
                                    '/content/gallery/publish',
                                    { ids: selected },
                                    { onSuccess: () => setSelected([]) },
                                )
                            }
                        >
                            Publish selected
                        </Button>
                    </div>
                )}
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {items.data.map((item) => (
                        <article
                            key={item.id}
                            className="overflow-hidden rounded-xl border bg-card"
                        >
                            <div className="relative">
                                <img
                                    src={item.image_url}
                                    alt={
                                        item.caption ??
                                        `${item.sport ?? 'Meet'} gallery candidate`
                                    }
                                    className="aspect-[4/3] w-full object-cover"
                                    loading="lazy"
                                />
                                {canReview &&
                                    ['submitted', 'approved'].includes(
                                        item.status,
                                    ) && (
                                        <input
                                            type="checkbox"
                                            checked={selected.includes(item.id)}
                                            onChange={() => toggle(item.id)}
                                            className="absolute top-3 left-3 size-5"
                                            aria-label={`Select photo ${item.id}`}
                                        />
                                    )}
                            </div>
                            <div className="space-y-2 p-4">
                                <p className="font-semibold">
                                    {item.sport}
                                    {item.event ? ` · ${item.event}` : ''}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {item.capture_date} · {item.uploader} ·{' '}
                                    {item.status}
                                </p>
                                <p className="text-sm">{item.caption}</p>
                                {canReview && item.status === 'submitted' && (
                                    <div className="flex gap-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() =>
                                                router.patch(
                                                    `/content/gallery/${item.id}/review`,
                                                    {
                                                        status: 'approved',
                                                        caption: item.caption,
                                                    },
                                                )
                                            }
                                        >
                                            Approve
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            onClick={() =>
                                                router.patch(
                                                    `/content/gallery/${item.id}/review`,
                                                    {
                                                        status: 'rejected',
                                                        rejection_reason:
                                                            'Not selected for publication',
                                                    },
                                                )
                                            }
                                        >
                                            Reject
                                        </Button>
                                    </div>
                                )}
                            </div>
                        </article>
                    ))}
                </div>
            </div>
        </>
    );
}
