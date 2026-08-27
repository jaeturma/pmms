import { Head, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

type Item = {
    id: number;
    title: string;
    slug: string;
    summary: string | null;
    body: string;
    status: string;
    published_at: string | null;
};
type Props = { items: { data: Item[] } };

export default function NewsManagement({ items }: Props) {
    const form = useForm<{
        title: string;
        slug: string;
        summary: string;
        body: string;
        is_featured: boolean;
        featured_image: File | null;
    }>({
        title: '',
        slug: '',
        summary: '',
        body: '',
        is_featured: false,
        featured_image: null,
    });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/content/news', {
            forceFormData: true,
            onSuccess: () => form.reset(),
        });
    };
    return (
        <>
            <Head title="News Management" />
            <div className="space-y-6">
                <PageHeader
                    title="News"
                    description="Create, schedule, publish and archive public stories."
                />
                <form
                    onSubmit={submit}
                    className="grid gap-3 rounded-xl border bg-card p-5"
                >
                    <Input
                        value={form.data.title}
                        onChange={(e) => form.setData('title', e.target.value)}
                        placeholder="Article title"
                        required
                    />
                    <Input
                        value={form.data.slug}
                        onChange={(e) => form.setData('slug', e.target.value)}
                        placeholder="Optional slug"
                    />
                    <Input
                        value={form.data.summary}
                        onChange={(e) =>
                            form.setData('summary', e.target.value)
                        }
                        placeholder="Summary"
                    />
                    <Textarea
                        value={form.data.body}
                        onChange={(e) => form.setData('body', e.target.value)}
                        placeholder="Article body"
                        required
                        rows={7}
                    />
                    <Input
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        onChange={(e) =>
                            form.setData(
                                'featured_image',
                                e.target.files?.[0] ?? null,
                            )
                        }
                    />
                    <Button disabled={form.processing}>Save draft</Button>
                </form>
                <div className="space-y-3">
                    {items.data.map((item) => (
                        <article
                            key={item.id}
                            className="rounded-xl border bg-card p-5"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 className="font-semibold">
                                        {item.title}
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        /{item.slug} · {item.status}
                                    </p>
                                </div>
                                <div className="flex gap-2">
                                    {item.status !== 'published' && (
                                        <Button
                                            size="sm"
                                            onClick={() =>
                                                router.patch(
                                                    `/content/news/${item.id}/status`,
                                                    { status: 'published' },
                                                )
                                            }
                                        >
                                            Publish
                                        </Button>
                                    )}
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() =>
                                            router.patch(
                                                `/content/news/${item.id}/status`,
                                                { status: 'archived' },
                                            )
                                        }
                                    >
                                        Archive
                                    </Button>
                                </div>
                            </div>
                            <p className="mt-3 text-sm">{item.summary}</p>
                        </article>
                    ))}
                </div>
            </div>
        </>
    );
}
