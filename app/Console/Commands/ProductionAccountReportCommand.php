<?php

namespace App\Console\Commands;

use App\Services\ProductionAccountReport;
use Illuminate\Console\Command;

class ProductionAccountReportCommand extends Command
{
    protected $signature = 'pmms:production-account-report';

    protected $description = 'Generate the password-free production account login verification matrix';

    public function handle(ProductionAccountReport $report): int
    {
        $summary = $report->generate();

        $this->components->info('Production account login matrix generated.');
        foreach ($summary as $label => $value) {
            $this->line("{$label}: {$value}");
        }

        return self::SUCCESS;
    }
}
