<?php

namespace Rz\LaravelAutoTranslator\Translators;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * LibreTranslate is a free, open-source machine translation API.
 * Default instance: https://libretranslate.com
 * Self-host: https://github.com/LibreTranslate/LibreTranslate
 */
class LibreTranslateTranslator implements TranslatorInterface
{
    protected Client $client;

    public function __construct(protected array $config)
    {
        $this->client = new Client([
            'base_uri' => rtrim($config['url'] ?? 'https://libretranslate.com', '/') . '/',
            'timeout'  => 30,
        ]);
    }

    public function translate(string $text, string $targetLang, string $sourceLang = 'en'): string
    {
        if (trim($text) === '') {
            return $text;
        }

        try {
            $payload = [
                'q'      => $text,
                'source' => $sourceLang,
                'target' => $targetLang,
                'format' => 'text',
            ];

            if (!empty($this->config['api_key'])) {
                $payload['api_key'] = $this->config['api_key'];
            }

            $response = $this->client->post('translate', [
                'json' => $payload,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['translatedText'] ?? $text;
        } catch (GuzzleException $e) {
            // Fall back to original text if translation fails
            return $text;
        }
    }

    public function translateBatch(array $texts, string $targetLang, string $sourceLang = 'en'): array
    {
        // LibreTranslate supports batch via the q[] parameter
        if (empty($texts)) {
            return $texts;
        }

        try {
            $payload = [
                'q'      => $texts,
                'source' => $sourceLang,
                'target' => $targetLang,
                'format' => 'text',
            ];

            if (!empty($this->config['api_key'])) {
                $payload['api_key'] = $this->config['api_key'];
            }

            $response = $this->client->post('translate', [
                'json' => $payload,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['translatedText']) && is_array($data['translatedText'])) {
                return $data['translatedText'];
            }

            // Fallback: translate one by one
            return array_map(fn($t) => $this->translate($t, $targetLang, $sourceLang), $texts);
        } catch (GuzzleException $e) {
            return $texts;
        }
    }

    public function getProviderName(): string
    {
        return 'libretranslate';
    }
}
