<?php

namespace App\Services\PlaceImport;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiCompatibleClient
{
    public function __construct(private readonly PlaceImportPrompt $prompt)
    {
    }

    /**
     * @param array<int, array<string, mixed>> $records
     * @param array<string, mixed> $taxonomy
     * @return array<string, mixed>
     */
    public function classify(array $records, array $taxonomy): array
    {
        $baseUrl = rtrim((string) config('services.place_import_ai.base_url'), '/');
        $apiKey = (string) config('services.place_import_ai.api_key');

        if ($baseUrl === '' || $apiKey === '') {
            throw new RuntimeException('Place import AI is not configured.');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.place_import_ai.timeout', 120))
            ->retry((int) config('services.place_import_ai.retries', 2), 1000)
            ->post($baseUrl . '/chat/completions', [
                'model' => config('services.place_import_ai.model', 'deepseek-v4-flash'),
                'temperature' => 0,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a strict data classification service. Output only the requested JSON object.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->prompt->build($records, $taxonomy),
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Place import AI request failed with HTTP status ' . $response->status() . '.');
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('Place import AI response did not contain message content.');
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Place import AI response was not valid JSON.');
        }

        return $decoded;
    }
}
