<?php

namespace Rz\LaravelAutoTranslator\Translators;

use Rz\LaravelAutoTranslator\Memory\TranslationMemory;

/**
 * TranslatorFactory creates the appropriate translator based on configuration.
 * Also wraps any translator with Translation Memory support.
 */
class TranslatorFactory
{
    public function __construct(
        protected string $provider,
        protected array $config,
        protected TranslationMemory $memory
    ) {
    }

    /**
     * Create the configured translator instance.
     */
    public function make(): TranslatorInterface
    {
        $translator = match ($this->provider) {
            'libretranslate' => new LibreTranslateTranslator($this->config['libretranslate'] ?? []),
            'deepl'          => new DeepLTranslator($this->config['deepl'] ?? []),
            'google'         => new GoogleTranslator($this->config['google'] ?? []),
            'openai'         => new OpenAITranslator($this->config['openai'] ?? []),
            default          => new NullTranslator(),
        };

        // Wrap with memory-aware decorator
        if ($this->config['memory']['enabled'] ?? true) {
            return new MemoryAwareTranslator($translator, $this->memory);
        }

        return $translator;
    }

    /**
     * Create a translator for a specific provider, bypassing config.
     */
    public function makeFor(string $provider): TranslatorInterface
    {
        return match ($provider) {
            'libretranslate' => new LibreTranslateTranslator($this->config['libretranslate'] ?? []),
            'deepl'          => new DeepLTranslator($this->config['deepl'] ?? []),
            'google'         => new GoogleTranslator($this->config['google'] ?? []),
            'openai'         => new OpenAITranslator($this->config['openai'] ?? []),
            default          => new NullTranslator(),
        };
    }
}
