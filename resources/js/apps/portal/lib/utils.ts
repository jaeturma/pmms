import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

/** Kept local to the portal design system on purpose — a fresh copy
 * rather than an import from `@/lib/utils`, so nothing under
 * `apps/portal` depends on any module outside its own tree. */
export function cn(...inputs: ClassValue[]): string {
    return twMerge(clsx(inputs));
}
