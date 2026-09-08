import { Head } from '@inertiajs/react';
import { PauseCircle } from 'lucide-react';
import { PortalEmptyState } from '@/apps/portal/components/empty-state';
import { PortalHero } from '@/apps/portal/components/hero';

type Props = {
    message: string;
};

/**
 * Rendered by `EnsurePublicScoreboardsActive` in place of any public
 * live-scoreboard page while a System Administrator has the "Suspend All
 * Live Scoreboards" lever pulled. No poll, no data — a viewer refreshes
 * the page to check whether scoreboards are back.
 */
export default function PortalScoreboardSuspended({ message }: Props) {
    return (
        <>
            <Head title="Live scoreboards suspended" />
            <div className="flex flex-col gap-6">
                <PortalHero
                    title="Live Scoreboards"
                    description="Real-time scoring for this meet."
                />
                <PortalEmptyState
                    icon={PauseCircle}
                    tone="ink"
                    title={message}
                    description="Refresh this page to check again."
                />
            </div>
        </>
    );
}
