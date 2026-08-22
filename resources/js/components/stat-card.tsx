import type { LucideIcon } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';

export type StatCardTone =
    | 'primary'
    | 'success'
    | 'warning'
    | 'destructive'
    | 'gold'
    | 'silver'
    | 'bronze';

const toneClasses: Record<StatCardTone, string> = {
    primary: 'bg-primary/10 text-primary',
    success: 'bg-success/15 text-success',
    warning: 'bg-warning/15 text-warning',
    destructive: 'bg-destructive/10 text-destructive',
    gold: 'bg-medal-gold/20 text-medal-gold-foreground',
    silver: 'bg-medal-silver/20 text-medal-silver-foreground',
    bronze: 'bg-medal-bronze/20 text-medal-bronze-foreground',
};

type Props = {
    label: string;
    value: number | string;
    icon?: LucideIcon;
    description?: string;
    /**
     * Colored circular icon badge (WP-08-04) — matches the reference
     * dashboard's per-metric icon colors. Omit for the plain, icon-only
     * look every other `StatCard` usage still has (e.g.
     * `management/index.tsx`, not part of this WP's scope).
     */
    tone?: StatCardTone;
};

export function StatCard({
    label,
    value,
    icon: Icon,
    description,
    tone,
}: Props) {
    return (
        <Card data-tone={tone}>
            <CardHeader className="flex flex-row items-center justify-between space-y-0">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                    {label}
                </CardTitle>
                {Icon &&
                    (tone ? (
                        <span
                            className={cn(
                                'flex size-9 shrink-0 items-center justify-center rounded-full',
                                toneClasses[tone],
                            )}
                        >
                            <Icon aria-hidden="true" className="size-4.5" />
                        </span>
                    ) : (
                        <Icon
                            aria-hidden="true"
                            className="size-4 text-muted-foreground"
                        />
                    ))}
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-semibold tracking-tight">
                    {value}
                </div>
                {description && (
                    <CardDescription className="mt-1">
                        {description}
                    </CardDescription>
                )}
            </CardContent>
        </Card>
    );
}
