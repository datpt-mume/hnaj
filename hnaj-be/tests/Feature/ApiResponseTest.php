<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    public function test_api_test_endpoint_returns_the_success_envelope(): void
    {
        $response = $this->getJson('/api/test');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['service', 'status'],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'service' => 'hnaj-be',
                    'status' => 'ok',
                ],
            ]);
    }

    public function test_missing_api_route_returns_the_error_envelope(): void
    {
        $response = $this->getJson('/api/does-not-exist');

        $response
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'The requested resource was not found.',
                'code' => 'NOT_FOUND',
            ]);
    }
}
