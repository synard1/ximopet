<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UuidMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run the UUID migrations
        $this->artisan('migrate', ['--force' => true]);
    }

    /** @test */
    public function users_table_has_uuid_primary_key()
    {
        $this->assertTrue(Schema::hasColumn('users', 'uuid'));
        $this->assertFalse(Schema::hasColumn('users', 'id'));

        // Check if uuid is primary key
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->assertNotNull($user->uuid);
        $this->assertIsString($user->uuid);
        $this->assertEquals(36, strlen($user->uuid)); // UUID v4 length
    }

    /** @test */
    public function permission_tables_use_uuid_for_model_references()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $role = Role::create(['name' => 'Test Role']);
        $permission = Permission::create(['name' => 'test permission']);

        // Test role assignment
        $user->assignRole($role);

        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->id,
            'model_id' => $user->uuid,
            'model_type' => User::class,
        ]);

        // Test permission assignment
        $user->givePermissionTo($permission);

        $this->assertDatabaseHas('model_has_permissions', [
            'permission_id' => $permission->id,
            'model_id' => $user->uuid,
            'model_type' => User::class,
        ]);
    }

    /** @test */
    public function foreign_key_references_use_uuid()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Test audit_trails table
        DB::table('audit_trails')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->uuid,
            'action' => 'test',
            'model_type' => 'Test',
            'model_name' => 'Test',
            'user_info' => json_encode(['name' => 'test']),
            'timestamp' => now(),
            'created_by' => $user->uuid,
            'table_name' => 'test',
        ]);

        $this->assertDatabaseHas('audit_trails', [
            'user_id' => $user->uuid,
            'created_by' => $user->uuid,
        ]);
    }

    /** @test */
    public function user_authentication_works_with_uuid()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertEquals($user->uuid, auth()->id());
    }

    /** @test */
    public function permission_system_works_with_uuid()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $role = Role::create(['name' => 'Admin']);
        $permission = Permission::create(['name' => 'manage users']);

        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->assertTrue($user->hasRole('Admin'));
        $this->assertTrue($user->hasPermissionTo('manage users'));
        $this->assertTrue($user->can('manage users'));
    }

    /** @test */
    public function user_relationships_work_with_uuid()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Test company relationship
        $company = DB::table('companies')->insertGetId([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Test Company',
            'status' => 'active',
            'created_by' => $user->uuid,
        ]);

        $this->assertDatabaseHas('companies', [
            'created_by' => $user->uuid,
        ]);
    }

    /** @test */
    public function uuid_mappings_table_created_correctly()
    {
        $this->assertTrue(Schema::hasTable('uuid_mappings'));

        $columns = Schema::getColumnListing('uuid_mappings');
        $this->assertContains('old_id', $columns);
        $this->assertContains('new_uuid', $columns);
        $this->assertContains('table_name', $columns);
    }

    /** @test */
    public function data_integrity_maintained_after_migration()
    {
        // Create test data before migration simulation
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $role = Role::create(['name' => 'Test Role']);
        $permission = Permission::create(['name' => 'test permission']);

        $user->assignRole($role);
        $user->givePermissionTo($permission);

        // Verify data integrity
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->id,
            'model_id' => $user->uuid,
            'model_type' => User::class,
        ]);

        $this->assertDatabaseHas('model_has_permissions', [
            'permission_id' => $permission->id,
            'model_id' => $user->uuid,
            'model_type' => User::class,
        ]);
    }

    /** @test */
    public function performance_impact_is_acceptable()
    {
        // Create multiple users to test performance
        $users = [];
        $startTime = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $users[] = User::create([
                'name' => "User $i",
                'email' => "user$i@example.com",
                'password' => bcrypt('password'),
            ]);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should complete within 5 seconds
        $this->assertLessThan(5, $executionTime, 'User creation performance is acceptable');

        // Test query performance
        $startTime = microtime(true);
        $user = User::where('email', 'user50@example.com')->first();
        $endTime = microtime(true);
        $queryTime = $endTime - $startTime;

        // Should complete within 1 second
        $this->assertLessThan(1, $queryTime, 'User query performance is acceptable');
        $this->assertNotNull($user);
    }
}
