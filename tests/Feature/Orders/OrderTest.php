<?php

namespace Tests\Feature\Orders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
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
        $this->get('/orders')->assertRedirect('/login');
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/orders')->assertForbidden();
    }

    public function test_admin_can_create_an_order_with_items_and_totals_are_computed(): void
    {
        $admin = $this->admin();
        $customer = Customer::factory()->create();
        $productA = Product::factory()->create(['price' => 100]);
        $productB = Product::factory()->create(['price' => 50]);

        $response = $this->actingAs($admin)->post('/orders', [
            'customer_id' => $customer->id,
            'status' => 'new',
            'items' => [
                ['product_id' => $productA->id, 'quantity' => 2],
                ['product_id' => $productB->id, 'quantity' => 3],
            ],
        ]);

        $response->assertRedirect(route('orders.index'));

        $order = $customer->orders()->first();
        $this->assertNotNull($order);
        $this->assertSame('350.00', (string) $order->total_amount);
        $this->assertCount(2, $order->items);
    }

    public function test_order_requires_at_least_one_item(): void
    {
        $admin = $this->admin();
        $customer = Customer::factory()->create();

        $response = $this->actingAs($admin)->post('/orders', [
            'customer_id' => $customer->id,
            'status' => 'new',
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
    }

    public function test_creating_an_order_updates_customer_aggregates(): void
    {
        $admin = $this->admin();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100]);

        $this->actingAs($admin)->post('/orders', [
            'customer_id' => $customer->id,
            'status' => 'new',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $customer->refresh();
        $this->assertSame(1, $customer->orders_count);
        $this->assertSame('100.00', (string) $customer->total_orders_amount);
        $this->assertNotNull($customer->last_order_at);
    }

    public function test_marking_an_order_completed_updates_reliability(): void
    {
        $admin = $this->admin();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 10]);

        $this->actingAs($admin)->post('/orders', [
            'customer_id' => $customer->id,
            'status' => 'new',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $order = $customer->orders()->first();

        $this->assertNull($customer->fresh()->reliability());

        $this->actingAs($admin)->put("/orders/{$order->id}", [
            'customer_id' => $customer->id,
            'status' => 'completed',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $customer->refresh();
        $this->assertSame(1, $customer->completed_orders_count);
        $this->assertSame(0, $customer->cancelled_orders_count);
        $this->assertSame(100.0, $customer->reliability());
    }

    public function test_cancelling_an_order_lowers_reliability(): void
    {
        $admin = $this->admin();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 10]);

        $this->actingAs($admin)->post('/orders', [
            'customer_id' => $customer->id,
            'status' => 'completed',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);
        $completedOrder = $customer->orders()->first();

        $this->actingAs($admin)->post('/orders', [
            'customer_id' => $customer->id,
            'status' => 'cancelled',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $customer->refresh();
        $this->assertSame(2, $customer->orders_count);
        $this->assertSame(1, $customer->completed_orders_count);
        $this->assertSame(1, $customer->cancelled_orders_count);
        $this->assertSame(50.0, $customer->reliability());

        $this->assertNotNull($completedOrder);
    }

    public function test_reassigning_an_order_to_another_customer_updates_both_customers_aggregates(): void
    {
        $admin = $this->admin();
        $customerA = Customer::factory()->create();
        $customerB = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 20]);

        $this->actingAs($admin)->post('/orders', [
            'customer_id' => $customerA->id,
            'status' => 'new',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);
        $order = $customerA->orders()->first();

        $this->actingAs($admin)->put("/orders/{$order->id}", [
            'customer_id' => $customerB->id,
            'status' => 'new',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $this->assertSame(0, $customerA->fresh()->orders_count);
        $this->assertSame(1, $customerB->fresh()->orders_count);
    }
}
