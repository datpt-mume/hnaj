<?php

namespace Tests\Feature;

use App\Exceptions\ApiExceptionHandler;
use RuntimeException;
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

    public function test_api_validation_error_returns_error_envelope(): void
    {
        $response = $this->postJson('/api/auth/register');

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'The given data was invalid.',
                'code' => 'VALIDATION_ERROR',
            ])
            ->assertJsonStructure(['errors']);
    }

    public function test_api_internal_error_returns_generic_envelope(): void
    {
        $handler = app(ApiExceptionHandler::class);
        $response = $handler->render(new RuntimeException('boom'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'An unexpected error occurred.',
            'code' => 'INTERNAL_SERVER_ERROR',
        ], json_decode($response->getContent(), true));
    }
}
