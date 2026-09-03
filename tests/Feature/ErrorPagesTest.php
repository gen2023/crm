<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array<int, int>>
     */
    public static function errorCodes(): array
    {
        return [[400], [401], [403], [404], [419], [422], [500]];
    }

    #[DataProvider('errorCodes')]
    public function test_custom_error_view_renders_for_status(int $code): void
    {
        $html = view("errors.$code")->render();

        $this->assertStringContainsString((string) $code, $html);
    }

    public function test_unknown_route_renders_the_custom_404_page_without_debug_info(): void
    {
        config(['app.debug' => false]);

        $response = $this->get('/this-route-does-not-exist');

        $response->assertStatus(404);
        $response->assertSee('Страница не найдена');
    }

    public function test_forbidden_request_renders_the_custom_403_page_without_debug_info(): void
    {
        config(['app.debug' => false]);
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/roles');

        $response->assertStatus(403);
        $response->assertSee('нет прав');
    }
}
