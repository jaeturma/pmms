import { Head, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

type Item = {
    id: number;
    question: string;
    answer: string;
    category: string;
    status: string;
    display_order: number;
};
type Props = { items: { data: Item[] } };
export default function FaqManagement({ items }: Props) {
    const form = useForm({
        question: '',
        answer: '',
        category: 'General',
        display_order: 0,
        is_featured: false,
    });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post('/content/faq', { onSuccess: () => form.reset() });
    };
    return (
        <>
            <Head title="FAQ Management" />
            <div className="space-y-6">
                <PageHeader
                    title="FAQ"
                    description="Manage categorized public answers and display order."
                />
                <form
                    onSubmit={submit}
                    className="grid gap-3 rounded-xl border bg-card p-5"
                >
                    <Input
                        value={form.data.question}
                        onChange={(e) =>
                            form.setData('question', e.target.value)
                        }
                        placeholder="Question"
                        required
                    />
                    <Textarea
                        value={form.data.answer}
                        onChange={(e) => form.setData('answer', e.target.value)}
                        placeholder="Answer"
                        required
                    />
                    <Input
                        value={form.data.category}
                        onChange={(e) =>
                            form.setData('category', e.target.value)
                        }
                        placeholder="Category"
                        required
                    />
                    <Button disabled={form.processing}>Save draft</Button>
                </form>
                <div className="space-y-3">
                    {items.data.map((item) => (
                        <article
                            key={item.id}
                            className="rounded-xl border bg-card p-5"
                        >
                            <h2 className="font-semibold">{item.question}</h2>
                            <p className="text-xs text-muted-foreground">
                                {item.category} · {item.status}
                            </p>
                            <p className="mt-2 text-sm">{item.answer}</p>
                            <div className="mt-3 flex gap-2">
                                {item.status !== 'published' && (
                                    <Button
                                        size="sm"
                                        onClick={() =>
                                            router.patch(
                                                `/content/faq/${item.id}/status`,
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
                                            `/content/faq/${item.id}/status`,
                                            { status: 'archived' },
                                        )
                                    }
                                >
                                    Archive
                                </Button>
                            </div>
                        </article>
                    ))}
                </div>
            </div>
        </>
    );
}
