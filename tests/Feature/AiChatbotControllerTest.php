<?php

namespace Tests\Feature;

use App\Http\Controllers\AiChatbotController;
use App\Services\GeminiContentGenerator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class AiChatbotControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('students', fn ($table) => $table->id());
        Schema::create('staff', fn ($table) => $table->id());
        Schema::create('faculties', fn ($table) => $table->id());
        Schema::create('clinic_visits', function ($table) {
            $table->id();
            $table->date('visit_date');
        });
        Schema::create('medicines', function ($table) {
            $table->id();
            $table->unsignedInteger('stock');
            $table->unsignedInteger('minimum_stock');
        });
    }

    public function test_it_uses_the_fallback_model_after_the_primary_model_is_unavailable(): void
    {
        config([
            'services.gemini.key' => 'test-key',
            'services.gemini.model' => 'gemini-primary',
            'services.gemini.fallback_model' => 'gemini-fallback',
        ]);

        Http::fake([
            'https://primary.test' => Http::response(['error' => ['message' => 'High demand']], 503),
            'https://fallback.test' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Fallback response']]],
                ]],
            ]),
        ]);

        $primaryResponse = Http::get('https://primary.test');
        $fallbackResponse = Http::get('https://fallback.test');

        $generator = Mockery::mock(GeminiContentGenerator::class);
        $generator->shouldReceive('generate')
            ->once()
            ->with('test-key', 'gemini-primary', Mockery::type('string'), Mockery::type('array'))
            ->andReturn($primaryResponse);
        $generator->shouldReceive('generate')
            ->once()
            ->with('test-key', 'gemini-fallback', Mockery::type('string'), Mockery::type('array'))
            ->andReturn($fallbackResponse);

        $controller = new AiChatbotController($generator);
        $response = $controller->chat(request()->merge([
            'messages' => [['role' => 'user', 'content' => 'How do I record a clinic visit?']],
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Fallback response', $response->getData(true)['message']);
    }

    public function test_it_rejects_non_clinic_questions_without_calling_the_provider(): void
    {
        $generator = Mockery::mock(GeminiContentGenerator::class);
        $generator->shouldNotReceive('generate');

        $controller = new AiChatbotController($generator);
        $response = $controller->chat(request()->merge([
            'messages' => [[
                'role' => 'user',
                'content' => 'What is the capital of France?',
            ]],
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            'I can only help with TCC Clinic topics, such as clinic visits, patient records, medicine inventory, clinic workflows, and general health questions. Please ask a clinic-related question.',
            $response->getData(true)['message']
        );
    }

    public function test_it_does_not_treat_a_partial_keyword_as_clinic_related(): void
    {
        $generator = Mockery::mock(GeminiContentGenerator::class);
        $generator->shouldNotReceive('generate');

        $controller = new AiChatbotController($generator);
        $response = $controller->chat(request()->merge([
            'messages' => [[
                'role' => 'user',
                'content' => 'Can you paint a picture for me?',
            ]],
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString(
            'only help with TCC Clinic topics',
            $response->getData(true)['message']
        );
    }
}
