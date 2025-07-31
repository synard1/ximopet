<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Company;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

class RoleModalTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a company
        $this->company = Company::factory()->create([
            'status' => 'active'
        ]);

        // Create a user with Administrator role
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@test.com'
        ]);

        // Create Administrator role for the company
        $adminRole = Role::create([
            'name' => 'Administrator',
            'company_id' => $this->company->id,
            'guard_name' => 'web'
        ]);

        // Assign Administrator role to user
        $this->user->assignRole($adminRole->name);

        // Create some test permissions
        Permission::create(['name' => 'access master data', 'guard_name' => 'web']);
        Permission::create(['name' => 'read master data', 'guard_name' => 'web']);
        Permission::create(['name' => 'create master data', 'guard_name' => 'web']);
    }

    /** @test */
    public function it_can_update_existing_role_without_duplicate_entry_error()
    {
        // Create an existing role
        $existingRole = Role::create([
            'name' => 'TestRole',
            'company_id' => $this->company->id,
            'guard_name' => 'web'
        ]);

        // Login as the user
        $this->actingAs($this->user);

        // Test updating the role name
        Livewire::test(\App\Livewire\Permission\RoleModal::class)
            ->call('mountRole', 'TestRole')
            ->set('name', 'UpdatedTestRole')
            ->set('checked_permissions', ['access master data'])
            ->call('submit')
            ->assertDispatched('success');

        // Verify the role was updated
        $this->assertDatabaseHas('roles', [
            'id' => $existingRole->id,
            'name' => 'UpdatedTestRole',
            'company_id' => $this->company->id
        ]);
    }

    /** @test */
    public function it_prevents_creating_duplicate_role_names_in_same_company()
    {
        // Create an existing role
        Role::create([
            'name' => 'ExistingRole',
            'company_id' => $this->company->id,
            'guard_name' => 'web'
        ]);

        // Login as the user
        $this->actingAs($this->user);

        // Try to create another role with the same name
        Livewire::test(\App\Livewire\Permission\RoleModal::class)
            ->call('mountRole', '') // Create new role
            ->set('name', 'ExistingRole')
            ->set('checked_permissions', ['access master data'])
            ->call('submit')
            ->assertHasErrors(['name']);
    }

    /** @test */
    public function it_allows_same_role_name_in_different_companies()
    {
        // Create another company
        $company2 = Company::factory()->create(['status' => 'active']);

        // Create user in second company
        $user2 = User::factory()->create([
            'company_id' => $company2->id,
            'email' => 'admin2@test.com'
        ]);

        // Create Administrator role for second company
        $adminRole2 = Role::create([
            'name' => 'Administrator',
            'company_id' => $company2->id,
            'guard_name' => 'web'
        ]);
        $user2->assignRole($adminRole2->name);

        // Create a role in first company
        Role::create([
            'name' => 'SameNameRole',
            'company_id' => $this->company->id,
            'guard_name' => 'web'
        ]);

        // Login as user from second company
        $this->actingAs($user2);

        // Should be able to create role with same name in different company
        Livewire::test(\App\Livewire\Permission\RoleModal::class)
            ->call('mountRole', '') // Create new role
            ->set('name', 'SameNameRole')
            ->set('checked_permissions', ['access master data'])
            ->call('submit')
            ->assertDispatched('success');

        // Verify both roles exist
        $this->assertDatabaseHas('roles', [
            'name' => 'SameNameRole',
            'company_id' => $this->company->id
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'SameNameRole',
            'company_id' => $company2->id
        ]);
    }

    /** @test */
    public function it_handles_global_roles_correctly()
    {
        // Create a global role (company_id = null)
        $globalRole = Role::create([
            'name' => 'GlobalRole',
            'company_id' => null,
            'guard_name' => 'web'
        ]);

        // Login as the user
        $this->actingAs($this->user);

        // Should be able to update global role
        Livewire::test(\App\Livewire\Permission\RoleModal::class)
            ->call('mountRole', 'GlobalRole')
            ->set('name', 'UpdatedGlobalRole')
            ->set('checked_permissions', ['access master data'])
            ->call('submit')
            ->assertDispatched('success');

        // Verify the role was updated but remains global
        $this->assertDatabaseHas('roles', [
            'id' => $globalRole->id,
            'name' => 'UpdatedGlobalRole',
            'company_id' => null
        ]);
    }
}
