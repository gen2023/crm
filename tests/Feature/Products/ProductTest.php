<?php

namespace Tests\Feature\Products;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
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
        $this->get('/products')->assertRedirect('/login');
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/products')->assertForbidden();
    }

    public function test_admin_can_list_products(): void
    {
        $admin = $this->admin();
        Product::factory()->create(['name' => 'Widget']);

        $response = $this->actingAs($admin)->get('/products');

        $response->assertOk();
        $response->assertSee('Widget');
    }

    public function test_admin_can_create_a_product(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post('/products', [
            'name' => 'New Product',
            'sku' => 'SKU-001',
            'price' => 199.99,
            'stock' => 10,
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['sku' => 'SKU-001', 'name' => 'New Product']);
    }

    public function test_sku_must_be_unique_on_create(): void
    {
        $admin = $this->admin();
        $existing = Product::factory()->create(['sku' => 'DUPLICATE']);

        $response = $this->actingAs($admin)->post('/products', [
            'name' => 'Another',
            'sku' => $existing->sku,
            'price' => 10,
            'stock' => 1,
        ]);

        $response->assertSessionHasErrors('sku');
    }

    public function test_admin_can_edit_a_product(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($admin)->put("/products/{$product->id}", [
            'name' => 'New Name',
            'sku' => $product->sku,
            'price' => $product->price,
            'stock' => $product->stock,
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertSame('New Name', $product->fresh()->name);
    }

    public function test_admin_can_delete_a_product_with_no_orders(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create();

        $response = $this->actingAs($admin)->delete("/products/{$product->id}");

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_product_used_in_an_order_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        OrderItem::factory()->for($order)->for($product)->create();

        $response = $this->actingAs($admin)->delete("/products/{$product->id}");

        $response->assertSessionHasErrors('product');
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
