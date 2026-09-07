<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiHealthTest extends TestCase
{
    /**
     * Testa se a rota /api/ping responde com sucesso e estrutura esperada.
     */
    public function test_api_ping_returns_ok(): void
    {
        $response = $this->getJson('/api/ping');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'message' => 'Adota+ API online',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'timestamp',
            ]);
    }
}
