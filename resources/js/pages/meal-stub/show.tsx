import { Head, router } from '@inertiajs/react';
import { CheckCircle2, Clock3, Utensils } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type MealStatus = 'available' | 'consumed' | 'expired' | 'upcoming';
type Meal = {
    id: number;
    date: string;
    meal: string;
    starts_at: string | null;
    ends_at: string | null;
    venue: string | null;
    display_status: MealStatus;
    consumed_at: string | null;
};
const statusLabels: Record<MealStatus, string> = {
    available: 'AVAILABLE',
    consumed: 'CONSUMED',
    expired: 'EXPIRED',
    upcoming: 'Not yet available',
};

function timeLabel(value: string | null): string {
    if (!value) return '—';
    const [hours, minutes] = value.split(':').map(Number);
    return new Intl.DateTimeFormat(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(2000, 0, 1, hours, minutes));
}

export default function Meals({
    person,
    today,
    todayLabel,
    meals,
}: {
    person: { name: string; role: string; sport: string | null; meet: string };
    today: string;
    todayLabel: string;
    meals: Meal[];
}) {
    const [allDays, setAllDays] = useState(false);
    const visible = allDays
        ? meals
        : meals.filter((meal) => meal.date === today);

    return (
        <>
            <Head title="Meals" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4">
                <PageHeader
                    title="Meals"
                    description={`${todayLabel} · ${person.meet}`}
                />
                <section className="rounded-xl border p-4">
                    <p className="text-xl font-bold">{person.name}</p>
                    <p className="text-sm text-muted-foreground">
                        {person.role}
                        {person.sport ? ` · ${person.sport}` : ''}
                    </p>
                </section>
                <div className="flex gap-2" aria-label="Meal day filter">
                    <Button
                        variant={!allDays ? 'default' : 'outline'}
                        onClick={() => setAllDays(false)}
                    >
                        Today
                    </Button>
                    <Button
                        variant={allDays ? 'default' : 'outline'}
                        onClick={() => setAllDays(true)}
                    >
                        All Days
                    </Button>
                </div>
                <div className="grid gap-4">
                    {visible.map((meal) => {
                        const available = meal.display_status === 'available';
                        const consumed = meal.display_status === 'consumed';
                        return (
                            <section
                                key={meal.id}
                                className="rounded-2xl border p-5 shadow-sm"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-lg font-black uppercase">
                                            {meal.meal}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {meal.date}
                                        </p>
                                    </div>
                                    <Badge
                                        variant={
                                            consumed
                                                ? 'secondary'
                                                : available
                                                  ? 'default'
                                                  : 'outline'
                                        }
                                    >
                                        {statusLabels[meal.display_status]}
                                    </Badge>
                                </div>
                                <div className="mt-4 space-y-1 text-sm">
                                    <p>
                                        <Clock3 className="mr-2 inline size-4" />
                                        {timeLabel(meal.starts_at)} –{' '}
                                        {timeLabel(meal.ends_at)}
                                    </p>
                                    <p>
                                        <Utensils className="mr-2 inline size-4" />
                                        {meal.venue ?? 'Venue not assigned'}
                                    </p>
                                </div>
                                {consumed ? (
                                    <p className="mt-5 font-semibold text-emerald-700">
                                        <CheckCircle2 className="mr-2 inline size-5" />
                                        CONSUMED
                                        {meal.consumed_at
                                            ? ` · Consumed at ${meal.consumed_at}`
                                            : ''}
                                    </p>
                                ) : available ? (
                                    <ConfirmDialog
                                        trigger={
                                            <Button className="mt-5 h-12 w-full text-base">
                                                Mark as Consumed
                                            </Button>
                                        }
                                        title="Mark this meal as consumed?"
                                        description={`${meal.meal} · ${meal.date} · ${timeLabel(meal.starts_at)}–${timeLabel(meal.ends_at)}. Once confirmed, this meal cannot be used again.`}
                                        confirmLabel="Yes, Mark as Consumed"
                                        onConfirm={() =>
                                            router.post(
                                                `/meal-stub/${meal.id}/consume`,
                                                {},
                                                { preserveScroll: true },
                                            )
                                        }
                                    />
                                ) : (
                                    <p className="mt-5 text-sm font-medium text-muted-foreground">
                                        {meal.display_status === 'upcoming'
                                            ? `Not yet available · Opens at ${timeLabel(meal.starts_at)}`
                                            : 'EXPIRED · Serving period ended'}
                                    </p>
                                )}
                            </section>
                        );
                    })}
                    {visible.length === 0 && (
                        <p className="rounded-xl border border-dashed p-8 text-center text-muted-foreground">
                            No meals configured for this day.
                        </p>
                    )}
                </div>
            </div>
        </>
    );
}
