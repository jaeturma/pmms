import { Head, router } from '@inertiajs/react';
import { CheckCircle2, Clock3, Utensils } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Meal = {
    id: number;
    date: string;
    meal: string;
    starts_at: string | null;
    ends_at: string | null;
    venue: string | null;
    status: string;
    display_status: string;
    consumed_at: string | null;
};

export default function MealStub({ person, today, meals }: {
    person: { name: string; role: string; sport: string | null; meet: string };
    today: string;
    meals: Meal[];
}) {
    const [allDays, setAllDays] = useState(false);
    const visible = allDays ? meals : meals.filter((meal) => meal.date === today);

    return <>
        <Head title="Meal Stub" />
        <div className="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4">
            <PageHeader title="Meal Stub" description={person.meet} />
            <section className="rounded-xl border p-4">
                <p className="text-xl font-bold">{person.name}</p>
                <p className="text-sm text-muted-foreground">{person.role}{person.sport ? ` · ${person.sport}` : ''}</p>
            </section>
            <div className="flex gap-2">
                <Button variant={!allDays ? 'default' : 'outline'} onClick={() => setAllDays(false)}>Today</Button>
                <Button variant={allDays ? 'default' : 'outline'} onClick={() => setAllDays(true)}>All Days</Button>
            </div>
            <div className="grid gap-4">
                {visible.map((meal) => {
                    const available = meal.display_status === 'available';
                    return <section key={meal.id} className="rounded-2xl border p-5 shadow-sm">
                        <div className="flex items-start justify-between gap-3">
                            <div><p className="text-lg font-black uppercase">{meal.meal}</p><p className="text-sm text-muted-foreground">{meal.date}</p></div>
                            <Badge variant={meal.status === 'consumed' ? 'secondary' : 'outline'}>{meal.display_status.replace('_', ' ').toUpperCase()}</Badge>
                        </div>
                        <div className="mt-4 space-y-1 text-sm">
                            <p><Clock3 className="mr-2 inline size-4" />{meal.starts_at?.slice(0, 5) ?? '—'} – {meal.ends_at?.slice(0, 5) ?? '—'}</p>
                            <p><Utensils className="mr-2 inline size-4" />{meal.venue ?? 'Venue not assigned'}</p>
                        </div>
                        {meal.status === 'consumed' ? <p className="mt-5 font-semibold text-emerald-700"><CheckCircle2 className="mr-2 inline size-5" />Consumed — {meal.consumed_at}</p> : available ?
                            <ConfirmDialog trigger={<Button className="mt-5 h-12 w-full text-base">Mark as Consumed</Button>} title="Mark this meal as consumed?" description={`${meal.meal} · ${meal.date} · ${meal.starts_at?.slice(0, 5)}–${meal.ends_at?.slice(0, 5)}. Once confirmed, this meal stub cannot be used again.`} confirmLabel="Yes, Mark as Consumed" onConfirm={() => router.post(`/meal-stub/${meal.id}/consume`, {}, { preserveScroll: true })} /> :
                            <p className="mt-5 text-sm font-medium text-muted-foreground">{meal.display_status === 'upcoming' ? `Available at ${meal.starts_at?.slice(0, 5)}` : 'Not Claimed'}</p>}
                    </section>;
                })}
                {visible.length === 0 && <p className="rounded-xl border border-dashed p-8 text-center text-muted-foreground">No meals configured.</p>}
            </div>
        </div>
    </>;
}
