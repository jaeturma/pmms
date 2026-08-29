import type { SelectHTMLAttributes } from 'react';
import { cn } from '@/apps/portal/lib/utils';

type PortalSelectOption = {
    value: string;
    label: string;
};

type PortalSelectProps = Omit<
    SelectHTMLAttributes<HTMLSelectElement>,
    'children'
> & {
    options: PortalSelectOption[];
    placeholder?: string;
};

export function PortalSelect({
    options,
    placeholder,
    className,
    ...props
}: PortalSelectProps) {
    return (
        <select
            className={cn(
                'h-9 rounded-[calc(var(--portal-radius)-0.25rem)] border border-[var(--portal-border)] bg-[var(--portal-surface)] px-2 text-sm text-[var(--portal-surface-foreground)]',
                className,
            )}
            {...props}
        >
            {placeholder && <option value="">{placeholder}</option>}
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
    );
}
