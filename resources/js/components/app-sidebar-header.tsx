import { usePage } from '@inertiajs/react';
import { CalendarDays } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import { useClock } from '@/hooks/use-clock';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

/**
 * The sidebar-inset header — sits in the main (light) content panel, not
 * inside the dark `Sidebar` itself, so it reads `--border`/`--background`
 * tokens rather than `--sidebar-*` ones (WP-08-02 gave those two token
 * groups genuinely different colors; before that they were coincidentally
 * both neutral gray, so the distinction didn't matter).
 */
export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth } = usePage().props;
    const now = useClock();

    return (
        <header className="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-amber-800/30 bg-gradient-to-r from-amber-700 via-amber-600 to-yellow-600 px-6 text-white shadow-sm transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4 [&_[data-slot=breadcrumb-link]:hover]:text-white [&_[data-slot=breadcrumb-list]]:text-white/80 [&_[data-slot=breadcrumb-page]]:text-white">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1 text-white hover:bg-white/15 hover:text-white" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            {auth.user && (
                <div className="flex items-center gap-4">
                    <div className="hidden items-center gap-1.5 text-sm text-white/85 sm:flex">
                        <CalendarDays aria-hidden="true" className="size-4" />
                        <span>
                            {now.toLocaleDateString(undefined, {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                            })}
                        </span>
                        <span aria-hidden="true">·</span>
                        <span>
                            {now.toLocaleTimeString(undefined, {
                                hour: 'numeric',
                                minute: '2-digit',
                            })}
                        </span>
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <button
                                type="button"
                                className="flex items-center gap-2 rounded-md p-1 text-left text-white outline-none hover:bg-white/15 focus-visible:ring-2 focus-visible:ring-white/70 [&_.text-muted-foreground]:text-white/75"
                                data-test="header-user-menu-button"
                            >
                                <UserInfo user={auth.user} showRole />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-56">
                            <UserMenuContent user={auth.user} />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            )}
        </header>
    );
}
