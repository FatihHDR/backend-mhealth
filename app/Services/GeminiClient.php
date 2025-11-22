<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GeminiClient
{
    private string $endpoint;

    private string $apiKey;

    public function __construct()
    {
        $model = config('services.gemini.model');
        $baseUrl = rtrim(config('services.gemini.base_url'), '/');

        $this->endpoint = sprintf('%s/%s:generateContent', $baseUrl, $model);
        $this->apiKey = (string) config('services.gemini.key');
    }

    /**
     * Send a prompt to Gemini and return the decoded JSON response.
     */
    public function generateText(string $prompt, array $options = []): array
    {
        $payload = array_merge([
            'contents' => [[
                'parts' => [['text' => $prompt]],
            ]],
        ], $options);

        /** @var Response $response */
        $response = Http::asJson()
            ->acceptJson()
            ->withHeaders([
                'x-goog-api-key' => $this->apiKey,
            ])
            ->post($this->endpoint.'?key='.$this->apiKey, $payload);

        $response->throw();

        return $response->json();
    }
}
