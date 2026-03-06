<?php

namespace Aar\AutoTranslator\Translators;

interface TranslatorInterface
{
    /**
     * Translate a string from the source language to the target language.
     *
     * @param string $text       The text to translate
     * @param string $targetLang Target language code (e.g. 'ar', 'fr')
     * @param string $sourceLang Source language code (e.g. 'en')
     * @return string            The translated text
     */
    public function translate(string $text, string $targetLang, string $sourceLang = 'en'): string;

    /**
     * Translate multiple strings at once (batch).
     *
     * @param string[] $texts      Array of texts to translate
     * @param string   $targetLang Target language code
     * @param string   $sourceLang Source language code
     * @return string[]            Array of translated texts (same order)
     */
    public function translateBatch(array $texts, string $targetLang, string $sourceLang = 'en'): array;

    /**
     * Get the provider name for this translator.
     */
    public function getProviderName(): string;
}
