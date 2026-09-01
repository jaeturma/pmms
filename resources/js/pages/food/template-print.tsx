import { Head, usePage } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import { useEffect } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';

type Meal = {
    id: number;
    date: string;
    meal: string;
    starts_at: string | null;
    ends_at: string | null;
};

function dateLabel(value: string): string {
    const [year, , day] = value.split('-');

    return `Sept. ${Number(day)}, ${year}`;
}

export default function TemplatePrint({
    meet,
    meals,
}: {
    meet: string;
    meals: Meal[];
}) {
    const { branding } = usePage().props;

    useEffect(() => {
        const timer = window.setTimeout(() => window.print(), 300);

        return () => window.clearTimeout(timer);
    }, []);

    return (
        <>
            <Head title={`Blank Meal Stubs — ${meet}`} />
            <style>{`
                @page { size: A4 portrait; margin: 0; }
                @media print {
                    html, body { margin: 0 !important; padding: 0 !important; background: white !important; }
                    body * { visibility: hidden; }
                    #blank-meal-stubs, #blank-meal-stubs * { visibility: visible; }
                    #blank-meal-stubs { position: fixed; inset: 0; display: grid !important; }
                }
                #blank-meal-stubs {
                    width: 210mm;
                    height: 297mm;
                    box-sizing: border-box;
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                    grid-template-rows: repeat(7, minmax(0, 1fr));
                    gap: 1.5mm;
                    padding: 6mm;
                    overflow: hidden;
                    print-color-adjust: exact;
                    -webkit-print-color-adjust: exact;
                }
            `}</style>

            <div className="flex items-center justify-center gap-2 p-4 print:hidden">
                <Button variant="outline" asChild>
                    <a href="/food"><ArrowLeft /> Back</a>
                </Button>
                <Button onClick={() => window.print()} disabled={!meals.length}>
                    <Printer /> Print Again
                </Button>
            </div>

            <main id="blank-meal-stubs" className="mx-auto hidden bg-white text-black print:grid">
                {meals.map((meal) => (
                    <section
                        key={meal.id}
                        className="flex min-h-0 break-inside-avoid flex-col justify-between overflow-hidden border border-dashed border-black p-[2mm] text-center"
                    >
                        <div className="text-center">
                            {branding.logoUrl ? (
                                <img
                                    src={branding.logoUrl}
                                    alt="Application logo"
                                    className="mx-auto size-[7mm] object-contain"
                                />
                            ) : (
                                <AppLogoIcon
                                    className="mx-auto size-[7mm] fill-black"
                                    aria-label="Application logo"
                                />
                            )}
                            <p className="mt-0.5 text-[7px] leading-none font-bold">
                                DdO PAA 2026
                            </p>
                        </div>
                        <div className="my-1 border-y border-black py-1">
                            <p
                                className="text-[14px] leading-none uppercase"
                                style={{
                                    fontFamily: 'Arial Black, Arial, sans-serif',
                                    fontWeight: 900,
                                }}
                            >
                                {meal.meal}
                            </p>
                            <p className="mt-1 text-[12px] font-black uppercase">
                                {dateLabel(meal.date)}
                            </p>
                        </div>
                        <p className="text-[9px] leading-tight font-black uppercase">
                            CHAIRPERSON
                        </p>
                    </section>
                ))}
            </main>
        </>
    );
}
