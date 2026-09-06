<?php

namespace App\Console\Commands;

use App\Services\DataIntegrityService;
use Illuminate\Console\Command;

class AuditData extends Command
{
    protected $signature = 'pmms:audit-data {--meet= : Limit to a meet ID} {--json : Output structured issues}';

    protected $description = 'Read-only PMMS relationship health check for authorized system administrators';

    public function handle(DataIntegrityService $service): int
    {
        if ($this->option('meet') !== null && (! ctype_digit((string) $this->option('meet')) || (int) $this->option('meet') < 1)) {
            $this->error('Meet must be a positive integer ID.');

            return self::INVALID;
        }
        $issues = $service->scan($this->option('meet') === null ? null : (int) $this->option('meet'));
        if ($this->option('json')) {
            $this->line($issues->toJson(JSON_PRETTY_PRINT));
        } else {
            $this->table(['Issue type', 'Count'], $issues->countBy('type')->map(fn ($count, $type) => [$type, $count])->values());
            $this->info($issues->count().' concerns. Read-only: no records changed.');
        }

        return self::SUCCESS;
    }
}
