<?php

namespace Rz\LaravelAutoTranslator\Console\Commands;

use Illuminate\Console\Command;
use Rz\LaravelAutoTranslator\Services\TranslationService;

class TranslateScanCommand extends Command
{
    protected $signature = 'rz:translate scan
                            {--fresh : Clear file cache and do a full scan}
                            {--export : Export results to language files after scanning}';

    protected $description = 'Scan the project for translation keys and sync to database';

    public function handle(TranslationService $service): int
    {
        $this->info('🔍 Scanning project for translation keys...');

        $incremental = !$this->option('fresh');

        if (!$incremental) {
            $this->line('  <comment>Running full scan (cache cleared)</comment>');
            // Clear file cache
            $cachePath = config('rz-translator.file_cache.path')
                ?? storage_path('app/rz-translator/file-cache.json');
            if (file_exists($cachePath)) {
                unlink($cachePath);
            }
        }

        $result = $service->scan($incremental);

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Files Scanned',      $result['scan_stats']['scanned']],
                ['Files Skipped (cached)', $result['scan_stats']['skipped']],
                ['Total Files',         $result['scan_stats']['total_files']],
                ['<info>New Keys Found</info>', "<info>{$result['new']}</info>"],
                ['Existing Keys',       $result['existing']],
                ['<comment>Dead Keys Detected</comment>', "<comment>{$result['dead']}</comment>"],
            ]
        );

        if ($result['new'] > 0) {
            $this->info("✅ {$result['new']} new translation keys added to the database.");
        } else {
            $this->line('  No new keys found.');
        }

        if ($result['dead'] > 0) {
            $this->warn("⚠️  {$result['dead']} dead keys detected. Run <comment>rz:translate clean</comment> to remove them.");
        }

        if ($this->option('export')) {
            $this->call('rz:translate export');
        }

        return self::SUCCESS;
    }
}
