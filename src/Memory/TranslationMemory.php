<?php

namespace Aar\AutoTranslator\Memory;

use Illuminate\Support\Facades\Cache;
use Aar\AutoTranslator\Models\TranslationMemoryModel;

/**
 * TranslationMemory stores and retrieves previously translated phrases
 * to avoid redundant API calls.
 */
class TranslationMemory
{
    public function __construct(protected array $config)
    {
    }

    /**
     * Look up a cached translation in memory.
     *
     * @return string|null The cached translation, or null if not found
     */
    public function recall(string $text, string $sourceLang, string $targetLang): ?string
    {
        if (!($this->config['enabled'] ?? true)) {
            return null;
        }

        $driver = $this->config['driver'] ?? 'database';

        if ($driver === 'cache') {
            return Cache::get($this->cacheKey($text, $sourceLang, $targetLang));
        }

        // Database driver
        $entry = TranslationMemoryModel::findTranslation($text, $sourceLang, $targetLang);
        return $entry?->translated_text;
    }

    /**
     * Store a translation in memory.
     */
    public function remember(
        string $text,
        string $sourceLang,
        string $targetLang,
        string $translatedText,
        string $provider
    ): void {
        if (!($this->config['enabled'] ?? true)) {
            return;
        }

        $driver = $this->config['driver'] ?? 'database';

        if ($driver === 'cache') {
            Cache::forever(
                $this->cacheKey($text, $sourceLang, $targetLang),
                $translatedText
            );
            return;
        }

        // Database driver
        TranslationMemoryModel::remember($text, $sourceLang, $targetLang, $translatedText, $provider);
    }

    /**
     * Generate a unique cache key for a translation.
     */
    protected function cacheKey(string $text, string $sourceLang, string $targetLang): string
    {
        return 'aar_tm_' . md5("{$sourceLang}:{$targetLang}:{$text}");
    }

    /**
     * Clear all memory entries.
     */
    public function flush(): void
    {
        $driver = $this->config['driver'] ?? 'database';

        if ($driver === 'cache') {
            // Can't flush selectively; users need to call Cache::flush()
            return;
        }

        TranslationMemoryModel::truncate();
    }

    /**
     * Get statistics about the translation memory.
     */
    public function stats(): array
    {
        return [
            'total_entries' => TranslationMemoryModel::count(),
            'total_uses'    => (int) TranslationMemoryModel::sum('use_count'),
            'providers'     => TranslationMemoryModel::select('provider')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('provider')
                ->pluck('count', 'provider')
                ->toArray(),
        ];
    }
}
