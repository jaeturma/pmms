import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { PortalHeader } from '@/apps/portal/layout/portal-header';
import { PortalFooter } from '@/apps/portal/layout/portal-footer';

type PortalLayoutProps = {
    children: ReactNode;
};

export default function PortalLayout({ children }: PortalLayoutProps) {
    const { url } = usePage();
    const activePath = url.split('?')[0];

    return (
        <div className="pmms-portal flex min-h-screen flex-col">
            <PortalHeader activePath={activePath} />
            <main className="w-full flex-1 px-4 py-8 sm:px-6 sm:py-10 lg:px-10 xl:px-16 2xl:px-24">{children}</main>
            <PortalFooter />
        </div>
    );
}
