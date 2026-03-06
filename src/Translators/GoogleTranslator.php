<?php

namespace Aar\AutoTranslator\Translators;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Google Cloud Translation API v2 (Basic)
 * Docs: https://cloud.google.com/translate/docs/reference/rest/v2/translate
 */
class GoogleTranslator implements TranslatorInterface
{
    protected Client $client;

    public function __construct(protected array $config)
    {
        $this->client = new Client([
            'base_uri' => 'https://translation.googleapis.com/language/translate/',
            'timeout'  => 30,
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
                'key'    => $this->config['api_key'] ?? '',
                'target' => $targetLang,
                'source' => $sourceLang,
                'format' => 'text',
                'q'      => $texts,
            ];

            $response = $this->client->get('v2', [
                'query' => $params,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $translations = $data['data']['translations'] ?? [];

            return array_map(
                fn($t) => html_entity_decode($t['translatedText'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                $translations
            );
        } catch (GuzzleException $e) {
            return $texts;
        }
    }

    public function getProviderName(): string
    {
        return 'google';
    }
}
