<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_their_own_info(): void
    {
        $user = User::factory()->create(['name' => 'Dash User']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Dash User');
        $response->assertSee($user->email);
    }

    public function test_recent_logins_card_shows_only_the_five_most_recent_entries(): void
    {
        $users = User::factory()->count(6)->create([
            'password' => bcrypt('correct-password-1'),
        ]);

        foreach ($users as $user) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'correct-password-1',
            ]);
            $this->post('/logout');
        }

        $viewer = User::factory()->create();
        $response = $this->actingAs($viewer)->get('/dashboard');

        $response->assertOk();

        // Oldest of the 6 logins should have been pushed out of the top 5.
        $response->assertDontSee($users->first()->name);

        foreach ($users->slice(1) as $user) {
            $response->assertSee($user->name);
        }
    }
}
