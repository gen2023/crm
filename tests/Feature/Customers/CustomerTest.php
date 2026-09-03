<?php

namespace Tests\Feature\Customers;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
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
        $this->get('/customers')->assertRedirect('/login');
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/customers')->assertForbidden();
    }

    public function test_admin_can_list_customers(): void
    {
        $admin = $this->admin();
        $customer = Customer::factory()->create(['name' => 'Ivan Ivanov']);

        $response = $this->actingAs($admin)->get('/customers');

        $response->assertOk();
        $response->assertSee('Ivan Ivanov');
    }

    public function test_admin_can_create_a_customer(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post('/customers', [
            'name' => 'New Customer',
            'phone' => '+380501112233',
            'email' => 'new-customer@example.com',
        ]);

        $response->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'name' => 'New Customer',
            'phone' => '+380501112233',
            'orders_count' => 0,
        ]);
    }

    public function test_phone_must_be_unique_on_create(): void
    {
        $admin = $this->admin();
        $existing = Customer::factory()->create(['phone' => '+380501112233']);

        $response = $this->actingAs($admin)->post('/customers', [
            'name' => 'Dup',
            'phone' => $existing->phone,
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_order_aggregate_fields_cannot_be_set_through_the_create_form(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/customers', [
            'name' => 'Sneaky',
            'phone' => '+380509998877',
            'orders_count' => 999,
            'total_orders_amount' => 100000,
        ]);

        $this->assertDatabaseHas('customers', [
            'phone' => '+380509998877',
            'orders_count' => 0,
            'total_orders_amount' => 0,
        ]);
    }

    public function test_admin_can_edit_a_customer(): void
    {
        $admin = $this->admin();
        $customer = Customer::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($admin)->put("/customers/{$customer->id}", [
            'name' => 'New Name',
            'phone' => $customer->phone,
            'email' => $customer->email,
        ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertSame('New Name', $customer->fresh()->name);
    }

    public function test_editing_phone_still_enforces_uniqueness(): void
    {
        $admin = $this->admin();
        $customer = Customer::factory()->create();
        $other = Customer::factory()->create();

        $response = $this->actingAs($admin)->put("/customers/{$customer->id}", [
            'name' => $customer->name,
            'phone' => $other->phone,
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_reliability_is_computed_from_completed_and_cancelled_orders(): void
    {
        $noOrders = Customer::factory()->create();
        $this->assertNull($noOrders->reliability());

        $mixed = Customer::factory()->create([
            'completed_orders_count' => 3,
            'cancelled_orders_count' => 1,
        ]);
        $this->assertSame(75.0, $mixed->reliability());

        $allCancelled = Customer::factory()->create([
            'completed_orders_count' => 0,
            'cancelled_orders_count' => 2,
        ]);
        $this->assertSame(0.0, $allCancelled->reliability());
    }
}
