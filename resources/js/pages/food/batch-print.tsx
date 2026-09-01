import { Head, usePage } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';

type Meal = {
    id: number;
    date: string;
    meal: string;
    starts_at: string | null;
    ends_at: string | null;
};

type Person = {
    id: number;
    name: string;
    role: string;
    sport: string | null;
    twg_group: string | null;
    meals: Meal[];
};

function dateLabel(value: string): string {
    const [year, , day] = value.split('-');

    return `Sept. ${Number(day)}, ${year}`;
}

export default function BatchPrint({
    meet,
    personnel,
}: {
    meet: string;
    personnel: Person[];
}) {
    const { branding } = usePage().props;

    return (
        <>
            <Head title="Batch Meal Stubs" />
            <style>{`
                @page { size: A4 portrait; margin: 0; }

                @media print {
                    html, body {
                        margin: 0 !important;
                        padding: 0 !important;
                        background: white !important;
                    }
                    body * { visibility: hidden; }
                    #batch-meal-stubs, #batch-meal-stubs * { visibility: visible; }
                    #batch-meal-stubs { position: absolute; inset: 0; }
                    .person-meal-sheet {
                        display: grid !important;
                        width: 210mm;
                        height: 297mm;
                        box-sizing: border-box;
                        grid-template-columns: repeat(4, minmax(0, 1fr));
                        grid-template-rows: repeat(7, minmax(0, 1fr));
                        gap: 1.5mm;
                        padding: 6mm;
                        overflow: hidden;
                        break-after: page;
                        page-break-after: always;
                        print-color-adjust: exact;
                        -webkit-print-color-adjust: exact;
                    }
                    .person-meal-sheet:last-child {
                        break-after: auto;
                        page-break-after: auto;
                    }
                }
            `}</style>

            <div className="mx-auto flex max-w-4xl items-center justify-between gap-3 p-4 print:hidden">
                <div>
                    <h1 className="text-xl font-bold">Batch Meal Stubs</h1>
                    <p className="text-sm text-muted-foreground">
                        {personnel.length} personnel · {meet} · one A4 page per
                        personnel
                    </p>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" asChild>
                        <a href="/food/distribution">
                            <ArrowLeft /> Back
                        </a>
                    </Button>
                    <Button onClick={() => window.print()} disabled={!personnel.length}>
                        <Printer /> Print Batch
                    </Button>
                </div>
            </div>

            {personnel.length === 0 && (
                <p className="mx-auto max-w-4xl rounded-xl border border-dashed p-10 text-center text-muted-foreground print:hidden">
                    No eligible personnel match the selected filters.
                </p>
            )}

            <main id="batch-meal-stubs" className="bg-white">
                {personnel.map((person) => (
                    <article
                        key={person.id}
                        className="person-meal-sheet hidden bg-white text-black print:grid"
                        aria-label={`Meal stubs for ${person.name}`}
                    >
                        {person.meals.map((meal) => (
                            <section
                                key={meal.id}
                                className="flex min-h-0 break-inside-avoid flex-col justify-between overflow-hidden border border-dashed border-black p-[2mm] text-center"
                            >
                                <div className="mb-1 text-center">
                                    {branding.logoUrl ? (
                                        <img
                                            src={branding.logoUrl}
                                            alt="Application logo"
                                            className="mx-auto size-[6mm] object-contain"
                                        />
                                    ) : (
                                        <AppLogoIcon
                                            className="mx-auto size-[6mm] fill-black"
                                            aria-label="Application logo"
                                        />
                                    )}
                                    <p className="mt-0.5 text-[7px] leading-none font-bold">
                                        DdO PAA 2026
                                    </p>
                                </div>
                                <p className="line-clamp-2 text-[9px] leading-tight font-black uppercase">
                                    {person.name}
                                </p>
                                <div className="my-1 border-y border-black py-1">
                                    <p
                                        className="text-[14px] leading-none uppercase"
                                        style={{
                                            fontFamily:
                                                'Arial Black, Arial, sans-serif',
                                            fontWeight: 900,
                                        }}
                                    >
                                        {meal.meal}
                                    </p>
                                    <p className="mt-1 text-[12px] font-black uppercase">
                                        {dateLabel(meal.date)}
                                    </p>
                                </div>
                                <div className="text-[8px] leading-tight">
                                    <p className="font-black uppercase">
                                        CHAIRPERSON
                                    </p>
                                    <p className="mt-0.5 line-clamp-1 uppercase">
                                        {person.sport ?? person.twg_group ?? person.role}
                                    </p>
                                </div>
                            </section>
                        ))}
                    </article>
                ))}
            </main>
        </>
    );
}
