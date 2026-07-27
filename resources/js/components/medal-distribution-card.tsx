import { Medal } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import type { MedalCounts } from '@/components/medal-table-parts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

/**
 * A pure-CSS conic-gradient donut (no charting library added, matching
 * this project's dependency-isolation discipline) showing the gold/
 * silver/bronze share of the total. Shared by the internal and public
 * tally pages — extracted on its second use.
 */
export function MedalDistributionCard({ totals }: { totals: MedalCounts }) {
    const segments: Array<{ label: string; value: number; dot: string }> = [
        { label: 'Gold', value: totals.gold, dot: 'bg-medal-gold' },
        { label: 'Silver', value: totals.silver, dot: 'bg-medal-silver' },
        { label: 'Bronze', value: totals.bronze, dot: 'bg-medal-bronze' },
    ];

    const gradient =
        totals.total > 0
            ? (() => {
                  let cursor = 0;
                  const stops = segments.map((segment) => {
                      const start = cursor;
                      cursor += (segment.value / totals.total) * 100;

                      return `var(--${segment.dot.replace('bg-', '')}) ${start}% ${cursor}%`;
                  });

                  return `conic-gradient(${stops.join(', ')})`;
              })()
            : undefined;

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-sm font-medium">
                    Medal distribution
                </CardTitle>
            </CardHeader>
            <CardContent>
                {totals.total === 0 ? (
                    <EmptyState icon={Medal} title="No medals yet" />
                ) : (
                    <div className="flex items-center gap-6">
                        <div
                            className="relative size-28 shrink-0 rounded-full"
                            style={{ background: gradient }}
                            role="img"
                            aria-label={`${totals.gold} gold, ${totals.silver} silver, ${totals.bronze} bronze`}
                        >
                            <div className="absolute inset-3 flex flex-col items-center justify-center rounded-full bg-card text-center">
                                <span className="text-xl font-semibold tracking-tight">
                                    {totals.total}
                                </span>
                                <span className="text-[0.65rem] text-muted-foreground">
                                    Total medals
                                </span>
                            </div>
                        </div>
                        <dl className="flex-1 space-y-2 text-sm">
                            {segments.map((segment) => (
                                <div
                                    key={segment.label}
                                    className="flex items-center justify-between gap-2"
                                >
                                    <dt className="flex items-center gap-1.5 text-muted-foreground">
                                        <span
                                            className={cn(
                                                'size-2 rounded-full',
                                                segment.dot,
                                            )}
                                            aria-hidden="true"
                                        />
                                        {segment.label}
                                    </dt>
                                    <dd className="font-medium">
                                        {segment.value} (
                                        {(
                                            (segment.value / totals.total) *
                                            100
                                        ).toFixed(1)}
                                        %)
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
