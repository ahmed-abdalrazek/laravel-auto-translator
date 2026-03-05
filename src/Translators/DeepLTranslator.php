<?php

namespace Rz\LaravelAutoTranslator\Translators;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * DeepL Translator – supports both free and pro API tiers.
 * Free API: api-free.deepl.com
 * Pro API:  api.deepl.com
 */
class DeepLTranslator implements TranslatorInterface
{
    protected Client $client;

    /** DeepL language code mappings (some locales need special codes) */
    protected array $langMap = [
        'en' => 'EN',
        'ar' => 'AR',
        'fr' => 'FR',
        'es' => 'ES',
        'de' => 'DE',
        'it' => 'IT',
        'pt' => 'PT',
        'ru' => 'RU',
        'zh' => 'ZH',
        'ja' => 'JA',
        'ko' => 'KO',
        'nl' => 'NL',
        'pl' => 'PL',
        'sv' => 'SV',
        'tr' => 'TR',
    ];

    public function __construct(protected array $config)
    {
        $isFree = $config['free_api'] ?? true;
        $baseUri = $isFree
            ? 'https://api-free.deepl.com/v2/'
            : 'https://api.deepl.com/v2/';

        $this->client = new Client([
            'base_uri' => $baseUri,
            'timeout'  => 30,
            'headers'  => [
                'Authorization' => 'DeepL-Auth-Key ' . ($config['api_key'] ?? ''),
            ],
        ]);
    }

    public function translate(string $text, string $targetLang, string $sourceLang = 'en'): string
    {
        $results = $this->translateBatch([$text], $targetLang, $sourceLang);
        return $results[0] ?? $text;
    }

    public function translateBatch(array $texts, string $targetLang, string $sourceLang = 'en'): array
    {
        if (empty($texts)) {
            return $texts;
        }

        try {
            $params = [
                'target_lang' => strtoupper($this->langMap[$targetLang] ?? $targetLang),
                'source_lang' => strtoupper($this->langMap[$sourceLang] ?? $sourceLang),
            ];

            foreach ($texts as $text) {
                $params['text'][] = $text;
            }

            $response = $this->client->post('translate', [
                'form_params' => $params,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return array_map(
                fn($t) => $t['text'] ?? '',
                $data['translations'] ?? []
            );
        } catch (GuzzleException $e) {
            return $texts;
        }
    }

    public function getProviderName(): string
    {
        return 'deepl';
    }
}
