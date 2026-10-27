<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GeminiContentGenerator
{
    private const MAX_ATTEMPTS = 3;

    public function generate(string $apiKey, string $model, string $systemInstruction, array $contents): Response
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
            ])
                ->timeout(60)
                ->acceptJson()
                ->post($url, [
                    // The Gemini REST API uses JSON field names in lower camel case.
                    // Sending Laravel-style snake_case names causes the provider to reject
                    // the request as having unknown fields.
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => $systemInstruction],
                        ],
                    ],
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'max_output_tokens' => 700,
                    ],
                ]);

            if (! $this->shouldRetry($response) || $attempt === self::MAX_ATTEMPTS) {
                return $response;
            }

            usleep(250000 * $attempt);
        }

        return $response;
    }

    private function shouldRetry(Response $response): bool
    {
        return $response->serverError() || $response->status() === 429;
    }
}
