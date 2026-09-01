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
            systemTimezone: string;
            auth: Auth;
            sidebarOpen: boolean;
            branding: {
                title: string;
                logoUrl: string | null;
                loginSplashTitle: string;
                loginBackgroundUrl: string | null;
            };
            division: {
                type: 'city' | 'province';
                name: string;
                areaLabel: string;
                logoUrl: string | null;
                heroLogoUrl: string | null;
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
                venue: string | null;
                schoolYear: string;
                liveCount: number;
            } | null;
            recaptcha: {
                enabled: boolean;
                siteKey: string | null;
            } | null;
            [key: string]: unknown;
        };
    }
}
