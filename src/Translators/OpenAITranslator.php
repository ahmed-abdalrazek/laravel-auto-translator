<?php

namespace Aar\AutoTranslator\Translators;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * OpenAI-based translator using GPT models.
 * Useful when high-quality, context-aware translations are needed.
 */
class OpenAITranslator implements TranslatorInterface
{
    protected Client $client;

    public function __construct(protected array $config)
    {
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout'  => 60,
            'headers'  => [
                'Authorization' => 'Bearer ' . ($config['api_key'] ?? ''),
                'Content-Type'  => 'application/json',
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

        $model = $this->config['model'] ?? 'gpt-4o-mini';
        $results = [];

        // Process in chunks to avoid token limits
        $chunks = array_chunk($texts, 20);

        foreach ($chunks as $chunk) {
            $numbered = implode("\n", array_map(
                fn($i, $t) => "{$i}. {$t}",
                range(1, count($chunk)),
                $chunk
            ));

            $prompt = "Translate the following texts from {$sourceLang} to {$targetLang}. "
                . "Return ONLY the translations, numbered the same way, with no explanation.\n\n"
                . $numbered;

            try {
                $response = $this->client->post('chat/completions', [
                    'json' => [
                        'model'    => $model,
                        'messages' => [
                            ['role' => 'user', 'content' => $prompt],
                        ],
                        'temperature' => 0.3,
                    ],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                $content = $data['choices'][0]['message']['content'] ?? '';

                // Parse numbered response
                $lines = explode("\n", trim($content));
                foreach ($lines as $line) {
                    if (preg_match('/^\d+\.\s*(.+)$/', trim($line), $m)) {
                        $results[] = trim($m[1]);
                    }
                }
            } catch (GuzzleException $e) {
                // Fall back to originals on error
                foreach ($chunk as $text) {
                    $results[] = $text;
                }
            }
        }

        return $results;
    }

    public function getProviderName(): string
    {
        return 'openai';
    }
}
