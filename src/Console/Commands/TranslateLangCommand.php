<?php

namespace Aar\AutoTranslator\Console\Commands;

use Illuminate\Console\Command;

class TranslateLangCommand extends Command
{
    protected $signature = 'aar:lang
                            {action : Action to perform: add or remove}
                            {locale : The locale code to add or remove (e.g. de, it, pt)}';

    protected $description = 'Dynamically add or remove supported locales';

    public function handle(): int
    {
        $action = strtolower($this->argument('action'));
        $locale = strtolower(trim($this->argument('locale')));

        if (!in_array($action, ['add', 'remove'])) {
            $this->error("Invalid action '{$action}'. Use 'add' or 'remove'.");
            return self::FAILURE;
        }

        if (!preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $locale)) {
            $this->error("Invalid locale format: '{$locale}'. Expected format: 'en', 'ar', 'en_US'.");
            return self::FAILURE;
        }

        $configPath = config_path('aar-translator.php');

        if (!file_exists($configPath)) {
            $this->error('Config file not found. Run: php artisan vendor:publish --tag=aar-translator-config');
            return self::FAILURE;
        }

        $content = file_get_contents($configPath);
        $currentLocales = config('aar-translator.locales', ['en']);

        if ($action === 'add') {
            if (in_array($locale, $currentLocales)) {
                $this->warn("Locale '{$locale}' is already configured.");
                return self::SUCCESS;
            }

            $newLocales = array_merge($currentLocales, [$locale]);
            $this->updateLocalesInConfig($content, $configPath, $newLocales);

            $this->info("✅ Locale '{$locale}' added successfully.");
            $this->line("  Run <comment>php artisan aar:auto --locale={$locale}</comment> to generate translations.");
        } else {
            if (!in_array($locale, $currentLocales)) {
                $this->warn("Locale '{$locale}' is not currently configured.");
                return self::SUCCESS;
            }

            if ($locale === config('aar-translator.source_locale', 'en')) {
                $this->error("Cannot remove the source locale '{$locale}'.");
                return self::FAILURE;
            }

            $newLocales = array_values(array_filter($currentLocales, fn($l) => $l !== $locale));
            $this->updateLocalesInConfig($content, $configPath, $newLocales);

            $this->info("✅ Locale '{$locale}' removed from configuration.");
        }

        $this->line("  Current locales: <info>" . implode(', ', $newLocales) . "</info>");

        return self::SUCCESS;
    }

    protected function updateLocalesInConfig(string $content, string $path, array $locales): void
    {
        $localeStr = "['" . implode("', '", $locales) . "']";

        // Replace the locales array in the config file
        $updated = preg_replace(
            "/'locales'\s*=>\s*\[[^\]]*\]/",
            "'locales' => {$localeStr}",
            $content
        );

        if ($updated !== null) {
            file_put_contents($path, $updated);
        }
    }
}
