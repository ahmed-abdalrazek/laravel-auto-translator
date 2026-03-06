<?php

namespace Aar\AutoTranslator\Console\Commands;

use Illuminate\Console\Command;
use Aar\AutoTranslator\Export\ImportService;

class TranslateImportCommand extends Command
{
    protected $signature = 'aar:translate import
                            {file : Path to the JSON or CSV file to import}
                            {--format= : Import format: json or csv (auto-detected from extension if not set)}';

    protected $description = 'Import translations from a JSON or CSV file';

    public function handle(ImportService $service): int
    {
        $filePath = $this->argument('file');
        $format = $this->option('format');

        if (!$format) {
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $format = in_array($ext, ['csv']) ? 'csv' : 'json';
        }

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        $this->info("📥 Importing translations from {$filePath}...");

        try {
            $result = match ($format) {
                'csv'  => $service->importCsv($filePath),
                default => $service->importJson($filePath),
            };

            $this->newLine();
            $this->table(
                ['Metric', 'Count'],
                [
                    ['<info>Imported</info>', "<info>{$result['imported']}</info>"],
                    ['Updated/Skipped', $result['skipped']],
                ]
            );

            $this->info("✅ Import complete: {$result['imported']} new, {$result['skipped']} updated.");
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
