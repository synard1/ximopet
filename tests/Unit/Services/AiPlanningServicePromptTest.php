<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AiPlanningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;

class AiPlanningServicePromptTest extends TestCase
{
    use RefreshDatabase;

    private AiPlanningService $planningService;
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->planningService = app(AiPlanningService::class);
        $this->reflection = new ReflectionClass($this->planningService);
    }

    /**
     * Test that buildOptimizedPrompt includes relevant data for confident_detailed_response
     */
    public function test_build_optimized_prompt_includes_data_for_confident_detailed_response()
    {
        // Arrange
        $message = 'tampilkan daftar perusahaan';
        $planningResult = [
            'language' => 'id',
            'strategy' => [
                'tone' => 'professional',
                'length' => 'medium'
            ]
        ];
        
        $reasoningResult = [
            'response_approach' => 'confident_detailed_response',
            'relevant_data' => [
                'companies' => [
                    'total_companies' => 2,
                    'companies' => [
                        [
                            'id' => '1',
                            'name' => 'Test Company 1',
                            'status' => 'active'
                        ],
                        [
                            'id' => '2',
                            'name' => 'Test Company 2',
                            'status' => 'active'
                        ]
                    ]
                ]
            ]
        ];

        // Act
        $buildPromptMethod = $this->reflection->getMethod('buildOptimizedPrompt');
        $buildPromptMethod->setAccessible(true);
        
        $prompt = $buildPromptMethod->invoke(
            $this->planningService,
            $message,
            $planningResult,
            $reasoningResult
        );

        // Assert
        $this->assertStringContainsString('CURRENT SYSTEM DATA', $prompt);
        $this->assertStringContainsString('Test Company 1', $prompt);
        $this->assertStringContainsString('Test Company 2', $prompt);
        $this->assertStringContainsString('total_companies', $prompt);
        $this->assertStringContainsString('IMPORTANT: Use the above data', $prompt);
    }

    /**
     * Test that buildOptimizedPrompt does NOT include data for minimal_response
     */
    public function test_build_optimized_prompt_excludes_data_for_minimal_response()
    {
        // Arrange
        $message = 'hello';
        $planningResult = [
            'language' => 'en',
            'strategy' => [
                'tone' => 'casual',
                'length' => 'very_short'
            ]
        ];
        
        $reasoningResult = [
            'response_approach' => 'minimal_response',
            'relevant_data' => [
                'companies' => [
                    'total_companies' => 1,
                    'companies' => [
                        [
                            'id' => '1',
                            'name' => 'Test Company',
                            'status' => 'active'
                        ]
                    ]
                ]
            ]
        ];

        // Act
        $buildPromptMethod = $this->reflection->getMethod('buildOptimizedPrompt');
        $buildPromptMethod->setAccessible(true);
        
        $prompt = $buildPromptMethod->invoke(
            $this->planningService,
            $message,
            $planningResult,
            $reasoningResult
        );

        // Assert
        $this->assertStringNotContainsString('CURRENT SYSTEM DATA', $prompt);
        $this->assertStringNotContainsString('Test Company', $prompt);
    }

    /**
     * Test that buildOptimizedPrompt does NOT include data for no_data_response
     */
    public function test_build_optimized_prompt_excludes_data_for_no_data_response()
    {
        // Arrange
        $message = 'show me something that does not exist';
        $planningResult = [
            'language' => 'en',
            'strategy' => [
                'tone' => 'professional',
                'length' => 'short'
            ]
        ];
        
        $reasoningResult = [
            'response_approach' => 'no_data_response',
            'relevant_data' => [] // No data available
        ];

        // Act
        $buildPromptMethod = $this->reflection->getMethod('buildOptimizedPrompt');
        $buildPromptMethod->setAccessible(true);
        
        $prompt = $buildPromptMethod->invoke(
            $this->planningService,
            $message,
            $planningResult,
            $reasoningResult
        );

        // Assert
        $this->assertStringNotContainsString('CURRENT SYSTEM DATA', $prompt);
        $this->assertStringContainsString('no relevant data is available', $prompt);
    }

    /**
     * Test that buildOptimizedPrompt handles empty relevant_data gracefully
     */
    public function test_build_optimized_prompt_handles_empty_data_gracefully()
    {
        // Arrange
        $message = 'tampilkan daftar perusahaan';
        $planningResult = [
            'language' => 'id',
            'strategy' => [
                'tone' => 'professional',
                'length' => 'medium'
            ]
        ];
        
        $reasoningResult = [
            'response_approach' => 'confident_detailed_response',
            'relevant_data' => [] // Empty data
        ];

        // Act
        $buildPromptMethod = $this->reflection->getMethod('buildOptimizedPrompt');
        $buildPromptMethod->setAccessible(true);
        
        $prompt = $buildPromptMethod->invoke(
            $this->planningService,
            $message,
            $planningResult,
            $reasoningResult
        );

        // Assert
        $this->assertStringNotContainsString('CURRENT SYSTEM DATA', $prompt);
        $this->assertStringContainsString('User query:', $prompt);
    }

    /**
     * Test that buildOptimizedPrompt handles missing relevant_data key
     */
    public function test_build_optimized_prompt_handles_missing_relevant_data_key()
    {
        // Arrange
        $message = 'tampilkan daftar perusahaan';
        $planningResult = [
            'language' => 'id',
            'strategy' => [
                'tone' => 'professional',
                'length' => 'medium'
            ]
        ];
        
        $reasoningResult = [
            'response_approach' => 'confident_detailed_response'
            // No relevant_data key
        ];

        // Act
        $buildPromptMethod = $this->reflection->getMethod('buildOptimizedPrompt');
        $buildPromptMethod->setAccessible(true);
        
        $prompt = $buildPromptMethod->invoke(
            $this->planningService,
            $message,
            $planningResult,
            $reasoningResult
        );

        // Assert - Should not crash and should not include data section
        $this->assertStringNotContainsString('CURRENT SYSTEM DATA', $prompt);
        $this->assertStringContainsString('User query:', $prompt);
    }
}