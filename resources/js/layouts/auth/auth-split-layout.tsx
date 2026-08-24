import { Link, usePage } from '@inertiajs/react';
import { CalendarDays, ShieldCheck } from 'lucide-react';
import { SiteLogo } from '@/components/site-logo';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name, branding } = usePage().props;

    return (
        <div className="grid min-h-svh bg-background lg:grid-cols-[minmax(0,1.05fr)_minmax(420px,0.95fr)]">
            <div className="relative hidden min-h-svh overflow-hidden bg-primary p-10 text-primary-foreground lg:flex lg:flex-col xl:p-14">
                {branding.loginBackgroundUrl && (
                    <img
                        src={branding.loginBackgroundUrl}
                        alt=""
                        className="absolute inset-0 size-full object-cover"
                    />
                )}
                <div className="absolute inset-0 bg-black/55" />
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_15%_20%,rgba(255,255,255,0.2),transparent_32%),radial-gradient(circle_at_85%_75%,rgba(255,255,255,0.12),transparent_30%),linear-gradient(145deg,rgba(0,0,0,0),rgba(0,0,0,0.28))]" />
                <div className="absolute -right-24 -bottom-24 size-96 rounded-full border border-white/10" />
                <Link
                    href={home()}
                    className="relative z-20 flex items-center gap-4 text-2xl font-semibold tracking-tight"
                >
                    <SiteLogo className="size-16 text-white drop-shadow-md" />
                    {name}
                </Link>
                <div className="relative z-10 my-auto max-w-xl">
                    <p className="mb-3 text-sm font-semibold tracking-[0.2em] text-white/70 uppercase">
                        Provincial Meet Management
                    </p>
                    <h2 className="text-4xl leading-tight font-semibold tracking-tight xl:text-5xl">
                        {branding.loginSplashTitle}
                    </h2>
                    <p className="mt-5 max-w-lg text-base leading-7 text-white/75">
                        Coordinate schedules, participants, results, and medal
                        standings with confidence.
                    </p>
                    <div className="mt-10 grid grid-cols-2 gap-3 text-sm text-white/80">
                        <span className="flex items-center gap-2">
                            <CalendarDays className="size-4" /> Live meet
                            operations
                        </span>
                        <span className="flex items-center gap-2">
                            <ShieldCheck className="size-4" /> Secure role-based
                            access
                        </span>
                    </div>
                </div>
                <p className="relative z-10 text-xs text-white/55">
                    Built for organized, transparent, and memorable
                    competitions.
                </p>
            </div>
            <div className="dark:via-slate-850 flex min-h-svh w-full items-center bg-gradient-to-b from-white via-slate-100 to-slate-300 px-5 py-10 sm:px-8 lg:px-12 xl:px-20 dark:from-slate-700 dark:to-slate-950">
                <div className="mx-auto flex w-full max-w-md flex-col justify-center space-y-7">
                    <Link
                        href={home()}
                        className="relative z-20 flex items-center justify-center lg:hidden"
                    >
                        <SiteLogo className="h-10 w-10 text-black sm:h-12 sm:w-12" />
                    </Link>
                    <div className="flex flex-col items-start gap-2 text-left">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {title}
                        </h1>
                        <p className="text-sm leading-6 text-muted-foreground">
                            {description}
                        </p>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
