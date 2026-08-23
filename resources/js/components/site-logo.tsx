import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';

export function SiteLogo({ className }: { className?: string }) {
    const { branding, division } = usePage().props;
    const logoUrl = branding.logoUrl ?? division.logoUrl;

    if (logoUrl) {
        return (
            <img
                src={logoUrl}
                alt={`${branding.title} logo`}
                className={cn('object-contain', className)}
            />
        );
    }

    return <AppLogoIcon className={cn('fill-current', className)} />;
}
