<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_their_own_name_in_the_topbar(): void
    {
        $user = User::factory()->create(['name' => 'Dash User']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Dash User');
        $response->assertSee('История заходов');
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

    public function test_user_without_orders_or_products_permission_does_not_see_those_cards(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('Последние 5 заказов');
        $response->assertDontSee('Заказы по статусам');
        $response->assertDontSee('Мало на складе');
    }

    public function test_admin_sees_recent_orders_and_status_counts(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = Customer::factory()->create(['name' => 'Dash Customer']);
        Order::factory()->for($customer)->create(['status' => 'new']);
        Order::factory()->for($customer)->create(['status' => 'completed']);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Dash Customer');
        $response->assertSee('Последние 5 заказов');
        $response->assertSee('Заказы по статусам');
    }

    public function test_admin_sees_products_below_the_configured_stock_threshold(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        config(['dashboard.low_stock_threshold' => 2]);

        $low = Product::factory()->create(['name' => 'Low Stock Item', 'stock' => 1]);
        $plenty = Product::factory()->create(['name' => 'Plenty Item', 'stock' => 50]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Low Stock Item');
        $response->assertDontSee('Plenty Item');
    }
}
