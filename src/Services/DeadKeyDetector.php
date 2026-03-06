<?php

namespace Aar\AutoTranslator\Services;

use Aar\AutoTranslator\Models\TranslationKey;
use Aar\AutoTranslator\Models\TranslationValue;

/**
 * Detects and removes dead translation keys –
 * keys that exist in the database/language files but are no longer
 * referenced anywhere in the project code.
 */
class DeadKeyDetector
{
    public function __construct(protected array $config)
    {
    }

    /**
     * Return all keys currently marked as dead.
     *
     * @return TranslationKey[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getDeadKeys()
    {
        return TranslationKey::dead()
            ->with('values')
            ->orderBy('group')
            ->orderBy('key')
            ->get();
    }

    /**
     * Permanently delete all dead keys (and their translations).
     *
     * @return int Number of deleted keys
     */
    public function deleteDeadKeys(): int
    {
        $deadKeys = TranslationKey::dead()->get();
        $count = $deadKeys->count();

        foreach ($deadKeys as $key) {
            $key->values()->delete();
            $key->delete();
        }

        return $count;
    }

    /**
     * Restore dead keys (un-mark them as dead).
     *
     * @param int[] $ids Key IDs to restore; if empty, restores all dead keys
     * @return int Number of restored keys
     */
    public function restoreDeadKeys(array $ids = []): int
    {
        $query = TranslationKey::dead();

        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return $query->update(['is_dead' => false]);
    }

    /**
     * Get dead key statistics.
     */
    public function stats(): array
    {
        $total = TranslationKey::count();
        $dead = TranslationKey::dead()->count();
        $live = TranslationKey::live()->count();

        return [
            'total' => $total,
            'dead'  => $dead,
            'live'  => $live,
        ];
    }

    /**
     * Scan existing PHP/JSON language files and sync them to the database,
     * detecting any keys in files that are absent from the code scan results.
     */
    public function scanFileKeys(string $langPath, array $codeKeys): array
    {
        $fileKeys = $this->extractFileKeys($langPath);
        $codeKeySet = array_flip($codeKeys);

        $deadFileKeys = array_filter($fileKeys, fn($k) => !isset($codeKeySet[$k]));

        return array_values($deadFileKeys);
    }

    /**
     * Extract all translation keys from PHP and JSON language files.
     *
     * @return string[] Flat list of all keys found in files
     */
    protected function extractFileKeys(string $langPath): array
    {
        $keys = [];

        if (!is_dir($langPath)) {
            return $keys;
        }

        // Scan JSON files (top-level keys)
        foreach (glob($langPath . '/*.json') as $jsonFile) {
            $data = json_decode(file_get_contents($jsonFile), true);
            if (is_array($data)) {
                foreach (array_keys($data) as $key) {
                    $keys[] = $key;
                }
            }
        }

        // Scan PHP group files
        foreach (glob($langPath . '/*/') as $localeDir) {
            foreach (glob($localeDir . '*.php') as $phpFile) {
                $group = basename($phpFile, '.php');
                $data = include $phpFile;
                if (is_array($data)) {
                    foreach (array_keys($data) as $key) {
                        $keys[] = "{$group}.{$key}";
                    }
                }
            }
        }

        return array_values(array_unique($keys));
    }
}
