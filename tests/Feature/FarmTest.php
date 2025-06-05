<?php

namespace Tests\Feature;

// use Tests\TestCase;
use App\Models\Farm;
use App\Models\User;
use App\Models\Kandang;
use App\Models\InventoryLocation;
use App\Models\FarmOperator;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
// use PHPUnit\Framework\TestCase;
use Tests\TestCase;

class FarmTest extends TestCase
{
    // use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    // public function test_the_application_returns_a_successful_response()
    // {
    //     $response = $this->get('/');

    //     $response->assertStatus(200);
    // }

    /** @test */
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
            ->call('storeFarm');
        // ->assertDispatch('success');

        $this->assertDatabaseHas('farms', $farmData);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        Livewire::test('master-data.farm-modal')
            ->set('code', '')
            ->set('name', '')
            ->call('storeFarm')
            ->assertHasErrors(['code', 'name']);
    }

    /** @test */
    public function it_validates_unique_code()
    {
        // Create a farm first
        Farm::create([
            'code' => 'FARM001',
            'name' => 'Existing Farm',
            'status' => 'active'
        ]);

        // Try to create another farm with the same code
        Livewire::test('master-data.farm-modal')
            ->set('code', 'FARM001')
            ->set('name', 'New Farm')
            ->call('storeFarm')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function it_can_edit_a_farm()
    {
        $farm = Farm::create([
            'code' => 'FARM001',
            'name' => 'Original Farm',
            'status' => 'active'
        ]);

        Livewire::test('master-data.farm-modal')
            ->call('editFarm', $farm->id)
            ->set('name', 'Updated Farm')
            ->call('storeFarm');
        // ->assertDispatchedBrowserEvent('success');

        $this->assertDatabaseHas('farms', [
            'id' => $farm->id,
            'name' => 'Updated Farm'
        ]);
    }

    /** @test */
    public function it_can_delete_a_farm_without_related_data()
    {
        $farm = Farm::create([
            'code' => 'FARM001',
            'name' => 'Test Farm',
            'status' => 'active'
        ]);

        Livewire::test('master-data.farm-modal')
            ->call('deleteFarmList', $farm->id);
        // ->assertDispatchedBrowserEvent('success');

        $this->assertSoftDeleted('farms', ['id' => $farm->id]);
    }

    /** @test */
    public function it_cannot_delete_farm_with_kandang()
    {
        $farm = Farm::create([
            'code' => 'FARM001',
            'name' => 'Test Farm',
            'status' => 'active'
        ]);

        Kandang::create([
            'kode' => 'KAN001',
            'farm_id' => $farm->id,
            'nama' => 'Test Kandang',
            'jumlah' => 0,
            'berat' => 0,
            'kapasitas' => 10000,
            'status' => 'active'
        ]);

        Livewire::test('master-data.farm-modal')
            ->call('deleteFarmList', $farm->id);
        // ->assertDispatchedBrowserEvent('error');

        $this->assertDatabaseHas('farms', ['id' => $farm->id]);
    }

    /** @test */
    public function it_has_relationships_with_other_models()
    {
        $farm = Farm::create([
            'code' => 'FARM001',
            'name' => 'Test Farm',
            'status' => 'active'
        ]);

        // Test kandang relationship
        $kandang = Kandang::create([
            'kode' => 'KAN001',
            'farm_id' => $farm->id,
            'nama' => 'Test Kandang',
            'jumlah' => 0,
            'berat' => 0,
            'kapasitas' => 10000,
            'status' => 'active'
        ]);
        $this->assertTrue($farm->kandangs->contains($kandang));

        // Test operator relationship
        $operator = User::factory()->create();
        FarmOperator::create([
            'farm_id' => $farm->id,
            'user_id' => $operator->id
        ]);
        $this->assertTrue($farm->operators->contains($operator));
    }

    /** @test */
    public function it_validates_phone_number_format()
    {
        Livewire::test('master-data.farm-modal')
            ->set('code', 'FARM001')
            ->set('name', 'Test Farm')
            ->set('phone_number', 'invalid-phone')
            ->call('storeFarm')
            ->assertHasErrors(['phone_number']);
    }

    /** @test */
    public function it_handles_modal_state_correctly()
    {
        $component = Livewire::test('master-data.farm-modal');

        // Test opening modal
        $component->call('create')
            ->assertSet('isOpen', true)
            ->assertSet('isEdit', false);

        // Test closing modal
        $component->call('closeModalFarm')
            ->assertSet('isOpen', false);

        // Test edit mode
        $farm = Farm::create([
            'code' => 'FARM001',
            'name' => 'Test Farm',
            'status' => 'active'
        ]);

        $component->call('editFarm', $farm->id)
            ->assertSet('isOpen', true)
            ->assertSet('isEdit', true)
            ->assertSet('farm_id', $farm->id);
    }
}
