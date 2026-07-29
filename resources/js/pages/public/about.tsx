import { Head, usePage } from '@inertiajs/react';
import {
    Building2,
    CalendarDays,
    Dumbbell,
    MapPin,
    School,
    Users,
} from 'lucide-react';
import { PublicPageHero } from '@/components/public-page-hero';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { pluralizeAreaLabel } from '@/lib/utils';

type Props = {
    meet: {
        id: number;
        name: string;
        school_year: string;
        starts_at: string;
        ends_at: string;
        venue: string | null;
        status_label: string;
    };
    municipalityCount: number;
    schoolCount: number;
    sportCount: number;
};

const divisionTypeLabel: Record<'city' | 'province', string> = {
    city: 'City',
    province: 'Province',
};

export default function PublicAbout({
    meet,
    municipalityCount,
    schoolCount,
    sportCount,
}: Props) {
    const { division } = usePage().props;

    return (
        <>
            <Head title={`About — ${meet.name}`} />
            <div className="flex flex-col gap-6 sm:gap-8">
                <PublicPageHero
                    title="About"
                    description="The Division running this meet, and the meet itself, in real numbers."
                    meta={
                        <div className="flex flex-wrap items-center gap-2">
                            <span>{meet.name}</span>
                            <Badge
                                variant="secondary"
                                className="bg-white/15 text-white"
                            >
                                {meet.status_label}
                            </Badge>
                        </div>
                    }
                />

                <div className="rounded-xl border p-5">
                    <h2 className="font-semibold">{division.name}</h2>
                    <dl className="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                        <div className="flex items-center gap-2">
                            <Building2
                                aria-hidden="true"
                                className="size-4 shrink-0 text-muted-foreground"
                            />
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Division type
                                </dt>
                                <dd>{divisionTypeLabel[division.type]}</dd>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Users
                                aria-hidden="true"
                                className="size-4 shrink-0 text-muted-foreground"
                            />
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Competing area unit
                                </dt>
                                <dd>{division.areaLabel}</dd>
                            </div>
                        </div>
                    </dl>
                </div>

                <div className="rounded-xl border p-5">
                    <h2 className="font-semibold">{meet.name}</h2>
                    <dl className="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                        <div className="flex items-center gap-2">
                            <CalendarDays
                                aria-hidden="true"
                                className="size-4 shrink-0 text-muted-foreground"
                            />
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Dates
                                </dt>
                                <dd>
                                    {meet.starts_at} – {meet.ends_at} (SY{' '}
                                    {meet.school_year})
                                </dd>
                            </div>
                        </div>
                        {meet.venue && (
                            <div className="flex items-center gap-2">
                                <MapPin
                                    aria-hidden="true"
                                    className="size-4 shrink-0 text-muted-foreground"
                                />
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Venue
                                    </dt>
                                    <dd>{meet.venue}</dd>
                                </div>
                            </div>
                        )}
                    </dl>
                </div>

                <div className="grid animate-card-in grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-5">
                    <StatCard
                        label={`Competing ${pluralizeAreaLabel(division.areaLabel).toLowerCase()}`}
                        value={municipalityCount}
                        icon={Users}
                        tone="primary"
                    />
                    <StatCard
                        label="Participating schools"
                        value={schoolCount}
                        icon={School}
                        tone="primary"
                    />
                    <StatCard
                        label="Sports contested"
                        value={sportCount}
                        icon={Dumbbell}
                        tone="primary"
                    />
                </div>
            </div>
        </>
    );
}
