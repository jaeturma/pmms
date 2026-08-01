import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    const { division } = usePage().props;

    return (
        <>
            <div className="flex aspect-square size-9 items-center justify-center overflow-hidden rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                {division.logoUrl ? (
                    <img src={division.logoUrl} alt="" className="size-full object-cover" />
                ) : (
                    <AppLogoIcon className="size-6 fill-current" />
                )}
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate text-base leading-tight font-bold text-sidebar-foreground">
                    PMMS
                </span>
                <span className="truncate text-xs leading-tight text-sidebar-foreground/70">
                    Division Edition
                </span>
            </div>
        </>
    );
}
