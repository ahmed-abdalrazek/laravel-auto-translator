<?php

namespace Aar\AutoTranslator\Console\Commands;

use Illuminate\Console\Command;
use Aar\AutoTranslator\Export\ExportService;

class TranslateExportCommand extends Command
{
    protected $signature = 'aar:export
                            {--format=json : Export format: json, csv, or zip}
                            {--locale= : Export only a specific locale}';

    protected $description = 'Export translations to JSON, CSV, or ZIP format';

    public function handle(ExportService $service): int
    {
        $format = strtolower($this->option('format') ?? 'json');
        $locale = $this->option('locale');

        $this->info("📦 Exporting translations as {$format}...");

        $file = match ($format) {
            'csv'  => $service->exportCsv($locale),
            'zip'  => $service->exportZip(),
            default => $service->exportJson($locale),
        };

        $this->info("✅ Export complete: <comment>{$file}</comment>");

        return self::SUCCESS;
    }
}
