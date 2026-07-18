import type { LucideIcon } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type Props = {
    label: string;
    value: number | string;
    icon?: LucideIcon;
    description?: string;
};

export function StatCard({ label, value, icon: Icon, description }: Props) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                    {label}
                </CardTitle>
                {Icon && <Icon className="size-4 text-muted-foreground" />}
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
