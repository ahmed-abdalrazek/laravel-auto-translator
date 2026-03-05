<?php

namespace Rz\LaravelAutoTranslator\Services;

use Rz\LaravelAutoTranslator\Models\TranslationKey;
use Rz\LaravelAutoTranslator\Models\TranslationValue;
use Rz\LaravelAutoTranslator\Scanners\ProjectScanner;
use Rz\LaravelAutoTranslator\Translators\TranslatorFactory;

/**
 * TranslationService orchestrates the full translation workflow:
 * 1. Scan project for translation keys
 * 2. Sync keys to database
 * 3. Detect dead keys
 * 4. Translate missing values using AI
 */
class TranslationService
{
    public function __construct(
        protected ProjectScanner $scanner,
        protected KeyGeneratorService $keyGenerator,
        protected TranslatorFactory $translatorFactory,
        protected array $config
    ) {
    }

    /**
     * Scan the project and sync all found keys to the database.
     *
     * @param bool $incremental Only scan changed files
     * @return array{new: int, existing: int, dead: int, scan_stats: array}
     */
    public function scan(bool $incremental = true): array
    {
        $scanResult = $this->scanner->scan($incremental);
        $foundKeys = $scanResult['keys'];

        // Sync keys to database
        $syncResult = $this->keyGenerator->syncKeys($foundKeys);

        // Mark dead keys (keys in DB but not found in scan)
        $deadCount = $this->markDeadKeys($foundKeys);

        return [
            'new'        => $syncResult['new'],
            'existing'   => $syncResult['existing'],
            'dead'       => $deadCount,
            'scan_stats' => $scanResult['stats'],
        ];
    }

    /**
     * Translate all missing translations for all configured locales.
     *
     * @param string|null $locale Translate only this locale if specified
     * @return array{translated: int, skipped: int, errors: int}
     */
    public function translateMissing(?string $locale = null): array
    {
        $translator = $this->translatorFactory->make();
        $sourceLocale = $this->config['source_locale'] ?? 'en';
        $locales = $locale
            ? [$locale]
            : array_filter($this->config['locales'] ?? ['en'], fn($l) => $l !== $sourceLocale);

        $translated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($locales as $targetLocale) {
            // Find all keys with missing value for this locale
            $missingValues = TranslationValue::query()
                ->join('translation_keys', 'translation_values.translation_key_id', '=', 'translation_keys.id')
                ->where('translation_values.locale', $targetLocale)
                ->whereNull('translation_values.value')
                ->where('translation_keys.is_dead', false)
                ->select(['translation_values.id', 'translation_values.translation_key_id'])
                ->get();

            // Batch translate
            $batches = $missingValues->chunk(50);

            foreach ($batches as $batch) {
                // Get source texts for each key
                $sourceTexts = [];
                $valueIds = [];

                foreach ($batch as $missing) {
                    $sourceValue = TranslationValue::where('translation_key_id', $missing->translation_key_id)
                        ->where('locale', $sourceLocale)
                        ->whereNotNull('value')
                        ->value('value');

                    if ($sourceValue === null) {
                        $skipped++;
                        continue;
                    }

                    $sourceTexts[] = $sourceValue;
                    $valueIds[] = $missing->id;
                }

                if (empty($sourceTexts)) {
                    continue;
                }

                try {
                    $translatedTexts = $translator->translateBatch($sourceTexts, $targetLocale, $sourceLocale);

                    foreach ($translatedTexts as $idx => $translatedText) {
                        if (isset($valueIds[$idx])) {
                            TranslationValue::where('id', $valueIds[$idx])->update([
                                'value'             => $translatedText,
                                'is_auto_translated' => true,
                                'provider'           => $translator->getProviderName(),
                            ]);
                            $translated++;
                        }
                    }
                } catch (\Throwable $e) {
                    $errors += count($sourceTexts);
                }
            }
        }

        return compact('translated', 'skipped', 'errors');
    }

    /**
     * Get translation statistics.
     */
    public function getStats(): array
    {
        $locales = $this->config['locales'] ?? ['en'];
        $totalKeys = TranslationKey::live()->count();
        $deadKeys = TranslationKey::dead()->count();

        $stats = [
            'total_keys' => $totalKeys,
            'dead_keys'  => $deadKeys,
            'locales'    => [],
        ];

        foreach ($locales as $locale) {
            $translated = TranslationValue::where('locale', $locale)
                ->whereNotNull('value')
                ->count();

            $missing = TranslationValue::where('locale', $locale)
                ->whereNull('value')
                ->count();

            $stats['locales'][$locale] = [
                'translated' => $translated,
                'missing'    => $missing,
                'total'      => $translated + $missing,
                'completion' => ($translated + $missing) > 0
                    ? round($translated / ($translated + $missing) * 100, 1)
                    : 100.0,
            ];
        }

        return $stats;
    }

    /**
     * Mark keys as dead if they weren't found in the latest scan.
     *
     * Strategy: find all live keys by (group, key) composite, then mark
     * every live key that is NOT in that set as dead.
     */
    protected function markDeadKeys(array $foundKeys): int
    {
        if (empty($foundKeys)) {
            return 0;
        }

        // Resolve found keys to [group, item] pairs
        $aliveKeys = [];
        foreach ($foundKeys as $rawKey) {
            [$group, $item] = $this->keyGenerator->parseKey($rawKey);
            $aliveKeys[] = ['group' => $group, 'key' => $item];
        }

        // Collect the IDs of all keys that ARE alive (present in the scan)
        $aliveQuery = TranslationKey::live()->where(function ($q) use ($aliveKeys) {
            foreach ($aliveKeys as $pair) {
                $q->orWhere(function ($inner) use ($pair) {
                    $inner->where('group', $pair['group'])->where('key', $pair['key']);
                });
            }
        });

        $aliveIds = $aliveQuery->pluck('id')->toArray();

        // Everything else that is live but NOT in the alive set is dead
        $count = TranslationKey::live()
            ->when(!empty($aliveIds), fn($q) => $q->whereNotIn('id', $aliveIds))
            ->update(['is_dead' => true]);

        return $count;
    }
}
