import { Head, router } from '@inertiajs/react';
import { CheckCircle2, Clock3, Printer, Utensils } from 'lucide-react';
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
    const [selectedDay, setSelectedDay] = useState('today');
    const meetYear = today.slice(0, 4);
    const meetDates = Array.from({ length: 6 }, (_, index) => {
        const day = index + 3;

        return {
            label: String(day),
            date: `${meetYear}-09-${String(day).padStart(2, '0')}`,
        };
    });
    const selectedDate = selectedDay === 'today' ? today : selectedDay;
    const visible =
        selectedDay === 'all'
            ? meals
            : meals.filter((meal) => meal.date === selectedDate);

    const printMealStub = () => window.print();

    return (
        <>
            <Head title="Meals" />
            <style>{`
                @page { size: A4 portrait; margin: 0; }
                @media print {
                    html, body {
                        width: 210mm; height: 297mm; margin: 0 !important;
                        padding: 0 !important; background: #fff !important;
                    }
                    body * { visibility: hidden; }
                    #printable-meal-stub, #printable-meal-stub * { visibility: visible; }
                    #printable-meal-stub {
                        position: fixed; inset: 0; display: flex !important;
                        width: 210mm; height: 297mm; box-sizing: border-box;
                        overflow: hidden;
                        print-color-adjust: exact; -webkit-print-color-adjust: exact;
                    }
                }
            `}</style>
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 print:hidden">
                <PageHeader
                    title="Meals"
                    description={`${todayLabel} · ${person.meet}`}
                />
                <Button
                    type="button"
                    variant="outline"
                    className="w-full sm:w-fit"
                    onClick={printMealStub}
                >
                    <Printer className="mr-2 size-4" />
                    Print / Save as PDF
                </Button>
                <section className="rounded-xl border p-4">
                    <p className="text-xl font-bold">{person.name}</p>
                    <p className="text-sm text-muted-foreground">
                        {person.role}
                        {person.sport ? ` · ${person.sport}` : ''}
                    </p>
                </section>
                <div
                    className="flex flex-wrap gap-2"
                    aria-label="Meal day filter"
                >
                    <Button
                        variant={
                            selectedDay === 'today' ? 'default' : 'outline'
                        }
                        onClick={() => setSelectedDay('today')}
                    >
                        Today
                    </Button>
                    {meetDates.map((meetDate) => (
                        <Button
                            key={meetDate.date}
                            variant={
                                selectedDay === meetDate.date
                                    ? 'default'
                                    : 'outline'
                            }
                            aria-label={`September ${meetDate.label}`}
                            onClick={() => setSelectedDay(meetDate.date)}
                        >
                            {meetDate.label}
                        </Button>
                    ))}
                    <Button
                        variant={selectedDay === 'all' ? 'default' : 'outline'}
                        onClick={() => setSelectedDay('all')}
                    >
                        All
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

            <article
                id="printable-meal-stub"
                className="hidden flex-col bg-white p-[12mm] text-black print:flex"
                aria-label="Printable meal stub"
            >
                <header className="border-b-2 border-black pb-4 text-center">
                    <p className="text-sm font-semibold tracking-[0.2em] uppercase">
                        Food Meal Stub
                    </p>
                    <h1 className="mt-2 text-2xl font-black uppercase">
                        {person.meet}
                    </h1>
                </header>

                <section className="mt-5 grid grid-cols-[1fr_auto] gap-5 rounded-lg border-2 border-black p-4">
                    <div>
                        <p className="text-xs font-semibold tracking-wide uppercase">
                            Personnel
                        </p>
                        <p className="mt-1 text-xl font-black uppercase">
                            {person.name}
                        </p>
                        <p className="mt-1 text-sm uppercase">
                            {person.role}
                            {person.sport ? ` · ${person.sport}` : ''}
                        </p>
                    </div>
                    <div className="text-right text-xs">
                        <p className="font-semibold uppercase">Date printed</p>
                        <p className="mt-1">{todayLabel}</p>
                    </div>
                </section>

                <section className="mt-5 min-h-0 flex-1">
                    <h2 className="mb-3 text-sm font-bold tracking-wide uppercase">
                        Meal Entitlements
                    </h2>
                    <div className="grid grid-cols-2 gap-2">
                        {meals.map((meal) => (
                            <div
                                key={meal.id}
                                className="break-inside-avoid rounded border border-black p-2.5"
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <div>
                                        <p className="text-sm font-black uppercase">
                                            {meal.meal}
                                        </p>
                                        <p className="text-xs">{meal.date}</p>
                                    </div>
                                    <span className="rounded border border-black px-1.5 py-0.5 text-[9px] font-bold uppercase">
                                        {statusLabels[meal.display_status]}
                                    </span>
                                </div>
                                <p className="mt-2 text-[11px]">
                                    {timeLabel(meal.starts_at)} –{' '}
                                    {timeLabel(meal.ends_at)}
                                </p>
                                <p className="mt-0.5 text-[11px]">
                                    {meal.venue ?? 'Venue not assigned'}
                                </p>
                                {meal.consumed_at && (
                                    <p className="mt-1 text-[10px] font-semibold">
                                        Consumed at {meal.consumed_at}
                                    </p>
                                )}
                            </div>
                        ))}
                    </div>
                    {meals.length === 0 && (
                        <p className="rounded border border-dashed border-black p-6 text-center text-sm">
                            No meals configured.
                        </p>
                    )}
                </section>

                <footer className="mt-4 border-t border-black pt-3 text-center text-[10px]">
                    This meal stub is issued to one personnel only and is not
                    transferable.
                </footer>
            </article>
        </>
    );
}
