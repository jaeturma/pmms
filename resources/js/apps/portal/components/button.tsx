import { Slot } from '@radix-ui/react-slot';
import type { ButtonHTMLAttributes } from 'react';
import { cn } from '@/apps/portal/lib/utils';

type PortalButtonVariant = 'solid' | 'outline' | 'ghost';
type PortalButtonSize = 'sm' | 'md' | 'lg';

type PortalButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: PortalButtonVariant;
    size?: PortalButtonSize;
    asChild?: boolean;
};

const variantClasses: Record<PortalButtonVariant, string> = {
    solid: 'bg-[var(--portal-accent)] text-[var(--portal-accent-foreground)] hover:opacity-90',
    outline:
        'border border-[var(--portal-border)] bg-transparent text-[var(--portal-fg)] hover:bg-[var(--portal-muted)]',
    ghost: 'bg-transparent text-[var(--portal-fg)] hover:bg-[var(--portal-muted)]',
};

const sizeClasses: Record<PortalButtonSize, string> = {
    sm: 'h-8 gap-1.5 rounded-[calc(var(--portal-radius)-0.25rem)] px-3 text-sm',
    md: 'h-10 gap-2 rounded-[var(--portal-radius)] px-4 text-sm',
    lg: 'h-12 gap-2 rounded-[var(--portal-radius)] px-6 text-base',
};

export function PortalButton({
    className,
    variant = 'solid',
    size = 'md',
    asChild = false,
    ...props
}: PortalButtonProps) {
    const Comp = asChild ? Slot : 'button';

    return (
        <Comp
            className={cn(
                'inline-flex items-center justify-center font-medium transition-[transform,opacity,background-color] duration-150 ease-[var(--portal-ease)] disabled:pointer-events-none disabled:opacity-50',
                variantClasses[variant],
                sizeClasses[size],
                className,
            )}
            {...props}
        />
    );
}
