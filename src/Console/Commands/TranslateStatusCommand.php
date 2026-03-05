<?php

namespace Rz\LaravelAutoTranslator\Console\Commands;

use Illuminate\Console\Command;
use Rz\LaravelAutoTranslator\Services\TranslationService;

class TranslateStatusCommand extends Command
{
    protected $signature = 'rz:translate status';

    protected $description = 'Show translation key statistics and completion percentages';

    public function handle(TranslationService $service): int
    {
        $this->info('📊 Translation Status');
        $this->newLine();

        $stats = $service->getStats();

        $this->line("  Total Keys : <info>{$stats['total_keys']}</info>");
        $this->line("  Dead Keys  : <comment>{$stats['dead_keys']}</comment>");
        $this->newLine();

        $rows = [];
        foreach ($stats['locales'] as $locale => $data) {
            $bar = $this->progressBar($data['completion']);
            $rows[] = [
                strtoupper($locale),
                $data['translated'],
                $data['missing'],
                $data['total'],
                "{$data['completion']}% {$bar}",
            ];
        }

        $this->table(
            ['Locale', 'Translated', 'Missing', 'Total', 'Progress'],
            $rows
        );

        return self::SUCCESS;
    }

    protected function progressBar(float $percentage, int $width = 20): string
    {
        $filled = (int) round($percentage / 100 * $width);
        $empty = $width - $filled;
        return '[' . str_repeat('█', $filled) . str_repeat('░', $empty) . ']';
    }
}
