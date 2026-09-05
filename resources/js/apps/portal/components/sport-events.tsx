import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

export function PortalSportEvents({
    events,
}: {
    events: Array<{ id: number; label: string; url: string }>;
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
                        className="flex h-full items-center justify-between gap-3 rounded-[var(--portal-radius)] border border-[var(--portal-border)] bg-[var(--portal-surface)] p-4 hover:border-[var(--portal-accent)]"
                    >
                        <span>
                            <span className="block font-semibold">
                                {event.label}
                            </span>
                            <span className="text-sm text-[var(--portal-muted-foreground)]">
                                Team standing and results
                            </span>
                        </span>
                        <ArrowRight className="size-4 shrink-0" />
                    </Link>
                </li>
            ))}
        </ul>
    );
}
