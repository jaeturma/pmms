import { usePage } from '@inertiajs/react';
import { SiteLogo } from '@/components/site-logo';

export default function AppLogo() {
    const { branding } = usePage().props;

    return (
        <>
            <div className="flex aspect-square size-9 items-center justify-center overflow-hidden rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                <SiteLogo className="size-full p-0.5" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate text-base leading-tight font-bold text-sidebar-foreground">
                    {branding.title}
                </span>
                <span className="truncate text-xs leading-tight text-sidebar-foreground/70">
                    Division Edition
                </span>
            </div>
        </>
    );
}
