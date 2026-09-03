<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AuthorizationLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_unauthenticated_access_is_logged(): void
    {
        Log::spy();

        $this->get('/roles');

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message) => $message === 'auth.unauthenticated_access');
    }

    public function test_forbidden_access_is_logged(): void
    {
        Log::spy();

        $user = User::factory()->create();

        $this->actingAs($user)->get('/roles');

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message) => $message === 'auth.access_denied');
    }
}
