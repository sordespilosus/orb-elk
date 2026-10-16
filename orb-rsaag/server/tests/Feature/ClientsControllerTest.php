<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class ClientsControllerTest extends TestCase
{
    use WithoutMiddleware;

    public function test_get_client_by_id()
    {
        $response = $this->getJson('/api/v2/client/1');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'client',
                'scope',
                'esiaScopes',
            ],
        ]);
    }

    public function test_get_non_existent_client_by_id()
    {
        $response = $this->getJson('/api/v2/client/9999');
        $response->assertStatus(500);
    }

    public function test_get_client_with_invalid_id()
    {
        $response = $this->getJson("/api/v2/client/abc");
        $response->assertStatus(500);
    }


}
