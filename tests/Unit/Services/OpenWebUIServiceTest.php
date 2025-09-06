<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\OpenWebUIService;
use App\Models\AiProvider;
use App\Models\Company;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Mockery;

class OpenWebUIServiceTest extends TestCase
{
    protected OpenWebUIService $openWebUIService;
    protected Company $company;
    protected AiProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->provider = AiProvider::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'openwebui',
            'configuration' => [
                'base_url' => 'https://openwebui.example.com',
                'api_key' => 'test-api-key',
                'model' => 'llama2',
                'timeout' => 30
            ],
            'is_active' => true
        ]);

        $this->openWebUIService = new OpenWebUIService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_send_message_successfully()
    {
        // Mock HTTP response
        Http::fake([
            'openwebui.example.com/api/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Hello! How can I help you with your farm management today?'
                        ]
                    ]
                ],
                'usage' => [
                    'total_tokens' => 25
                ]
            ], 200)
        ]);

        $response = $this->openWebUIService->sendMessage(
            'Hello, I need help with my farm',
            'Farm context: You are managing a poultry farm',
            $this->provider
        );

        $this->assertIsArray($response);
        $this->assertArrayHasKey('response', $response);
        $this->assertArrayHasKey('processing_time', $response);
        $this->assertArrayHasKey('tokens_used', $response);
        $this->assertEquals('Hello! How can I help you with your farm management today?', $response['response']);
        $this->assertEquals(25, $response['tokens_used']);
        $this->assertIsFloat($response['processing_time']);
    }

    public function test_handles_api_error_response()
    {
        // Mock HTTP error response
        Http::fake([
            'openwebui.example.com/api/chat/completions' => Http::response([
                'error' => [
                    'message' => 'Invalid API key'
                ]
            ], 401)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('OpenWebUI API Error: Invalid API key');

        $this->openWebUIService->sendMessage(
            'Hello',
            'Context',
            $this->provider
        );
    }

    public function test_handles_network_timeout()
    {
        // Mock HTTP timeout
        Http::fake([
            'openwebui.example.com/api/chat/completions' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            }
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('OpenWebUI connection failed');

        $this->openWebUIService->sendMessage(
            'Hello',
            'Context',
            $this->provider
        );
    }

    public function test_can_test_connection_successfully()
    {
        // Mock successful health check
        Http::fake([
            'openwebui.example.com/api/health' => Http::response([
                'status' => 'ok'
            ], 200)
        ]);

        $result = $this->openWebUIService->testConnection($this->provider);

        $this->assertTrue($result['success']);
        $this->assertEquals('Connection successful', $result['message']);
    }

    public function test_connection_test_fails_with_invalid_credentials()
    {
        // Mock authentication failure
        Http::fake([
            'openwebui.example.com/api/health' => Http::response([
                'error' => 'Unauthorized'
            ], 401)
        ]);

        $result = $this->openWebUIService->testConnection($this->provider);

        $this->assertFalse($result['success']);
        $this->assertStringContains('Authentication failed', $result['message']);
    }

    public function test_can_get_available_models()
    {
        // Mock models endpoint
        Http::fake([
            'openwebui.example.com/api/models' => Http::response([
                'data' => [
                    [
                        'id' => 'llama2',
                        'name' => 'Llama 2',
                        'description' => 'Meta Llama 2 model'
                    ],
                    [
                        'id' => 'codellama',
                        'name' => 'Code Llama',
                        'description' => 'Code generation model'
                    ]
                ]
            ], 200)
        ]);

        $models = $this->openWebUIService->getAvailableModels($this->provider);

        $this->assertIsArray($models);
        $this->assertCount(2, $models);
        $this->assertEquals('llama2', $models[0]['id']);
        $this->assertEquals('codellama', $models[1]['id']);
    }

    public function test_caches_available_models()
    {
        // Mock models endpoint (should only be called once due to caching)
        Http::fake([
            'openwebui.example.com/api/models' => Http::response([
                'data' => [
                    ['id' => 'llama2', 'name' => 'Llama 2']
                ]
            ], 200)
        ]);

        // First call
        $models1 = $this->openWebUIService->getAvailableModels($this->provider);

        // Second call (should use cache)
        $models2 = $this->openWebUIService->getAvailableModels($this->provider);

        $this->assertEquals($models1, $models2);

        // Verify HTTP was only called once
        Http::assertSentCount(1);
    }

    public function test_validates_provider_configuration()
    {
        // Create provider with missing configuration
        $invalidProvider = AiProvider::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'openwebui',
            'configuration' => [
                'base_url' => 'https://openwebui.example.com'
                // Missing api_key
            ]
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid OpenWebUI configuration');

        $this->openWebUIService->sendMessage('Hello', 'Context', $invalidProvider);
    }

    public function test_formats_messages_correctly_for_api()
    {
        Http::fake([
            'openwebui.example.com/api/chat/completions' => function ($request) {
                $body = json_decode($request->body(), true);

                // Verify message format
                $this->assertArrayHasKey('messages', $body);
                $this->assertCount(2, $body['messages']);

                // System message
                $this->assertEquals('system', $body['messages'][0]['role']);
                $this->assertStringContains('farm management', $body['messages'][0]['content']);

                // User message
                $this->assertEquals('user', $body['messages'][1]['role']);
                $this->assertEquals('Hello AI', $body['messages'][1]['content']);

                return Http::response([
                    'choices' => [['message' => ['content' => 'Response']]],
                    'usage' => ['total_tokens' => 10]
                ]);
            }
        ]);

        $this->openWebUIService->sendMessage(
            'Hello AI',
            'You are a farm management assistant',
            $this->provider
        );
    }

    public function test_respects_model_configuration()
    {
        Http::fake([
            'openwebui.example.com/api/chat/completions' => function ($request) {
                $body = json_decode($request->body(), true);

                // Verify model is set correctly
                $this->assertEquals('llama2', $body['model']);

                return Http::response([
                    'choices' => [['message' => ['content' => 'Response']]],
                    'usage' => ['total_tokens' => 10]
                ]);
            }
        ]);

        $this->openWebUIService->sendMessage('Hello', 'Context', $this->provider);
    }

    public function test_includes_proper_headers()
    {
        Http::fake([
            'openwebui.example.com/api/chat/completions' => function ($request) {
                // Verify headers
                $this->assertEquals('Bearer test-api-key', $request->header('Authorization')[0]);
                $this->assertEquals('application/json', $request->header('Content-Type')[0]);
                $this->assertStringContains('Demo51-AI-Chat', $request->header('User-Agent')[0]);

                return Http::response([
                    'choices' => [['message' => ['content' => 'Response']]],
                    'usage' => ['total_tokens' => 10]
                ]);
            }
        ]);

        $this->openWebUIService->sendMessage('Hello', 'Context', $this->provider);
    }

    public function test_handles_streaming_response()
    {
        // Create provider with streaming enabled
        $streamingProvider = AiProvider::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'openwebui',
            'configuration' => [
                'base_url' => 'https://openwebui.example.com',
                'api_key' => 'test-api-key',
                'model' => 'llama2',
                'stream' => true
            ]
        ]);

        Http::fake([
            'openwebui.example.com/api/chat/completions' => function ($request) {
                $body = json_decode($request->body(), true);

                // Verify streaming is enabled
                $this->assertTrue($body['stream']);

                return Http::response([
                    'choices' => [['message' => ['content' => 'Streamed response']]],
                    'usage' => ['total_tokens' => 15]
                ]);
            }
        ]);

        $response = $this->openWebUIService->sendMessage('Hello', 'Context', $streamingProvider);

        $this->assertEquals('Streamed response', $response['response']);
    }

    public function test_handles_rate_limiting()
    {
        // Mock rate limit response
        Http::fake([
            'openwebui.example.com/api/chat/completions' => Http::response([
                'error' => [
                    'message' => 'Rate limit exceeded'
                ]
            ], 429)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Rate limit exceeded');

        $this->openWebUIService->sendMessage('Hello', 'Context', $this->provider);
    }

    public function test_logs_api_interactions()
    {
        Http::fake([
            'openwebui.example.com/api/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'Response']]],
                'usage' => ['total_tokens' => 10]
            ])
        ]);

        // Capture logs
        $this->expectsEvents(\Illuminate\Log\Events\MessageLogged::class);

        $this->openWebUIService->sendMessage('Hello', 'Context', $this->provider);
    }
}