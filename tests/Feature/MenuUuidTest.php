<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Menu;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class MenuUuidTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user for created_by fields
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_menu_creates_with_uuid()
    {
        $menu = Menu::create([
            'name' => 'test-menu',
            'label' => 'Test Menu',
            'route' => '/test',
            'location' => 'sidebar',
            'order_number' => 1,
            'is_active' => true
        ]);

        // Verify UUID properties
        $this->assertIsString($menu->id);
        $this->assertEquals(36, strlen($menu->id));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $menu->id);

        // Verify UUID is unique
        $this->assertTrue(Str::isUuid($menu->id));
    }

    public function test_menu_relationships_work_with_uuid()
    {
        $parent = Menu::create([
            'name' => 'parent-menu',
            'label' => 'Parent Menu',
            'route' => '/parent',
            'location' => 'sidebar',
            'order_number' => 1
        ]);

        $child = Menu::create([
            'parent_id' => $parent->id,
            'name' => 'child-menu',
            'label' => 'Child Menu',
            'route' => '/parent/child',
            'location' => 'sidebar',
            'order_number' => 1
        ]);

        // Verify parent-child relationship
        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertTrue($parent->children->contains($child));
        $this->assertEquals($parent->id, $child->parent->id);

        // Verify UUIDs are properly stored
        $this->assertTrue(Str::isUuid($parent->id));
        $this->assertTrue(Str::isUuid($child->parent_id));
    }

    public function test_menu_role_relationship_with_uuid()
    {
        $menu = Menu::create([
            'name' => 'test-menu',
            'label' => 'Test Menu',
            'route' => '/test',
            'location' => 'sidebar'
        ]);

        $role = Role::create([
            'name' => 'test-role',
            'guard_name' => 'web'
        ]);

        // Attach role to menu
        $menu->roles()->attach($role->id);

        // Verify relationship
        $this->assertTrue($menu->roles->contains($role));
        $this->assertTrue($role->menus->contains($menu));

        // Verify UUIDs in pivot table
        $this->assertDatabaseHas('menu_role', [
            'menu_id' => $menu->id,
            'role_id' => $role->id
        ]);
    }

    public function test_menu_permission_relationship_with_uuid()
    {
        $menu = Menu::create([
            'name' => 'test-menu',
            'label' => 'Test Menu',
            'route' => '/test',
            'location' => 'sidebar'
        ]);

        $permission = Permission::create([
            'name' => 'test-permission',
            'guard_name' => 'web'
        ]);

        // Attach permission to menu
        $menu->permissions()->attach($permission->id);

        // Verify relationship
        $this->assertTrue($menu->permissions->contains($permission));

        // Verify UUIDs in pivot table
        $this->assertDatabaseHas('menu_permission', [
            'menu_id' => $menu->id,
            'permission_id' => $permission->id
        ]);
    }

    public function test_menu_hierarchical_structure_with_uuid()
    {
        // Create a complex menu structure
        $dashboard = Menu::create([
            'name' => 'dashboard',
            'label' => 'Dashboard',
            'route' => '/dashboard',
            'location' => 'sidebar',
            'order_number' => 1
        ]);

        $masterData = Menu::create([
            'name' => 'master-data',
            'label' => 'Master Data',
            'route' => '#',
            'location' => 'sidebar',
            'order_number' => 2
        ]);

        $users = Menu::create([
            'parent_id' => $masterData->id,
            'name' => 'users',
            'label' => 'Users',
            'route' => '/users',
            'location' => 'sidebar',
            'order_number' => 1
        ]);

        $roles = Menu::create([
            'parent_id' => $masterData->id,
            'name' => 'roles',
            'label' => 'Roles',
            'route' => '/roles',
            'location' => 'sidebar',
            'order_number' => 2
        ]);

        // Verify hierarchical structure
        $this->assertNull($dashboard->parent_id);
        $this->assertNull($masterData->parent_id);
        $this->assertEquals($masterData->id, $users->parent_id);
        $this->assertEquals($masterData->id, $roles->parent_id);

        // Verify children collection
        $this->assertCount(2, $masterData->children);
        $this->assertTrue($masterData->children->contains($users));
        $this->assertTrue($masterData->children->contains($roles));

        // Verify all IDs are UUIDs
        $this->assertTrue(Str::isUuid($dashboard->id));
        $this->assertTrue(Str::isUuid($masterData->id));
        $this->assertTrue(Str::isUuid($users->id));
        $this->assertTrue(Str::isUuid($roles->id));
    }

    public function test_menu_model_configuration()
    {
        $menu = new Menu();

        // Verify model configuration
        $this->assertEquals('id', $menu->getKeyName());
        $this->assertEquals('string', $menu->getKeyType());
        $this->assertFalse($menu->getIncrementing());

        // Verify traits are applied
        $this->assertContains('Illuminate\Database\Eloquent\Concerns\HasUuids', class_uses_recursive(Menu::class));
    }

    public function test_menu_get_by_location_with_uuid()
    {
        // Create test user with role
        $role = Role::create(['name' => 'test-role', 'guard_name' => 'web']);
        $this->user->assignRole($role);

        // Create menu with role
        $menu = Menu::create([
            'name' => 'test-menu',
            'label' => 'Test Menu',
            'route' => '/test',
            'location' => 'sidebar',
            'is_active' => true,
            'order_number' => 1
        ]);

        $menu->roles()->attach($role->id);

        // Test getMenuByLocation method
        $menus = Menu::getMenuByLocation('sidebar', $this->user);

        $this->assertCount(1, $menus);
        $this->assertEquals($menu->id, $menus->first()->id);
        $this->assertTrue(Str::isUuid($menus->first()->id));
    }

    public function test_menu_creation_with_user_tracking()
    {
        $menu = Menu::create([
            'name' => 'test-menu',
            'label' => 'Test Menu',
            'route' => '/test',
            'location' => 'sidebar'
        ]);

        // Verify user tracking fields (from BaseModel)
        $this->assertEquals($this->user->id, $menu->created_by);
        $this->assertEquals($this->user->id, $menu->updated_by);

        // Verify UUIDs for user tracking
        $this->assertTrue(Str::isUuid($menu->created_by));
        $this->assertTrue(Str::isUuid($menu->updated_by));
    }

    public function test_menu_update_preserves_uuid()
    {
        $menu = Menu::create([
            'name' => 'test-menu',
            'label' => 'Test Menu',
            'route' => '/test',
            'location' => 'sidebar'
        ]);

        $originalId = $menu->id;

        // Update menu
        $menu->update([
            'label' => 'Updated Test Menu',
            'route' => '/updated-test'
        ]);

        // Verify UUID is preserved
        $this->assertEquals($originalId, $menu->id);
        $this->assertTrue(Str::isUuid($menu->id));

        // Verify update worked
        $this->assertEquals('Updated Test Menu', $menu->label);
        $this->assertEquals('/updated-test', $menu->route);
    }

    public function test_menu_deletion_cascades_properly()
    {
        $parent = Menu::create([
            'name' => 'parent-menu',
            'label' => 'Parent Menu',
            'route' => '/parent',
            'location' => 'sidebar'
        ]);

        $child = Menu::create([
            'parent_id' => $parent->id,
            'name' => 'child-menu',
            'label' => 'Child Menu',
            'route' => '/parent/child',
            'location' => 'sidebar'
        ]);

        $parentId = $parent->id;
        $childId = $child->id;

        // Delete parent
        $parent->delete();

        // Verify cascade deletion
        $this->assertDatabaseMissing('menus', ['id' => $parentId]);
        $this->assertDatabaseMissing('menus', ['id' => $childId]);
    }
}
