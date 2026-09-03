<?php

namespace Tests\Feature\Roles;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/roles')->assertRedirect('/login');
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/roles')->assertForbidden();
    }

    public function test_admin_can_view_roles_list(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/roles');

        $response->assertOk();
        $response->assertSee('admin');
    }

    public function test_admin_can_create_a_role_with_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/roles', [
            'name' => 'editor',
            'description' => 'Can edit content',
            'permissions' => ['users.view', 'users.edit'],
        ]);

        $response->assertRedirect(route('roles.index'));

        $role = Role::where('name', 'editor')->firstOrFail();
        $this->assertSame('Can edit content', $role->description);
        $this->assertTrue($role->hasPermissionTo('users.view'));
        $this->assertTrue($role->hasPermissionTo('users.edit'));
        $this->assertFalse($role->hasPermissionTo('users.delete'));
    }

    public function test_role_creation_requires_unique_name(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/roles', [
            'name' => 'admin',
            'permissions' => [],
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_a_roles_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $role->syncPermissions(['users.view']);

        $response = $this->actingAs($admin)->put("/roles/{$role->id}", [
            'name' => 'editor',
            'description' => 'Updated',
            'permissions' => ['users.view', 'users.edit'],
        ]);

        $response->assertRedirect(route('roles.index'));

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('users.edit'));
    }

    public function test_admin_can_delete_a_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $role = Role::create(['name' => 'temporary', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->delete("/roles/{$role->id}");

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseMissing('roles', ['name' => 'temporary']);
    }

    public function test_user_with_only_roles_view_permission_cannot_create(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::where('name', 'roles.view')->firstOrFail());

        $this->actingAs($user)->get('/roles')->assertOk();
        $this->actingAs($user)->get('/roles/create')->assertForbidden();
        $this->actingAs($user)->post('/roles', ['name' => 'x'])->assertForbidden();
    }
}
