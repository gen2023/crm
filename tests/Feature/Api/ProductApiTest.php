<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
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

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
    }

    public function test_authenticated_user_without_permission_gets_403(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/products')->assertForbidden();
    }

    public function test_can_list_products(): void
    {
        Sanctum::actingAs($this->admin());
        Product::factory()->create(['name' => 'API Widget']);

        $response = $this->getJson('/api/products');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'API Widget']);
    }

    public function test_can_get_a_single_product(): void
    {
        Sanctum::actingAs($this->admin());
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertOk();
        $response->assertJsonPath('data.sku', $product->sku);
    }

    public function test_can_create_a_product(): void
    {
        Sanctum::actingAs($this->admin());

        $response = $this->postJson('/api/products', [
            'name' => 'Created via API',
            'sku' => 'API-SKU-1',
            'price' => 42.5,
            'stock' => 7,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.sku', 'API-SKU-1');
        $this->assertDatabaseHas('products', ['sku' => 'API-SKU-1']);
    }

    public function test_create_validates_input(): void
    {
        Sanctum::actingAs($this->admin());

        $response = $this->postJson('/api/products', ['name' => 'Missing fields']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sku', 'price', 'stock']);
    }

    public function test_can_update_a_product(): void
    {
        Sanctum::actingAs($this->admin());
        $product = Product::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/products/{$product->id}", [
            'name' => 'Updated Name',
            'sku' => $product->sku,
            'price' => $product->price,
            'stock' => $product->stock,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Updated Name');
        $this->assertSame('Updated Name', $product->fresh()->name);
    }
}
