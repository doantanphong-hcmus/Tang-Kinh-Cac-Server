<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

class ApiConventionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->prefix('api/v1/testing')->group(function (): void {
            Route::get('/success', fn (): JsonResource => JsonResource::make(['status' => 'ok']));

            Route::post('/validation', function (Request $request): JsonResponse {
                $validated = $request->validate([
                    'email' => ['required', 'email'],
                ]);

                return response()->json(['data' => $validated]);
            });

            Route::get('/authenticated', fn (): JsonResponse => response()->json([
                'data' => ['authenticated' => true],
            ]))->middleware('auth:sanctum');

            Route::get('/failure', function (): never {
                throw new RuntimeException('Internal details must not reach the client.');
            });
        });
    }

    public function test_api_success_uses_json_envelope_and_request_id_header(): void
    {
        $response = $this->withHeader('Accept', 'text/html')->get('/api/v1/testing/success');

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson([
                'data' => ['status' => 'ok'],
            ]);

        $this->assertMatchesRegularExpression(
            '/^[0-9A-HJKMNP-TV-Z]{26}$/',
            (string) $response->headers->get('X-Request-ID'),
        );
    }

    public function test_cors_is_disabled_for_api_responses(): void
    {
        $this->withHeader('Origin', 'https://untrusted.example')->get('/api/v1/health')
            ->assertOk()
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_returns_404_error_envelope_when_api_route_is_missing(): void
    {
        $response = $this->get('/api/v1/missing');

        $this->assertErrorResponse(
            response: $response,
            status: 404,
            code: 'NOT_FOUND',
            message: 'The requested resource was not found.',
        );
    }

    public function test_returns_422_error_envelope_when_validation_fails(): void
    {
        $response = $this->postJson('/api/v1/testing/validation');

        $this->assertErrorResponse(
            response: $response,
            status: 422,
            code: 'VALIDATION_ERROR',
            message: 'The request contains invalid fields.',
            fields: ['email' => ['The email field is required.']],
        );
    }

    public function test_returns_400_error_envelope_when_json_is_malformed(): void
    {
        $response = $this->call(
            method: 'POST',
            uri: '/api/v1/testing/validation',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"email":',
        );

        $this->assertErrorResponse(
            response: $response,
            status: 400,
            code: 'BAD_REQUEST',
            message: 'The request could not be understood.',
        );
    }

    public function test_returns_401_error_envelope_when_authentication_is_missing(): void
    {
        $response = $this->get('/api/v1/testing/authenticated');

        $this->assertErrorResponse(
            response: $response,
            status: 401,
            code: 'UNAUTHENTICATED',
            message: 'Authentication is required.',
        );
    }

    public function test_returns_safe_500_error_envelope_and_reports_unexpected_failure(): void
    {
        Exceptions::fake();

        $response = $this->get('/api/v1/testing/failure');

        $this->assertErrorResponse(
            response: $response,
            status: 500,
            code: 'INTERNAL_SERVER_ERROR',
            message: 'An unexpected server error occurred.',
        );
        $response->assertDontSee('Internal details');
        Exceptions::assertReported(RuntimeException::class);
    }

    /**
     * @param  array<string, array<int, string>>|null  $fields
     */
    private function assertErrorResponse(
        TestResponse $response,
        int $status,
        string $code,
        string $message,
        ?array $fields = null,
    ): void {
        $requestId = $response->json('error.request_id');
        $error = [
            'code' => $code,
            'message' => $message,
            'request_id' => $requestId,
        ];

        if ($fields !== null) {
            $error['fields'] = $fields;
        }

        $response
            ->assertStatus($status)
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('X-Request-ID', $requestId)
            ->assertExactJson(['error' => $error]);

        $this->assertIsString($requestId);
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $requestId);
    }
}
