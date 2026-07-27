import { usePage } from '@inertiajs/react';
import { Flag } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

/**
 * The sidebar footer's meet-context card (WP-08-03) — real data
 * (`currentMeet`, shared globally in `HandleInertiaRequests`), not the
 * reference images' fictional "Stronger Together, Champions Forever!"
 * tagline/illustration, which don't correspond to anything in this app.
 * Hidden entirely when there's no current meet or the sidebar is
 * collapsed to icon-only (matches every other sidebar footer element's
 * `group-data-[collapsible=icon]:hidden` convention).
 */
export function SidebarMeetCard() {
    const { currentMeet } = usePage().props;

    if (currentMeet === null) {
        return null;
    }

    return (
        <div className="group-data-[collapsible=icon]:hidden">
            <div className="flex flex-col gap-2 rounded-lg bg-sidebar-accent p-3 text-sidebar-accent-foreground">
                <div className="flex items-center justify-between gap-2">
                    <span className="flex items-center gap-1.5 text-xs font-semibold tracking-wide uppercase opacity-80">
                        <Flag aria-hidden="true" className="size-3.5" />
                        Current meet
                    </span>
                    <Badge variant="secondary" className="shrink-0">
                        {currentMeet.status_label}
                    </Badge>
                </div>
                <p className="truncate text-sm font-medium">
                    {currentMeet.name}
                </p>
                <p className="text-xs opacity-80">
                    {currentMeet.starts_at} – {currentMeet.ends_at}
                    {currentMeet.venue && (
                        <>
                            <br />
                            {currentMeet.venue}
                        </>
                    )}
                </p>
            </div>
        </div>
    );
}
