<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AiDatabaseServiceRefactored;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;

class AiDatabaseServiceRefactoredTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AiDatabaseServiceRefactored();
    }
    
    /**
     * Test that the service can be instantiated
     */
    public function test_service_can_be_instantiated()
    {
        $this->assertInstanceOf(AiDatabaseServiceRefactored::class, $this->service);
    }
    
    /**
     * Test isSuperAdmin method
     */
    public function test_is_super_admin_method()
    {
        // Create a mock user
        $user = Mockery::mock(User::class);
        
        // Test with SuperAdmin role
        $user->shouldReceive('getRoleNames')->andReturn(collect(['SuperAdmin']));
        $this->assertTrue($this->invokeMethod($this->service, 'isSuperAdmin', [$user]));
        
        // Test with regular user role
        $user->shouldReceive('getRoleNames')->andReturn(collect(['user']));
        $this->assertFalse($this->invokeMethod($this->service, 'isSuperAdmin', [$user]));
    }
    
    /**
     * Test getCompanyData method returns correct structure
     */
    public function test_get_company_data_returns_correct_structure()
    {
        // Create a mock user
        $user = Mockery::mock(User::class);
        $user->id = 1;
        $user->company_id = 1;
        $user->shouldReceive('getRoleNames')->andReturn(collect(['user']));
        
        // Mock the Auth facade
        Auth::shouldReceive('user')->andReturn($user);
        
        $result = $this->service->getCompanyData($user);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_companies', $result);
        $this->assertArrayHasKey('companies', $result);
        $this->assertIsArray($result['companies']);
    }
    
    /**
     * Test getLivestockSummary method returns correct structure
     */
    public function test_get_livestock_summary_returns_correct_structure()
    {
        // Create a mock user
        $user = Mockery::mock(User::class);
        $user->company_id = 1;
        $user->shouldReceive('getRoleNames')->andReturn(collect(['user']));
        
        // Mock the Auth facade
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('id')->andReturn(1);
        
        $result = $this->service->getLivestockSummary();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_livestock', $result);
        $this->assertArrayHasKey('active_batches', $result);
        $this->assertArrayHasKey('recent_mortality', $result);
        $this->assertArrayHasKey('feed_consumption', $result);
    }
    
    /**
     * Test getFinancialSummary method returns correct structure
     */
    public function test_get_financial_summary_returns_correct_structure()
    {
        // Create a mock user
        $user = Mockery::mock(User::class);
        $user->company_id = 1;
        $user->shouldReceive('getRoleNames')->andReturn(collect(['user']));
        
        // Mock the Auth facade
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('id')->andReturn(1);
        
        $result = $this->service->getFinancialSummary();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('monthly_expenses', $result);
        $this->assertArrayHasKey('monthly_revenue', $result);
        $this->assertArrayHasKey('feed_costs', $result);
        $this->assertArrayHasKey('recent_purchases', $result);
    }
    
    /**
     * Helper method to test private methods
     */
    protected function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}