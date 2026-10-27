<?php

namespace Tests\Feature;

use App\Services\GeminiContentGenerator;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiContentGeneratorTest extends TestCase
{
    public function test_it_sends_gemini_request_fields_in_the_provider_format(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Hello']]],
                ]],
            ]),
        ]);

        app(GeminiContentGenerator::class)->generate(
            'test-key',
            'gemini-3.6-flash',
            'System instruction',
            [[
                'role' => 'user',
                'parts' => [['text' => 'Hello']],
            ]]
        );

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return array_key_exists('systemInstruction', $payload)
                && array_key_exists('generationConfig', $payload)
                && ! array_key_exists('system_instruction', $payload)
                && ! array_key_exists('generation_config', $payload);
        });
    }

    public function test_it_retries_a_transient_gemini_failure(): void
    {
        Http::fakeSequence()
            ->push(['error' => ['message' => 'High demand']], 503)
            ->push([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Hello']]],
                ]],
            ], 200);

        $response = app(GeminiContentGenerator::class)->generate(
            'test-key',
            'gemini-3.6-flash',
            'System instruction',
            [[
                'role' => 'user',
                'parts' => [['text' => 'Hello']],
            ]]
        );

        $this->assertTrue($response->successful());
        $this->assertSame('Hello', data_get($response->json(), 'candidates.0.content.parts.0.text'));
        Http::assertSentCount(2);
    }

    public function test_it_does_not_retry_a_provider_client_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => ['message' => 'Invalid request']], 400),
        ]);

        $response = app(GeminiContentGenerator::class)->generate(
            'test-key',
            'gemini-3.6-flash',
            'System instruction',
            [[
                'role' => 'user',
                'parts' => [['text' => 'Hello']],
            ]]
        );

        $this->assertSame(400, $response->status());
        Http::assertSentCount(1);
    }
}
