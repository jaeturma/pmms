import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    meet as publicMeet,
    results as publicResults,
    tally as publicTally,
} from '@/routes/public';

type Props = {
    meetId: number;
    active: 'schedule' | 'results' | 'tally';
};

/**
 * Section navigation for the public meet pages.
 */
export function PublicMeetNav({ meetId, active }: Props) {
    const items = [
        { key: 'schedule', label: 'Schedule', href: publicMeet(meetId) },
        { key: 'results', label: 'Results', href: publicResults(meetId) },
        { key: 'tally', label: 'Medal tally', href: publicTally(meetId) },
    ] as const;

    return (
        <nav aria-label="Meet sections" className="flex gap-2 overflow-x-auto">
            {items.map((item) => (
                <Button
                    key={item.key}
                    variant={active === item.key ? 'default' : 'outline'}
                    size="sm"
                    className="shrink-0"
                    aria-current={active === item.key ? 'page' : undefined}
                    asChild
                >
                    <Link href={item.href}>{item.label}</Link>
                </Button>
            ))}
        </nav>
    );
}
