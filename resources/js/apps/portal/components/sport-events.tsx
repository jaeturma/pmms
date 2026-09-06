import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

export function PortalSportEvents({
    events,
}: {
    events: Array<{
        id: number;
        label: string;
        url: string;
        medal_awarded?: boolean;
    }>;
}) {
    if (!events.length) {
        return (
            <p className="text-sm text-[var(--portal-muted-foreground)]">
                No sports events configured yet.
            </p>
        );
    }

    return (
        <ul className="grid gap-3 sm:grid-cols-2">
            {events.map((event) => (
                <li key={event.id}>
                    <Link
                        href={event.url}
                        className={
                            event.medal_awarded
                                ? 'flex h-full items-center justify-between gap-3 rounded-[var(--portal-radius)] border border-transparent bg-[#800000] p-4 text-white hover:bg-[#990000]'
                                : 'flex h-full items-center justify-between gap-3 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4 hover:border-[var(--portal-accent)]'
                        }
                    >
                        <span>
                            <span className="block font-semibold">
                                {event.label}
                            </span>
                            <span
                                className={
                                    event.medal_awarded
                                        ? 'text-sm text-white/75'
                                        : 'text-sm text-[var(--portal-muted-foreground)]'
                                }
                            >
                                {event.medal_awarded
                                    ? 'Medals awarded · view results'
                                    : 'Team standing and results'}
                            </span>
                        </span>
                        <ArrowRight className="size-4 shrink-0" />
                    </Link>
                </li>
            ))}
        </ul>
    );
}
