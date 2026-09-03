<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogTest extends TestCase
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

    public function test_creating_a_user_writes_an_audit_log_entry(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/users', [
            'name' => 'Audited',
            'email' => 'audited@example.com',
            'password' => 'secret-password-1',
            'password_confirmation' => 'secret-password-1',
            'status' => 'active',
        ]);

        $user = User::where('email', 'audited@example.com')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.created',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_updating_a_user_never_logs_the_password(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();

        $this->actingAs($admin)->put("/users/{$target->id}", [
            'name' => 'Renamed',
            'email' => $target->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
            'status' => 'active',
        ]);

        $entry = AuditLog::where('action', 'user.updated')
            ->where('subject_id', $target->id)
            ->firstOrFail();

        $this->assertArrayNotHasKey('password', $entry->properties['changes'] ?? []);
        $this->assertTrue($entry->properties['password_changed']);
        $this->assertStringNotContainsString('brand-new-password', json_encode($entry->properties));
    }

    public function test_deactivating_a_user_writes_an_audit_log_entry(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();

        $this->actingAs($admin)->delete("/users/{$target->id}");

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.deactivated',
            'subject_type' => User::class,
            'subject_id' => $target->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_creating_a_role_writes_an_audit_log_entry(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/roles', [
            'name' => 'auditor',
            'permissions' => ['users.view'],
        ]);

        $role = Role::where('name', 'auditor')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'role.created',
            'subject_type' => Role::class,
            'subject_id' => $role->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_deleting_a_role_writes_an_audit_log_entry_before_the_row_is_gone(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'temp', 'guard_name' => 'web']);

        $this->actingAs($admin)->delete("/roles/{$role->id}");

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'role.deleted',
            'subject_type' => Role::class,
            'subject_id' => $role->id,
            'user_id' => $admin->id,
        ]);
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }
}
