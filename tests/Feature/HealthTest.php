<?php
namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_responde_ok(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }
}
