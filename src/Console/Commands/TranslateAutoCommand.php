<?php

namespace Aar\AutoTranslator\Console\Commands;

use Illuminate\Console\Command;
use Aar\AutoTranslator\Services\TranslationService;

class TranslateAutoCommand extends Command
{
    protected $signature = 'aar:translate auto
                            {--locale= : Translate only a specific locale}
                            {--provider= : Override the configured translation provider}
                            {--export : Export to language files after translating}';

    protected $description = 'Automatically translate all missing translation keys';

    public function handle(TranslationService $service): int
    {
        $locale = $this->option('locale');

        $target = $locale ? "locale [{$locale}]" : 'all locales';
        $this->info("🤖 Auto-translating missing keys for {$target}...");

        if ($this->option('provider')) {
            config(['aar-translator.translator' => $this->option('provider')]);
            $this->line("  Using provider: <comment>{$this->option('provider')}</comment>");
        }

        $result = $service->translateMissing($locale);

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['<info>Translated</info>', "<info>{$result['translated']}</info>"],
                ['Skipped (no source)', $result['skipped']],
                ['<error>Errors</error>', "<error>{$result['errors']}</error>"],
            ]
        );

        if ($result['translated'] > 0) {
            $this->info("✅ {$result['translated']} translations completed.");
        } else {
            $this->line('  No missing translations found.');
        }

        if ($result['errors'] > 0) {
            $this->warn("⚠️  {$result['errors']} errors occurred. Check your translation provider settings.");
        }

        if ($this->option('export')) {
            $this->call('aar:translate export');
        }

        return self::SUCCESS;
    }
}
