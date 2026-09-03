<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_admin_user_from_env_when_running_locally(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->app->detectEnvironment(fn () => 'local');

        $overrides = [
            'ADMIN_EMAIL' => 'seeded-admin@example.com',
            'ADMIN_PASSWORD' => 'seeded-password-1',
            'ADMIN_NAME' => 'Seeded Admin',
        ];

        foreach ($overrides as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        try {
            $this->seed(AdminUserSeeder::class);
        } finally {
            foreach (array_keys($overrides) as $key) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            }
        }

        $admin = User::where('email', 'seeded-admin@example.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame('Seeded Admin', $admin->name);
        $this->assertSame('active', $admin->status);
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_it_is_skipped_outside_the_local_environment(): void
    {
        $this->seed(RolePermissionSeeder::class);

        // The base testing environment is "testing", not "local".
        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }
}
