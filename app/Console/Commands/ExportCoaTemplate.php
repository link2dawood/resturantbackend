<?php

namespace App\Console\Commands;

use App\Services\CoaTemplateService;
use Illuminate\Console\Command;

/**
 * Phase 4 — Tenant migration: snapshot Fann's Philly's live chart of accounts
 * into the reusable template (database/data/coa-template.json) used to seed new
 * sign-ups.
 */
class ExportCoaTemplate extends Command
{
    protected $signature = 'coa:export-template';

    protected $description = "Snapshot the current chart of accounts into the new-signup template";

    public function handle(CoaTemplateService $service): int
    {
        $count = $service->export();

        $this->info("Exported {$count} accounts to ".CoaTemplateService::templatePath());

        return self::SUCCESS;
    }
}
