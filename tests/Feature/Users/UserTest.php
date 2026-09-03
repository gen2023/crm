<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/users')->assertRedirect('/login');
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/users')->assertForbidden();
    }

    public function test_admin_can_list_users(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get('/users');

        $response->assertOk();
        $response->assertSee($admin->email);
    }

    public function test_admin_can_create_a_user_with_a_role_and_that_user_can_immediately_login(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'New Manager',
            'email' => 'new-manager@example.com',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
            'status' => 'active',
            'roles' => ['manager'],
        ]);

        $response->assertRedirect(route('users.index'));

        $created = User::where('email', 'new-manager@example.com')->firstOrFail();
        $this->assertTrue($created->hasRole('manager'));
        $this->assertTrue($created->isActive());

        $this->post('/logout');

        $loginResponse = $this->post('/login', [
            'email' => 'new-manager@example.com',
            'password' => 'new-password-1',
        ]);
        $loginResponse->assertRedirect(route('dashboard'));
    }

    public function test_user_creation_requires_unique_email(): void
    {
        $admin = $this->admin();
        $existing = User::factory()->create();

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Dup',
            'email' => $existing->email,
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_edit_a_user(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($admin)->put("/users/{$target->id}", [
            'name' => 'New Name',
            'email' => $target->email,
            'status' => 'active',
            'roles' => ['user'],
        ]);

        $response->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertSame('New Name', $target->name);
        $this->assertTrue($target->hasRole('user'));
    }

    public function test_editing_email_still_enforces_uniqueness(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->actingAs($admin)->put("/users/{$target->id}", [
            'name' => $target->name,
            'email' => $other->email,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_deactivate_another_user(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();

        $response = $this->actingAs($admin)->delete("/users/{$target->id}");

        $response->assertRedirect(route('users.index'));
        $this->assertSame('inactive', $target->fresh()->status);
    }

    public function test_admin_can_reactivate_a_user(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($admin)->post("/users/{$target->id}/activate");

        $response->assertRedirect(route('users.index'));
        $this->assertSame('active', $target->fresh()->status);
    }

    public function test_user_cannot_deactivate_own_account(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->delete("/users/{$admin->id}");

        $response->assertForbidden();
        $this->assertSame('active', $admin->fresh()->status);
    }

    public function test_last_active_admin_cannot_be_deactivated(): void
    {
        $lastAdmin = $this->admin();
        $actor = User::factory()->create();
        $actor->givePermissionTo('users.delete');

        $response = $this->actingAs($actor)->delete("/users/{$lastAdmin->id}");

        $response->assertForbidden();
        $this->assertSame('active', $lastAdmin->fresh()->status);
    }

    public function test_admin_can_be_deactivated_when_another_active_admin_remains(): void
    {
        $admin1 = $this->admin();
        $admin2 = $this->admin();

        $response = $this->actingAs($admin1)->delete("/users/{$admin2->id}");

        $response->assertRedirect(route('users.index'));
        $this->assertSame('inactive', $admin2->fresh()->status);
    }

    public function test_admin_role_cannot_be_removed_from_the_last_active_admin(): void
    {
        $lastAdmin = $this->admin();
        $actor = User::factory()->create();
        $actor->givePermissionTo('users.edit');

        $response = $this->actingAs($actor)->put("/users/{$lastAdmin->id}", [
            'name' => $lastAdmin->name,
            'email' => $lastAdmin->email,
            'status' => 'active',
            'roles' => [],
        ]);

        $response->assertForbidden();
        $this->assertTrue($lastAdmin->fresh()->hasRole('admin'));
    }

    public function test_admin_role_can_be_removed_when_another_active_admin_remains(): void
    {
        $admin1 = $this->admin();
        $admin2 = $this->admin();

        $response = $this->actingAs($admin1)->put("/users/{$admin2->id}", [
            'name' => $admin2->name,
            'email' => $admin2->email,
            'status' => 'active',
            'roles' => [],
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertFalse($admin2->fresh()->hasRole('admin'));
    }
}
