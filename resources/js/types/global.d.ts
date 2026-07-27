import type { Auth } from '@/types/auth';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            division: {
                type: 'city' | 'province';
                name: string;
                areaLabel: string;
            };
            currentMeet: {
                name: string;
                status_label: string;
                starts_at: string;
                ends_at: string;
                venue: string | null;
            } | null;
            publicNav: {
                meetId: number;
                meetName: string;
                liveCount: number;
            } | null;
            [key: string]: unknown;
        };
    }
}
