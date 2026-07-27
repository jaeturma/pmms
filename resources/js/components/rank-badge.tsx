import { cn } from '@/lib/utils';

const medalToneClasses: Record<1 | 2 | 3, string> = {
    1: 'bg-medal-gold text-medal-gold-foreground',
    2: 'bg-medal-silver text-medal-silver-foreground',
    3: 'bg-medal-bronze text-medal-bronze-foreground',
};

export function RankBadge({ position }: { position: number }) {
    const tone = medalToneClasses[position as 1 | 2 | 3];

    return (
        <span
            className={cn(
                'flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                tone ?? 'bg-muted text-muted-foreground',
            )}
        >
            {position}
        </span>
    );
}
