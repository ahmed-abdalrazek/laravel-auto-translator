<?php

namespace Aar\AutoTranslator\Services;

use Illuminate\Support\Str;
use Aar\AutoTranslator\Models\TranslationKey;
use Aar\AutoTranslator\Models\TranslationValue;

/**
 * KeyGeneratorService handles:
 * - Detecting missing translation keys
 * - Creating keys in the database
 * - Generating PHP array / JSON language files
 * - Formatting snake_case keys to human-readable English
 */
class KeyGeneratorService
{
    public function __construct(protected array $config)
    {
    }

    /**
     * Sync a list of found keys into the database and language files.
     *
     * @param string[] $keys    Translation keys found by scanner
     * @return array{new: int, existing: int, keys: TranslationKey[]}
     */
    public function syncKeys(array $keys): array
    {
        $new = 0;
        $existing = 0;
        $syncedKeys = [];

        foreach ($keys as $rawKey) {
            [$group, $item] = $this->parseKey($rawKey);

            /** @var TranslationKey $keyModel */
            $keyModel = TranslationKey::firstOrCreate(
                ['key' => $item, 'group' => $group],
                ['file' => $group !== '*' ? $group : null, 'is_dead' => false]
            );

            if ($keyModel->wasRecentlyCreated) {
                $new++;
                $this->generateDefaultValues($keyModel);
            } else {
                // Mark as alive if it was previously flagged dead
                if ($keyModel->is_dead) {
                    $keyModel->update(['is_dead' => false]);
                }
                $existing++;
            }

            $syncedKeys[] = $keyModel;
        }

        return ['new' => $new, 'existing' => $existing, 'keys' => $syncedKeys];
    }

    /**
     * Generate default (source locale) translation value for a key.
     */
    public function generateDefaultValues(TranslationKey $keyModel): void
    {
        $sourceLocale = $this->config['source_locale'] ?? 'en';
        $locales = $this->config['locales'] ?? ['en'];
        $autoFormat = $this->config['auto_format_keys'] ?? true;

        // Generate English (source) value
        $sourceValue = $autoFormat
            ? $this->formatKey($keyModel->key)
            : $keyModel->key;

        TranslationValue::firstOrCreate(
            ['translation_key_id' => $keyModel->id, 'locale' => $sourceLocale],
            ['value' => $sourceValue, 'is_auto_translated' => false]
        );

        // Create empty entries for other locales so they show as "missing"
        foreach ($locales as $locale) {
            if ($locale === $sourceLocale) {
                continue;
            }
            TranslationValue::firstOrCreate(
                ['translation_key_id' => $keyModel->id, 'locale' => $locale],
                ['value' => null, 'is_auto_translated' => false]
            );
        }
    }

    /**
     * Write all translations to PHP array language files and JSON files.
     */
    public function exportToFiles(): void
    {
        $langPath = $this->config['lang_path'] ?? (function_exists('lang_path') ? lang_path() : resource_path('lang'));
        $locales = $this->config['locales'] ?? ['en'];

        foreach ($locales as $locale) {
            $this->exportLocaleToFiles($locale, $langPath);
        }
    }

    /**
     * Export a single locale's translations to files.
     */
    protected function exportLocaleToFiles(string $locale, string $langPath): void
    {
        // JSON translations (group = '*')
        $jsonKeys = TranslationValue::query()
            ->join('translation_keys', 'translation_values.translation_key_id', '=', 'translation_keys.id')
            ->where('translation_values.locale', $locale)
            ->where('translation_keys.group', '*')
            ->whereNotNull('translation_values.value')
            ->select(['translation_keys.key', 'translation_values.value'])
            ->get()
            ->pluck('value', 'key')
            ->toArray();

        if (!empty($jsonKeys)) {
            $jsonFile = $langPath . "/{$locale}.json";
            $this->ensureDir(dirname($jsonFile));
            file_put_contents($jsonFile, json_encode($jsonKeys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        // PHP group files
        $groups = TranslationKey::where('group', '!=', '*')
            ->select('group')
            ->distinct()
            ->pluck('group');

        foreach ($groups as $group) {
            $groupKeys = TranslationValue::query()
                ->join('translation_keys', 'translation_values.translation_key_id', '=', 'translation_keys.id')
                ->where('translation_values.locale', $locale)
                ->where('translation_keys.group', $group)
                ->whereNotNull('translation_values.value')
                ->select(['translation_keys.key', 'translation_values.value'])
                ->get()
                ->pluck('value', 'key')
                ->toArray();

            if (!empty($groupKeys)) {
                $phpFile = $langPath . "/{$locale}/{$group}.php";
                $this->ensureDir(dirname($phpFile));
                $phpContent = "<?php\n\nreturn " . $this->exportPhpArray($groupKeys) . ";\n";
                file_put_contents($phpFile, $phpContent);
            }
        }
    }

    /**
     * Format a snake_case key to a human-readable English string.
     * Example: order_summary -> "Order Summary"
     */
    public function formatKey(string $key): string
    {
        // Handle dot-notation keys (e.g., "auth.login" -> "Login")
        $parts = explode('.', $key);
        $lastPart = end($parts);

        return Str::title(str_replace(['_', '-'], ' ', $lastPart));
    }

    /**
     * Parse a translation key into [group, item].
     * Keys like "messages.welcome" -> ['messages', 'welcome']
     * Keys like "Hello World" -> ['*', 'Hello World']
     */
    public function parseKey(string $key): array
    {
        if (str_contains($key, '.') && !str_contains($key, ' ')) {
            $parts = explode('.', $key, 2);
            return [$parts[0], $parts[1]];
        }

        return ['*', $key];
    }

    /**
     * Convert a PHP array to a formatted string representation.
     */
    protected function exportPhpArray(array $data, int $indent = 0): string
    {
        $pad = str_repeat('    ', $indent);
        $innerPad = str_repeat('    ', $indent + 1);
        $lines = ["["];

        foreach ($data as $key => $value) {
            $escapedKey = is_string($key) ? "'{$key}'" : $key;
            if (is_array($value)) {
                $lines[] = "{$innerPad}{$escapedKey} => " . $this->exportPhpArray($value, $indent + 1) . ",";
            } else {
                $escapedValue = addslashes((string) $value);
                $lines[] = "{$innerPad}{$escapedKey} => '{$escapedValue}',";
            }
        }

        $lines[] = "{$pad}]";
        return implode("\n", $lines);
    }

    protected function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
