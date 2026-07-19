import { Head, Link } from '@inertiajs/react';
import { IdCard, Printer } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { index as accreditationIndex } from '@/routes/accreditation';
import { index as delegationsIndex } from '@/routes/delegations';

type Card = {
    id: number;
    number: string | null;
    name: string;
    type_label: string;
    detail: string | null;
    school: string;
    photo_url: string | null;
    accredited_on: string;
};

type Props = {
    delegation: {
        id: number;
        school: string;
        meet: string;
        school_year: string;
    };
    cards: Card[];
    generatedAt: string;
};

function initials(name: string): string {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

export default function AccreditationCards({
    delegation,
    cards,
    generatedAt,
}: Props) {
    return (
        <>
            <Head title={`ID cards — ${delegation.school}`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="ID cards"
                    description={`${delegation.school} — ${delegation.meet} (${delegation.school_year})`}
                    actions={
                        <div className="flex gap-2 print:hidden">
                            <Button variant="outline" asChild>
                                <Link href={accreditationIndex(delegation.id)}>
                                    Accreditation
                                </Link>
                            </Button>
                            {cards.length > 0 && (
                                <Button onClick={() => window.print()}>
                                    <Printer />
                                    Print
                                </Button>
                            )}
                        </div>
                    }
                />

                <p className="text-sm text-muted-foreground print:hidden">
                    {cards.length} card{cards.length === 1 ? '' : 's'} ·
                    Generated {generatedAt}
                </p>

                {cards.length === 0 ? (
                    <EmptyState
                        icon={IdCard}
                        title="No accredited members"
                        description="Accredit athletes or personnel first, then print their cards here."
                    />
                ) : (
                    <div className="flex flex-wrap gap-4">
                        {cards.map((card) => (
                            <div
                                key={card.id}
                                className="w-84 shrink-0 break-inside-avoid overflow-hidden rounded-xl border bg-white text-neutral-900 shadow-sm print:shadow-none"
                            >
                                <div className="border-b bg-neutral-900 px-4 py-2 text-white">
                                    <p className="text-sm leading-tight font-semibold">
                                        {delegation.meet}
                                    </p>
                                    <p className="text-xs text-neutral-300">
                                        SY {delegation.school_year} · Official
                                        Accreditation
                                    </p>
                                </div>
                                <div className="flex gap-4 p-4">
                                    {card.photo_url ? (
                                        <img
                                            src={card.photo_url}
                                            alt={`Photo of ${card.name}`}
                                            className="size-24 shrink-0 rounded-lg border object-cover"
                                        />
                                    ) : (
                                        <div className="flex size-24 shrink-0 items-center justify-center rounded-lg border bg-neutral-100 text-2xl font-semibold text-neutral-400">
                                            {initials(card.name)}
                                        </div>
                                    )}
                                    <div className="min-w-0 space-y-1">
                                        <p className="truncate text-base leading-tight font-bold">
                                            {card.name}
                                        </p>
                                        <p className="text-sm font-medium text-neutral-700">
                                            {card.type_label}
                                            {card.detail && (
                                                <>
                                                    <br />
                                                    <span className="font-normal">
                                                        {card.detail}
                                                    </span>
                                                </>
                                            )}
                                        </p>
                                        <p className="truncate text-sm text-neutral-600">
                                            {card.school}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-center justify-between border-t bg-neutral-50 px-4 py-2">
                                    <span className="font-mono text-sm font-semibold">
                                        {card.number}
                                    </span>
                                    <span className="text-xs text-neutral-500">
                                        Accredited {card.accredited_on}
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

AccreditationCards.layout = {
    breadcrumbs: [
        {
            title: 'Delegations',
            href: delegationsIndex(),
        },
    ],
};
