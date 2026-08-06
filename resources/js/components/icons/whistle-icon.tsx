import type { SVGProps } from 'react';

/**
 * A referee's whistle — lucide-react has no whistle glyph in the installed
 * version (^0.475.0), so this fills that one gap using the same visual
 * convention every lucide icon uses (24x24 viewBox, stroke-based, inherits
 * `currentColor`), so it drops into the same `<Button><WhistleIcon
 * aria-hidden /></Button>` slots as any other icon.
 */
export function WhistleIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width={24}
            height={24}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={2}
            strokeLinecap="round"
            strokeLinejoin="round"
            {...props}
        >
            <path d="M8.5 8.5a5.5 5.5 0 1 0 5.44 6.31L20 12l-4-2.5" />
            <path d="M20 12a2 2 0 1 0 2-2" />
            <path d="M8.5 8.5 5 6" />
            <circle cx="8.5" cy="14" r="1.5" />
        </svg>
    );
}
