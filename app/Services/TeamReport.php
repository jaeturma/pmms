<?php

namespace App\Services;

use App\Models\Meet;

class TeamReport
{
    public function __construct(private readonly SportsMedalAwardsReport $awards) {}

    public function build(Meet $meet, int $delegationId): array
    {
        $sports = collect($this->awards->build($meet, null, $delegationId))
            ->map(function (array $sport): array {
                $sport['events'] = array_values(array_filter($sport['events'], fn ($event) => $event['awards'] !== []));

                return $sport;
            })->filter(fn ($sport) => $sport['events'] !== [])->values()->all();

        $summary = ['gold' => 0, 'silver' => 0, 'bronze' => 0, 'total' => 0];
        foreach ($sports as $sport) {
            foreach ($sport['events'] as $event) {
                foreach ($event['awards'] as $award) {
                    $count = (int) $award['tally_count'];
                    if (array_key_exists($award['medal'], $summary) && $award['medal'] !== 'total') {
                        $summary[$award['medal']] += $count;
                    }
                    $summary['total'] += $count;
                }
            }
        }

        return compact('sports', 'summary');
    }
}
