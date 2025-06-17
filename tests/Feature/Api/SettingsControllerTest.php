<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Upload\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_get_settings_unauthenticated(): void
    {
        $response = $this->getJson('/api/settings');

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'upload_max_size' => Settings::getPostMaxSize(),
            ],
        ]);
    }

    public function test_get_settings_authenticated(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/settings');

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'upload_max_size' => Settings::getPostMaxSize(),
            ],
        ]);
    }
}
