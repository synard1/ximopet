<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Models\Farm;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_application_returns_a_successful_response()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function it_can_create_a_farm()
    {
        $farmData = [
            'code' => 'FARM001',
            'name' => 'Test Farm',
            'address' => 'Test Address',
            'phone_number' => '08123456789',
            'contact_person' => 'John Doe',
            'status' => 'active'
        ];

        Livewire::test('master-data.farm-modal')
            ->set('code', $farmData['code'])
            ->set('name', $farmData['name'])
            ->set('address', $farmData['address'])
            ->set('phone_number', $farmData['phone_number'])
            ->set('contact_person', $farmData['contact_person'])
            ->set('status', $farmData['status'])
            ->call('storeFarm')
            ->assertDispatchedBrowserEvent('success');

        $this->assertDatabaseHas('farms', $farmData);
    }
}
