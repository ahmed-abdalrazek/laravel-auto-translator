<?php

namespace Rz\LaravelAutoTranslator\Translators;

use Rz\LaravelAutoTranslator\Memory\TranslationMemory;

/**
 * Decorator that wraps any translator with Translation Memory support.
 * Checks memory before calling the underlying translator, and stores
 * new translations in memory after each call.
 */
class MemoryAwareTranslator implements TranslatorInterface
{
    public function __construct(
        protected TranslatorInterface $inner,
        protected TranslationMemory $memory
    ) {
    }

    public function translate(string $text, string $targetLang, string $sourceLang = 'en'): string
    {
        if (trim($text) === '') {
            return $text;
        }

        // Check memory first
        $cached = $this->memory->recall($text, $sourceLang, $targetLang);
        if ($cached !== null) {
            return $cached;
        }

        // Translate using underlying provider
        $translated = $this->inner->translate($text, $targetLang, $sourceLang);

        // Save to memory
        $this->memory->remember($text, $sourceLang, $targetLang, $translated, $this->inner->getProviderName());

        return $translated;
    }

    public function translateBatch(array $texts, string $targetLang, string $sourceLang = 'en'): array
    {
        if (empty($texts)) {
            return $texts;
        }

        $results = [];
        $toTranslate = [];
        $indexMap = [];

        // Check memory for each text
        foreach ($texts as $i => $text) {
            $cached = $this->memory->recall($text, $sourceLang, $targetLang);
            if ($cached !== null) {
                $results[$i] = $cached;
            } else {
                $toTranslate[$i] = $text;
                $indexMap[] = $i;
            }
        }

        if (!empty($toTranslate)) {
            $translated = $this->inner->translateBatch(array_values($toTranslate), $targetLang, $sourceLang);
            foreach ($translated as $j => $translatedText) {
                $originalIndex = $indexMap[$j];
                $originalText = $toTranslate[$originalIndex];
                $results[$originalIndex] = $translatedText;
                $this->memory->remember(
                    $originalText,
                    $sourceLang,
                    $targetLang,
                    $translatedText,
                    $this->inner->getProviderName()
                );
            }
        }

        ksort($results);
        return array_values($results);
    }

    public function getProviderName(): string
    {
        return $this->inner->getProviderName();
    }
}
