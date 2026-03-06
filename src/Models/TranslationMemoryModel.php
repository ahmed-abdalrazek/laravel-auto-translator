<?php

namespace Aar\AutoTranslator\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationMemoryModel extends Model
{
    protected $table = 'translation_memory';

    protected $fillable = [
        'source_text',
        'source_lang',
        'target_lang',
        'translated_text',
        'provider',
        'use_count',
    ];

    protected $casts = [
        'use_count' => 'integer',
    ];

    /**
     * Find a memory entry for the given source text and language pair.
     */
    public static function findTranslation(
        string $sourceText,
        string $sourceLang,
        string $targetLang
    ): ?self {
        return static::where('source_hash', md5($sourceText))
            ->where('source_text', $sourceText)
            ->where('source_lang', $sourceLang)
            ->where('target_lang', $targetLang)
            ->first();
    }

    /**
     * Store a new translation in memory or increment use_count if it exists.
     */
    public static function remember(
        string $sourceText,
        string $sourceLang,
        string $targetLang,
        string $translatedText,
        string $provider
    ): self {
        $existing = static::findTranslation($sourceText, $sourceLang, $targetLang);

        if ($existing) {
            $existing->increment('use_count');
            return $existing;
        }

        return static::create([
            'source_text'     => $sourceText,
            'source_hash'     => md5($sourceText),
            'source_lang'     => $sourceLang,
            'target_lang'     => $targetLang,
            'translated_text' => $translatedText,
            'provider'        => $provider,
            'use_count'       => 1,
        ]);
    }
}
