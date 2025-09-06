<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ChatContextService;
use App\Helpers\OllamaChatContextHelper;
use App\Helpers\OllamaChatQueryHelper;
use App\Models\User;
use App\Models\Company;
use App\Models\Farm;
use App\Models\Kandang;
use App\Models\Livestock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;

class ChatContextServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ChatContextService $chatContextService;
    protected $contextHelper;
    protected $queryHelper;
    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company and user
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);

        // Mock helpers
        $this->contextHelper = Mockery::mock(OllamaChatContextHelper::class);
        $this->queryHelper = Mockery::mock(OllamaChatQueryHelper::class);

        // Create ChatContextService with mocked dependencies
        $this->chatContextService = new ChatContextService(
            $this->contextHelper,
            $this->queryHelper
        );

        Auth::login($this->user);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_builds_basic_context_for_user()
    {
        // Mock context helper to return basic context
        $expectedContext = "You are an AI assistant for a farm management system.\n\n";
        $expectedContext .= "Company: {$this->company->name}\n";
        $expectedContext .= "User: {$this->user->name}\n";
        $expectedContext .= "Role: Farm Manager\n\n";
        $expectedContext .= "Please provide helpful information about farm management, livestock care, and agricultural practices.";

        $this->contextHelper
            ->shouldReceive('getBasicContext')
            ->once()
            ->with($this->user->id, $this->company->id)
            ->andReturn($expectedContext);

        $context = $this->chatContextService->buildContext($this->user->id, $this->company->id);

        $this->assertIsString($context);
        $this->assertStringContains($this->company->name, $context);
        $this->assertStringContains($this->user->name, $context);
        $this->assertStringContains('farm management', $context);
    }

    public function test_builds_context_with_farm_data()
    {
        // Create test farm data
        $farm = Farm::factory()->create(['company_id' => $this->company->id]);
        $kandang = Kandang::factory()->create(['farm_id' => $farm->id]);
        $livestock = Livestock::factory()->create(['kandang_id' => $kandang->id]);

        $farmContext = "Farm Information:\n";
        $farmContext .= "- Farm: {$farm->name}\n";
        $farmContext .= "- Kandang: {$kandang->name}\n";
        $farmContext .= "- Active Livestock: 1\n";

        $this->contextHelper
            ->shouldReceive('getBasicContext')
            ->once()
            ->andReturn('Basic context');

        $this->contextHelper
            ->shouldReceive('getFarmContext')
            ->once()
            ->with($this->company->id)
            ->andReturn($farmContext);

        $context = $this->chatContextService->buildContextWithFarmData(
            $this->user->id,
            $this->company->id
        );

        $this->assertStringContains('Basic context', $context);
        $this->assertStringContains($farm->name, $context);
        $this->assertStringContains($kandang->name, $context);
    }

    public function test_builds_context_with_livestock_focus()
    {
        $livestock = Livestock::factory()->create();

        $livestockContext = "Livestock Focus:\n";
        $livestockContext .= "- Livestock ID: {$livestock->id}\n";
        $livestockContext .= "- Current Status: Active\n";
        $livestockContext .= "- Health Status: Good\n";

        $this->contextHelper
            ->shouldReceive('getBasicContext')
            ->once()
            ->andReturn('Basic context');

        $this->contextHelper
            ->shouldReceive('getLivestockContext')
            ->once()
            ->with($livestock->id)
            ->andReturn($livestockContext);

        $context = $this->chatContextService->buildContextWithLivestock(
            $this->user->id,
            $this->company->id,
            $livestock->id
        );

        $this->assertStringContains('Basic context', $context);
        $this->assertStringContains("Livestock ID: {$livestock->id}", $context);
        $this->assertStringContains('Health Status: Good', $context);
    }

    public function test_processes_query_for_relevant_data()
    {
        $query = "Show me feed consumption for my chickens";

        $relevantData = [
            'feed_data' => [
                'total_consumption' => '500kg',
                'daily_average' => '25kg',
                'feed_type' => 'Starter Feed'
            ],
            'livestock_count' => 100
        ];

        $this->queryHelper
            ->shouldReceive('analyzeQuery')
            ->once()
            ->with($query, $this->company->id)
            ->andReturn($relevantData);

        $result = $this->chatContextService->processQuery($query, $this->company->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('feed_data', $result);
        $this->assertArrayHasKey('livestock_count', $result);
        $this->assertEquals('500kg', $result['feed_data']['total_consumption']);
    }

    public function test_builds_context_with_financial_data()
    {
        $financialContext = "Financial Overview:\n";
        $financialContext .= "- Monthly Revenue: $15,000\n";
        $financialContext .= "- Feed Costs: $8,000\n";
        $financialContext .= "- Profit Margin: 47%\n";

        $this->contextHelper
            ->shouldReceive('getBasicContext')
            ->once()
            ->andReturn('Basic context');

        $this->contextHelper
            ->shouldReceive('getFinancialContext')
            ->once()
            ->with($this->company->id)
            ->andReturn($financialContext);

        $context = $this->chatContextService->buildContextWithFinancials(
            $this->user->id,
            $this->company->id
        );

        $this->assertStringContains('Basic context', $context);
        $this->assertStringContains('Monthly Revenue: $15,000', $context);
        $this->assertStringContains('Profit Margin: 47%', $context);
    }

    public function test_builds_context_with_health_monitoring()
    {
        $healthContext = "Health Monitoring:\n";
        $healthContext .= "- Active Health Alerts: 2\n";
        $healthContext .= "- Vaccination Schedule: Up to date\n";
        $healthContext .= "- Mortality Rate: 1.2%\n";

        $this->contextHelper
            ->shouldReceive('getBasicContext')
            ->once()
            ->andReturn('Basic context');

        $this->contextHelper
            ->shouldReceive('getHealthContext')
            ->once()
            ->with($this->company->id)
            ->andReturn($healthContext);

        $context = $this->chatContextService->buildContextWithHealth(
            $this->user->id,
            $this->company->id
        );

        $this->assertStringContains('Basic context', $context);
        $this->assertStringContains('Active Health Alerts: 2', $context);
        $this->assertStringContains('Mortality Rate: 1.2%', $context);
    }

    public function test_builds_comprehensive_context()
    {
        // Mock all context types
        $this->contextHelper
            ->shouldReceive('getBasicContext')
            ->once()
            ->andReturn('Basic context');

        $this->contextHelper
            ->shouldReceive('getFarmContext')
            ->once()
            ->andReturn('Farm context');

        $this->contextHelper
            ->shouldReceive('getFinancialContext')
            ->once()
            ->andReturn('Financial context');

        $this->contextHelper
            ->shouldReceive('getHealthContext')
            ->once()
            ->andReturn('Health context');

        $context = $this->chatContextService->buildComprehensiveContext(
            $this->user->id,
            $this->company->id
        );

        $this->assertStringContains('Basic context', $context);
        $this->assertStringContains('Farm context', $context);
        $this->assertStringContains('Financial context', $context);
        $this->assertStringContains('Health context', $context);
    }

    public function test_extracts_intent_from_user_message()
    {
        $messages = [
            'Show me today\'s feed consumption' => 'feed_inquiry',
            'How many chickens died this week?' => 'mortality_inquiry',
            'What\'s my profit this month?' => 'financial_inquiry',
            'Add 100 chickens to kandang A' => 'livestock_management',
            'Schedule vaccination for next week' => 'health_management'
        ];

        foreach ($messages as $message => $expectedIntent) {
            $this->queryHelper
                ->shouldReceive('extractIntent')
                ->with($message)
                ->andReturn($expectedIntent);

            $intent = $this->chatContextService->extractIntent($message);
            $this->assertEquals($expectedIntent, $intent);
        }
    }

    public function test_formats_context_for_different_ai_providers()
    {
        $basicContext = 'You are a farm management assistant.';

        $this->contextHelper
            ->shouldReceive('getBasicContext')
            ->andReturn($basicContext);

        // Test Ollama format
        $ollamaContext = $this->chatContextService->formatContextForProvider(
            $basicContext,
            'ollama'
        );

        // Test OpenWebUI format
        $openwebuiContext = $this->chatContextService->formatContextForProvider(
            $basicContext,
            'openwebui'
        );

        $this->assertIsString($ollamaContext);
        $this->assertIsString($openwebuiContext);
        $this->assertStringContains('farm management assistant', $ollamaContext);
        $this->assertStringContains('farm management assistant', $openwebuiContext);
    }

    public function test_handles_empty_context_gracefully()
    {
        $this->contextHelper
            ->shouldReceive('getBasicContext')
            ->once()
            ->andReturn('');

        $context = $this->chatContextService->buildContext($this->user->id, $this->company->id);

        $this->assertIsString($context);
        $this->assertNotEmpty($context); // Should provide fallback context
    }

    public function test_caches_context_for_performance()
    {
        $expectedContext = 'Cached context data';

        $this->contextHelper
            ->shouldReceive('getBasicContext')
            ->once() // Should only be called once due to caching
            ->andReturn($expectedContext);

        // First call
        $context1 = $this->chatContextService->buildContext($this->user->id, $this->company->id);

        // Second call (should use cache)
        $context2 = $this->chatContextService->buildContext($this->user->id, $this->company->id);

        $this->assertEquals($context1, $context2);
    }

    public function test_validates_user_access_to_company_data()
    {
        // Create different company
        $otherCompany = Company::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unauthorized access to company data');

        $this->chatContextService->buildContext($this->user->id, $otherCompany->id);
    }

    public function test_handles_missing_helper_dependencies()
    {
        // Create service without helpers
        $serviceWithoutHelpers = new ChatContextService(null, null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Context helper not available');

        $serviceWithoutHelpers->buildContext($this->user->id, $this->company->id);
    }
}