<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_login_is_logged_without_the_password(): void
    {
        Log::spy();

        $user = User::factory()->create([
            'password' => bcrypt('correct-password'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($user) {
                return $message === 'auth.login_failed'
                    && $context['email'] === $user->email
                    && ! isset($context['password']);
            });
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'whatever-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_error_message_does_not_reveal_which_field_was_wrong(): void
    {
        $existingUser = User::factory()->create([
            'password' => bcrypt('correct-password'),
        ]);

        $message = __('Неверный email или пароль.');

        $this->post('/login', [
            'email' => $existingUser->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors(['email' => $message]);

        $this->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors(['email' => $message]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('correct-password'),
            'status' => 'inactive',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_protected_route_redirects_guests_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }
}
