<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
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
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/settings')->assertForbidden();
    }

    public function test_all_cards_are_enabled_by_default(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get('/settings');

        $response->assertOk();
        foreach (array_keys(config('dashboard.cards')) as $key) {
            $response->assertSee('name="cards[]" value="'.$key.'" checked', false);
        }
    }

    public function test_admin_can_disable_a_card_and_it_disappears_from_the_dashboard(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->put('/settings', [
            'cards' => ['recent_logins', 'recent_orders', 'order_status_counts'],
            'low_stock_threshold' => 2,
        ]);

        $response->assertRedirect(route('settings.edit'));

        $dashboard = $this->actingAs($admin)->get('/dashboard');
        $dashboard->assertOk();
        $dashboard->assertDontSee('Мало на складе');
        $dashboard->assertSee('Последние 5 заказов');
    }

    public function test_admin_can_change_the_low_stock_threshold(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->put('/settings', [
            'cards' => array_keys(config('dashboard.cards')),
            'low_stock_threshold' => 10,
        ]);

        $response->assertRedirect(route('settings.edit'));

        $edit = $this->actingAs($admin)->get('/settings');
        $edit->assertSee('value="10"', false);
    }

    public function test_low_stock_threshold_is_required(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->put('/settings', [
            'cards' => array_keys(config('dashboard.cards')),
        ]);

        $response->assertSessionHasErrors('low_stock_threshold');
    }
}
