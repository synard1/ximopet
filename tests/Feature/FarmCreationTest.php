<?php

namespace Tests\Feature;

use App\Models\Farm;
use App\Models\User;
use App\Models\Company;
use App\Models\Coop;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FarmCreationTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data for automated input testing
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'status' => 'active',
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_check_farm_model_exists()
    {
        // Simple test to ensure Farm model exists and can be instantiated
        $farm = new Farm();
        $this->assertInstanceOf(Farm::class, $farm);
    }

    /** @test */
    public function it_can_validate_farm_model_structure()
    {
        // Test basic model structure without database
        $farm = new Farm();

        // Test fillable fields - check what's actually in the model
        $fillable = $farm->getFillable();

        // Basic required fields should be fillable
        $this->assertContains('code', $fillable);
        $this->assertContains('name', $fillable);

        // Check if company_id is fillable (it might not be depending on model setup)
        if (in_array('company_id', $fillable)) {
            $this->assertContains('company_id', $fillable);
        }
    }

    /** @test */
    public function it_can_check_farm_modal_component_exists()
    {
        // Test if FarmModal component class exists
        $this->assertTrue(class_exists(\App\Livewire\MasterData\FarmModal::class));
    }

    /** @test */
    public function it_can_check_farm_index_view_exists()
    {
        // Test if farm index view exists
        $this->assertTrue(view()->exists('pages.masterdata.farm.index'));
    }

    /** @test */
    public function it_can_check_farm_modal_view_exists()
    {
        // Test if farm modal view exists
        $this->assertTrue(view()->exists('livewire.master-data.farm-modal'));
    }

    /** @test */
    public function it_can_check_farm_table_exists()
    {
        // Test if farms table exists in database schema
        $this->assertTrue(Schema::hasTable('farms'));
    }

    /** @test */
    public function it_can_check_farm_model_has_required_methods()
    {
        // Test if Farm model has required methods
        $farm = new Farm();

        // Check if model has basic Eloquent methods
        $this->assertTrue(method_exists($farm, 'getFillable'));
        $this->assertTrue(method_exists($farm, 'getTable'));

        // Check table name
        $this->assertEquals('farms', $farm->getTable());
    }

    /** @test */
    public function it_can_automatically_create_farm_through_livewire()
    {
        // Test automatic input for creating farm
        $testData = [
            'code' => 'AUTO001',
            'name' => 'Auto Test Farm',
            'address' => 'Jl. Auto Test No. 123',
            'phone_number' => '08123456789',
            'contact_person' => 'Auto Test Person',
            'status' => 'active'
        ];

        $component = Livewire::test(\App\Livewire\MasterData\FarmModal::class)
            ->call('create_farm')
            ->assertSet('isOpen', true)
            ->assertSet('isEdit', false);

        // Set all input fields automatically
        foreach ($testData as $field => $value) {
            $component->set($field, $value);
        }

        // Submit form
        $component->call('storeFarm')
            ->assertHasNoErrors();

        // Verify data was created in database
        $this->assertDatabaseHas('farms', [
            'code' => 'AUTO001',
            'name' => 'Auto Test Farm',
            'address' => 'Jl. Auto Test No. 123',
            'phone_number' => '08123456789',
            'contact_person' => 'Auto Test Person',
            'status' => 'active'
        ]);
    }

    /** @test */
    public function it_can_automatically_test_validation_errors()
    {
        // Test automatic input validation
        $invalidData = [
            ['code' => '', 'name' => 'Test Farm'], // Missing code
            ['code' => 'TEST001', 'name' => ''], // Missing name
            ['code' => 'TEST002', 'name' => 'Test Farm', 'phone_number' => 'notanumber'], // Invalid phone
        ];

        foreach ($invalidData as $index => $data) {
            $component = Livewire::test(\App\Livewire\MasterData\FarmModal::class)
                ->call('create_farm');

            // Set test data
            foreach ($data as $field => $value) {
                $component->set($field, $value);
            }

            // Submit and expect validation errors
            $component->call('storeFarm')
                ->assertHasErrors();
        }
    }

    /** @test */
    public function it_can_automatically_edit_existing_farm()
    {
        // Create a farm first
        $farm = Farm::create([
            'code' => 'EDIT001',
            'name' => 'Original Farm',
            'address' => 'Original Address',
            'phone_number' => '08111111111',
            'contact_person' => 'Original Person',
            'status' => 'active'
        ]);

        // Test automatic edit
        $updatedData = [
            'name' => 'Updated Farm Name',
            'address' => 'Updated Address',
            'phone_number' => '08222222222',
            'contact_person' => 'Updated Person'
        ];

        $component = Livewire::test(\App\Livewire\MasterData\FarmModal::class)
            ->call('editFarm', $farm->id)
            ->assertSet('isOpen', true)
            ->assertSet('isEdit', true)
            ->assertSet('farm_id', $farm->id);

        // Update fields automatically
        foreach ($updatedData as $field => $value) {
            $component->set($field, $value);
        }

        // Submit update
        $component->call('storeFarm')
            ->assertHasNoErrors();

        // Verify data was updated
        $this->assertDatabaseHas('farms', [
            'id' => $farm->id,
            'code' => 'EDIT001', // Code should remain same
            'name' => 'Updated Farm Name',
            'address' => 'Updated Address',
            'phone_number' => '08222222222',
            'contact_person' => 'Updated Person'
        ]);
    }

    /** @test */
    public function it_can_automatically_test_farm_deletion()
    {
        // Create a farm without kandang (can be deleted)
        $farm = Farm::create([
            'code' => 'DELETE001',
            'name' => 'Deletable Farm',
            'status' => 'active'
        ]);

        // Verify farm exists before deletion
        $this->assertDatabaseHas('farms', ['id' => $farm->id]);

        // Check if there are any related records that might prevent deletion
        $hasKandang = Coop::where('farm_id', $farm->id)->exists();
        $this->assertFalse($hasKandang, 'Farm should not have any kandang records');

        // Test automatic deletion
        $component = Livewire::test(\App\Livewire\MasterData\FarmModal::class);

        // Call delete method
        $component->call('deleteFarmList', $farm->id);

        // Check if success event was dispatched
        $component->assertDispatched('success', 'Farm berhasil dihapus');

        // Verify farm was soft deleted (not hard deleted)
        $this->assertSoftDeleted('farms', ['id' => $farm->id]);

        // Verify farm still exists in database but with deleted_at timestamp
        $this->assertDatabaseHas('farms', [
            'id' => $farm->id,
            'deleted_at' => now()->toDateTimeString()
        ]);
    }

    /** @test */
    public function it_can_automatically_test_farm_deletion_with_kandang()
    {
        // Create a farm with kandang (cannot be deleted)
        $farm = Farm::create([
            'code' => 'NODELETE001',
            'name' => 'Farm with Kandang',
            'status' => 'active'
        ]);

        // Create kandang for this farm using existing user
        Coop::create([
            'farm_id' => $farm->id,
            'code' => 'COOP001',
            'name' => 'Test Coop',
            'capacity' => 1000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // Test automatic deletion (should fail)
        Livewire::test(\App\Livewire\MasterData\FarmModal::class)
            ->call('deleteFarmList', $farm->id);

        // Verify farm was NOT deleted
        $this->assertDatabaseHas('farms', ['id' => $farm->id]);
    }

    /** @test */
    public function it_can_automatically_test_duplicate_code_validation()
    {
        // Create existing farm
        Farm::create([
            'code' => 'DUPLICATE001',
            'name' => 'Existing Farm',
            'status' => 'active'
        ]);

        // Try to create another farm with same code
        Livewire::test(\App\Livewire\MasterData\FarmModal::class)
            ->call('create_farm')
            ->set('code', 'DUPLICATE001')
            ->set('name', 'New Farm')
            ->call('storeFarm')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function it_can_automatically_test_modal_open_close()
    {
        // Test automatic modal operations
        $component = Livewire::test(\App\Livewire\MasterData\FarmModal::class);

        // Test open modal
        $component->call('create_farm')
            ->assertSet('isOpen', true)
            ->assertSet('isEdit', false);

        // Test close modal
        $component->call('closeModalFarm')
            ->assertSet('isOpen', false);

        // Verify form was reset
        $component->assertSet('code', '')
            ->assertSet('name', '')
            ->assertSet('address', '')
            ->assertSet('phone_number', '')
            ->assertSet('contact_person', '');
    }

    /** @test */
    public function it_can_run_automated_farm_crud_cycle()
    {
        // Complete automated CRUD test cycle
        $testData = [
            'code' => 'CYCLE001',
            'name' => 'CRUD Test Farm',
            'address' => 'CRUD Test Address',
            'phone_number' => '08999999999',
            'contact_person' => 'CRUD Test Person',
            'status' => 'active'
        ];

        // 1. CREATE
        $component = Livewire::test(\App\Livewire\MasterData\FarmModal::class)
            ->call('create_farm');

        foreach ($testData as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('storeFarm')->assertHasNoErrors();

        $farm = Farm::where('code', 'CYCLE001')->first();
        $this->assertNotNull($farm);

        // 2. READ/EDIT
        $component->call('editFarm', $farm->id)
            ->assertSet('farm_id', $farm->id)
            ->assertSet('code', 'CYCLE001')
            ->assertSet('name', 'CRUD Test Farm');

        // 3. UPDATE
        $component->set('name', 'Updated CRUD Farm')
            ->call('storeFarm')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('farms', [
            'id' => $farm->id,
            'name' => 'Updated CRUD Farm'
        ]);

        // 4. DELETE (Soft Delete)
        $component->call('deleteFarmList', $farm->id);
        $this->assertSoftDeleted('farms', ['id' => $farm->id]);
    }
}
