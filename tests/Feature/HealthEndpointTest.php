<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_health_endpoint_returns_public_status_without_infrastructure_details(): void
    {
        $response = $this->get('/api/v1/health');

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson([
                'data' => [
                    'status' => 'ok',
                ],
            ]);

        $this->assertMatchesRegularExpression(
            '/^[0-9A-HJKMNP-TV-Z]{26}$/',
            (string) $response->headers->get('X-Request-ID'),
        );
    }
}
