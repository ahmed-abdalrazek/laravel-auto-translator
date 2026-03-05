<?php

namespace Rz\LaravelAutoTranslator\Export;

use Rz\LaravelAutoTranslator\Models\TranslationKey;
use Rz\LaravelAutoTranslator\Models\TranslationValue;
use Rz\LaravelAutoTranslator\Services\KeyGeneratorService;

/**
 * ImportService: imports translations from JSON or CSV files into the database.
 */
class ImportService
{
    public function __construct(
        protected KeyGeneratorService $keyGenerator,
        protected array $config
    ) {
    }

    /**
     * Import translations from a JSON file.
     *
     * Format: { "locale": { "group": { "key": "value" } } }
     * or:     { "locale": { "key": "value" } }  (for JSON/non-grouped)
     *
     * @return array{imported: int, skipped: int}
     */
    public function importJson(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $data = json_decode(file_get_contents($filePath), true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException("Invalid JSON file: {$filePath}");
        }

        $imported = 0;
        $skipped = 0;

        foreach ($data as $locale => $groups) {
            foreach ($groups as $groupOrKey => $valueOrKeys) {
                if (is_array($valueOrKeys)) {
                    // Grouped: { group: { key: value } }
                    foreach ($valueOrKeys as $key => $value) {
                        $result = $this->importSingleValue(
                            $key,
                            $groupOrKey,
                            $locale,
                            (string) $value
                        );
                        $result ? $imported++ : $skipped++;
                    }
                } else {
                    // Flat JSON: { key: value }
                    $result = $this->importSingleValue(
                        $groupOrKey,
                        '*',
                        $locale,
                        (string) $valueOrKeys
                    );
                    $result ? $imported++ : $skipped++;
                }
            }
        }

        return compact('imported', 'skipped');
    }

    /**
     * Import translations from a CSV file.
     *
     * Expected headers: group, key, locale1, locale2, ...
     *
     * @return array{imported: int, skipped: int}
     */
    public function importCsv(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle);

        if (!$headers || !in_array('key', $headers)) {
            fclose($handle);
            throw new \InvalidArgumentException("CSV must have 'key' column");
        }

        $localeColumns = array_filter(
            $headers,
            fn($h) => !in_array($h, ['group', 'key', 'file'])
        );

        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowData = array_combine($headers, $row);
            $group = $rowData['group'] ?? '*';
            $key = $rowData['key'] ?? '';

            if (empty($key)) {
                $skipped++;
                continue;
            }

            foreach ($localeColumns as $locale) {
                $value = $rowData[$locale] ?? null;
                if ($value !== null && $value !== '') {
                    $result = $this->importSingleValue($key, $group, $locale, $value);
                    $result ? $imported++ : $skipped++;
                }
            }
        }

        fclose($handle);
        return compact('imported', 'skipped');
    }

    /**
     * Import a single translation value into the database.
     *
     * @return bool True if a new record was created, false if updated/skipped
     */
    protected function importSingleValue(string $key, string $group, string $locale, string $value): bool
    {
        // Ensure the key exists
        $keyModel = TranslationKey::firstOrCreate(
            ['key' => $key, 'group' => $group],
            ['file' => $group !== '*' ? $group : null, 'is_dead' => false]
        );

        $valueModel = TranslationValue::firstOrCreate(
            ['translation_key_id' => $keyModel->id, 'locale' => $locale],
            ['value' => $value]
        );

        if (!$valueModel->wasRecentlyCreated) {
            $valueModel->update(['value' => $value]);
            return false;
        }

        return true;
    }
}
