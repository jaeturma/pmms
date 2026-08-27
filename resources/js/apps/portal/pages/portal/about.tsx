import { Head, usePage } from '@inertiajs/react';
import {
    Award,
    Flame,
    HeartHandshake,
    Medal,
    School,
    Shield,
    Sparkles,
    Target,
    Trophy,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { PortalHero } from '@/apps/portal/components/hero';
import { PortalTorchIcon } from '@/apps/portal/components/torch-icon';
import type { PortalMeetSummary } from '@/apps/portal/types';

type Props = {
    meet: PortalMeetSummary;
    municipalityCount: number;
    schoolCount: number;
    sportCount: number;
};

type PortalAboutPageProps = {
    division: { name: string; areaLabel: string; heroIconUrl: string | null };
};

const values: { label: string; icon: LucideIcon }[] = [
    { label: 'Passion', icon: Flame },
    { label: 'Determination', icon: Target },
    { label: 'Unity', icon: Users },
    { label: 'Sportsmanship', icon: HeartHandshake },
    { label: 'Excellence', icon: Award },
    { label: 'Leadership', icon: Shield },
    { label: 'Teamwork', icon: Users },
    { label: 'Camaraderie', icon: HeartHandshake },
    { label: 'Discipline', icon: Medal },
    { label: 'Perseverance', icon: Sparkles },
];

function Emblem({
    src,
    className = '',
}: {
    src: string | null;
    className?: string;
}) {
    if (src) {
        return (
            <img
                src={src}
                alt="DdOPAA torch emblem"
                className={`h-auto w-full object-contain ${className}`}
            />
        );
    }

    return (
        <PortalTorchIcon
            className={`h-auto w-full text-[var(--portal-ink)] ${className}`}
        />
    );
}

export default function PortalAbout({
    meet,
    municipalityCount,
    schoolCount,
    sportCount,
}: Props) {
    const { props } = usePage<PortalAboutPageProps>();
    const emblemUrl = props.division.heroIconUrl;

    return (
        <>
            <Head title={`About DdOPAA — ${meet.name}`} />
            <div className="flex flex-col gap-10 sm:gap-14">
                <PortalHero
                    title="About DdOPAA"
                    description="Davao de Oro Provincial Athletic Association — the story, identity, and symbolism behind the DdOPAA emblem."
                    eyebrow={meet.name}
                />

                <section
                    aria-labelledby="emblem-heading"
                    className="overflow-hidden rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)]"
                >
                    <div
                        aria-hidden="true"
                        className="h-2 bg-[linear-gradient(90deg,var(--portal-accent)_0_25%,var(--portal-maroon)_25%_50%,var(--portal-ink)_50%_75%,var(--portal-accent)_75%)]"
                    />
                    <div className="grid items-center gap-8 p-6 sm:p-10 lg:grid-cols-[minmax(16rem,0.8fr)_1.2fr] lg:p-14">
                        <figure className="mx-auto w-full max-w-xs">
                            <div className="rounded-full bg-[var(--portal-accent-soft)] p-8 shadow-[0_0_4rem_color-mix(in_oklab,var(--portal-accent)_25%,transparent)]">
                                <Emblem src={emblemUrl} />
                            </div>
                            <figcaption className="mt-4 text-center text-sm text-[var(--portal-muted-foreground)]">
                                Davao de Oro Provincial Athletic Association
                            </figcaption>
                        </figure>

                        <div>
                            <p className="text-sm font-bold tracking-[0.2em] text-[var(--portal-accent)] uppercase">
                                More than a symbol
                            </p>
                            <h2
                                id="emblem-heading"
                                className="mt-2 text-3xl font-extrabold tracking-tight text-[var(--portal-ink)] sm:text-4xl"
                            >
                                The DdOPAA Emblem
                            </h2>
                            <div className="my-6 flex flex-wrap items-center gap-3 text-xl font-black sm:text-3xl">
                                <span className="rounded-lg bg-[var(--portal-accent-soft)] px-4 py-2 text-[var(--portal-ink)]">
                                    DDO
                                </span>
                                <span aria-label="plus">+</span>
                                <span className="rounded-lg bg-[var(--portal-maroon-soft)] px-4 py-2 text-[var(--portal-maroon)]">
                                    PAA
                                </span>
                                <span aria-label="equals">=</span>
                                <span className="rounded-lg bg-[var(--portal-ink)] px-4 py-2 text-[var(--portal-ink-foreground)]">
                                    Torch
                                </span>
                            </div>
                            <p className="max-w-2xl text-base leading-8 text-[var(--portal-muted-foreground)] sm:text-lg">
                                DDO represents Davao de Oro, while PAA
                                represents the Provincial Athletic Association.
                                Together, their forms create the DdOPAA Torch—a
                                symbol of the province's passion, the strength
                                of its athletic community, and a shared pursuit
                                of excellence through sports.
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    aria-labelledby="fire-heading"
                    className="grid items-center gap-8 lg:grid-cols-2"
                >
                    <div className="relative mx-auto flex aspect-square w-full max-w-sm items-center justify-center overflow-hidden rounded-full bg-[var(--portal-accent-soft)] p-14">
                        <div
                            aria-hidden="true"
                            className="absolute inset-10 rounded-full bg-[var(--portal-accent)]/20 blur-2xl"
                        />
                        <Flame
                            aria-hidden="true"
                            className="relative h-full w-full fill-[var(--portal-accent)]/25 text-[var(--portal-maroon)]"
                            strokeWidth={1.35}
                        />
                    </div>
                    <div className="border-l-4 border-[var(--portal-accent)] pl-6 sm:pl-8">
                        <p className="text-sm font-bold tracking-[0.2em] text-[var(--portal-maroon)] uppercase">
                            Davao de Oro · DDO
                        </p>
                        <h2
                            id="fire-heading"
                            className="mt-2 text-3xl font-extrabold text-[var(--portal-ink)]"
                        >
                            As the Fire of the Torch
                        </h2>
                        <p className="mt-5 text-base leading-8 text-[var(--portal-muted-foreground)] sm:text-lg">
                            The fire represents the passion, determination, and
                            unity that fuel Davao de Oro's athletes. As a symbol
                            of energy and enlightenment, the DDO flame expresses
                            the province's burning spirit for sportsmanship and
                            its desire to shine in athletics. It is a guiding
                            light, inspiring every athlete to pursue excellence
                            and illuminating the path toward success.
                        </p>
                    </div>
                </section>

                <section
                    aria-labelledby="handle-heading"
                    className="grid items-center gap-8 lg:grid-cols-2"
                >
                    <div className="order-2 border-l-4 border-[var(--portal-maroon)] pl-6 sm:pl-8 lg:order-1 lg:border-r-4 lg:border-l-0 lg:pr-8 lg:pl-0">
                        <p className="text-sm font-bold tracking-[0.2em] text-[var(--portal-maroon)] uppercase">
                            Provincial Athletic Association · PAA
                        </p>
                        <h2
                            id="handle-heading"
                            className="mt-2 text-3xl font-extrabold text-[var(--portal-ink)]"
                        >
                            As the Handle of the Torch
                        </h2>
                        <p className="mt-5 text-base leading-8 text-[var(--portal-muted-foreground)] sm:text-lg">
                            The handle symbolizes the strong foundation and
                            support provided by the Provincial Athletic
                            Association. It represents the structure,
                            leadership, and teamwork that hold the fire aloft
                            and keep it burning steadily. Connecting athletes,
                            organizers, and the community, it reflects a solid
                            and unified commitment to nurturing talent and
                            advancing sportsmanship throughout the province.
                        </p>
                    </div>
                    <div className="order-1 mx-auto flex w-full max-w-sm flex-col items-center rounded-[var(--portal-radius)] bg-[var(--portal-maroon-soft)] p-10 text-center lg:order-2">
                        <span className="text-7xl font-black tracking-tighter text-[var(--portal-maroon)] sm:text-8xl">
                            PAA
                        </span>
                        <div
                            aria-hidden="true"
                            className="mt-4 h-28 w-7 rounded-full bg-[var(--portal-maroon)] shadow-lg"
                        />
                        <p className="mt-5 text-sm font-semibold tracking-widest text-[var(--portal-maroon)] uppercase">
                            Foundation · Leadership · Support
                        </p>
                    </div>
                </section>

                <section
                    aria-labelledby="torch-heading"
                    className="portal-hero-gradient overflow-hidden rounded-[var(--portal-radius)] px-6 py-12 text-center text-[var(--portal-ink-foreground)] sm:px-12 sm:py-16"
                >
                    <div className="relative mx-auto w-28 sm:w-36">
                        <Emblem
                            src={emblemUrl}
                            className="text-[var(--portal-ink-foreground)]"
                        />
                    </div>
                    <h2
                        id="torch-heading"
                        className="mt-7 text-3xl font-extrabold sm:text-4xl"
                    >
                        The Torch as a Whole
                    </h2>
                    <p className="mx-auto mt-6 max-w-4xl text-base leading-8 text-[var(--portal-ink-foreground)]/85 sm:text-lg">
                        The torch represents the unification of purpose and
                        aspiration. A beacon of hope and triumph, it lights the
                        way as athletes push their limits. It captures the
                        essence of the Davao de Oro Provincial Athletic
                        Meet—celebrating athletic excellence while fostering
                        camaraderie, discipline, and perseverance. It reminds
                        everyone of the enduring flame of competition and the
                        shared vision of bringing glory and honor to Davao de
                        Oro.
                    </p>
                </section>

                <section aria-labelledby="values-heading">
                    <div className="text-center">
                        <p className="text-sm font-bold tracking-[0.2em] text-[var(--portal-maroon)] uppercase">
                            The spirit we carry
                        </p>
                        <h2
                            id="values-heading"
                            className="mt-2 text-3xl font-extrabold text-[var(--portal-ink)]"
                        >
                            Values of the DdOPAA Torch
                        </h2>
                    </div>
                    <div className="mt-7 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                        {values.map(({ label, icon: Icon }) => (
                            <div
                                key={label}
                                className="flex min-w-0 flex-col items-center gap-3 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4 text-center"
                            >
                                <span className="portal-icon-badge size-11 bg-[var(--portal-accent-soft)] text-[var(--portal-maroon)]">
                                    <Icon
                                        aria-hidden="true"
                                        className="size-5"
                                    />
                                </span>
                                <span className="text-sm font-bold break-words">
                                    {label}
                                </span>
                            </div>
                        ))}
                    </div>
                </section>

                <section
                    aria-label={`${meet.name} participation`}
                    className="grid grid-cols-1 gap-4 sm:grid-cols-3"
                >
                    {[
                        {
                            value: municipalityCount,
                            label: 'Competing municipalities',
                            icon: Users,
                        },
                        {
                            value: schoolCount,
                            label: 'Participating schools',
                            icon: School,
                        },
                        {
                            value: sportCount,
                            label: 'Sports contested',
                            icon: Trophy,
                        },
                    ].map(({ value, label, icon: Icon }) => (
                        <div
                            key={label}
                            className="flex items-center gap-4 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-5"
                        >
                            <span className="portal-icon-badge size-14 shrink-0 bg-[var(--portal-ink-soft)] text-[var(--portal-ink)]">
                                <Icon aria-hidden="true" className="size-7" />
                            </span>
                            <div>
                                <p className="text-2xl font-bold tabular-nums">
                                    {value}
                                </p>
                                <p className="text-xs text-[var(--portal-muted-foreground)]">
                                    {label}
                                </p>
                            </div>
                        </div>
                    ))}
                </section>
            </div>
        </>
    );
}
