<?php

namespace Aar\AutoTranslator\Translators;

/**
 * Null translator – returns the original text unchanged.
 * Useful for testing or when no translation provider is configured.
 */
class NullTranslator implements TranslatorInterface
{
    public function translate(string $text, string $targetLang, string $sourceLang = 'en'): string
    {
        return $text;
    }

    public function translateBatch(array $texts, string $targetLang, string $sourceLang = 'en'): array
    {
        return $texts;
    }

    public function getProviderName(): string
    {
        return 'null';
    }
}
