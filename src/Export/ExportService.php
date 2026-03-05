<?php

namespace Rz\LaravelAutoTranslator\Export;

use Illuminate\Support\Facades\Storage;
use Rz\LaravelAutoTranslator\Models\TranslationKey;
use Rz\LaravelAutoTranslator\Models\TranslationValue;
use ZipArchive;

/**
 * ExportService: exports translations to JSON, CSV, or ZIP formats.
 */
class ExportService
{
    protected string $exportPath;

    public function __construct(protected array $config)
    {
        $this->exportPath = $config['export']['path']
            ?? storage_path('app/rz-translator/exports');

        if (!is_dir($this->exportPath)) {
            mkdir($this->exportPath, 0755, true);
        }
    }

    /**
     * Export translations to a JSON file.
     *
     * @param string|null $locale Export only this locale; null exports all
     * @return string Path to the exported file
     */
    public function exportJson(?string $locale = null): string
    {
        $locales = $locale ? [$locale] : ($this->config['locales'] ?? ['en']);
        $data = [];

        foreach ($locales as $loc) {
            $data[$loc] = $this->buildLocaleData($loc);
        }

        $filename = $this->exportPath . '/translations_' . date('Ymd_His') . '.json';
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $filename;
    }

    /**
     * Export translations to a CSV file.
     *
     * @param string|null $locale Export only this locale; null exports all
     * @return string Path to the exported file
     */
    public function exportCsv(?string $locale = null): string
    {
        $locales = $locale ? [$locale] : ($this->config['locales'] ?? ['en']);
        $filename = $this->exportPath . '/translations_' . date('Ymd_His') . '.csv';

        $handle = fopen($filename, 'w');

        // Header row
        $header = ['group', 'key'];
        foreach ($locales as $loc) {
            $header[] = $loc;
        }
        fputcsv($handle, $header);

        // Data rows
        $keys = TranslationKey::live()
            ->with(['values' => fn($q) => $q->whereIn('locale', $locales)])
            ->orderBy('group')
            ->orderBy('key')
            ->get();

        foreach ($keys as $key) {
            $row = [$key->group, $key->key];
            foreach ($locales as $loc) {
                $value = $key->values->firstWhere('locale', $loc);
                $row[] = $value?->value ?? '';
            }
            fputcsv($handle, $row);
        }

        fclose($handle);
        return $filename;
    }

    /**
     * Export translations to a ZIP file containing one JSON per locale.
     *
     * @return string Path to the exported ZIP file
     */
    public function exportZip(): string
    {
        $locales = $this->config['locales'] ?? ['en'];
        $zipFile = $this->exportPath . '/translations_' . date('Ymd_His') . '.zip';

        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($locales as $locale) {
            $data = $this->buildLocaleData($locale);
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $zip->addFromString("{$locale}.json", $json);
        }

        $zip->close();
        return $zipFile;
    }

    /**
     * Build an associative array of translations for a single locale.
     *
     * @return array<string, array<string, string>>
     */
    protected function buildLocaleData(string $locale): array
    {
        $result = [];

        $values = TranslationValue::query()
            ->join('translation_keys', 'translation_values.translation_key_id', '=', 'translation_keys.id')
            ->where('translation_values.locale', $locale)
            ->where('translation_keys.is_dead', false)
            ->select([
                'translation_keys.group',
                'translation_keys.key',
                'translation_values.value',
            ])
            ->orderBy('translation_keys.group')
            ->orderBy('translation_keys.key')
            ->get();

        foreach ($values as $value) {
            $result[$value->group][$value->key] = $value->value ?? '';
        }

        return $result;
    }
}
