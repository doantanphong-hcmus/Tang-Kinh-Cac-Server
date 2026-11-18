<?php

declare(strict_types=1);

namespace Tests\Feature\Contract;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

final class OpenApiTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const array HTTP_METHODS = ['delete', 'get', 'patch', 'post', 'put'];

    public function test_documents_every_runtime_api_route_with_matching_method_and_security(): void
    {
        $documentedOperations = $this->documentedOperations();
        $runtimeRoutes = $this->runtimeRoutes();

        $this->assertSame(array_keys($runtimeRoutes), array_keys($documentedOperations));

        foreach ($runtimeRoutes as $operation => $route) {
            $security = $documentedOperations[$operation]['security'] ?? null;

            if (in_array('auth:sanctum', $route->middleware(), true)) {
                $this->assertSame([['bearerAuth' => []]], $security, "{$operation} must require bearerAuth.");
            } else {
                $this->assertContains($security, [null, []], "{$operation} must be publicly callable.");
            }
        }
    }

    public function test_operations_have_complete_parameters_responses_and_resolvable_references(): void
    {
        $document = $this->document();
        $operationIds = [];

        foreach ($this->documentedOperations() as $operation => $definition) {
            $operationId = $definition['operationId'] ?? null;
            $this->assertIsString($operationId, "{$operation} must define operationId.");
            $this->assertNotContains($operationId, $operationIds, "{$operationId} must be unique.");
            $operationIds[] = $operationId;

            [, $path] = explode(' ', $operation, 2);
            preg_match_all('/{([^}]+)}/', $path, $matches);
            $documentedPathParameters = [];

            foreach ($definition['parameters'] ?? [] as $parameter) {
                if (($parameter['in'] ?? null) !== 'path') {
                    continue;
                }

                $this->assertTrue($parameter['required'] ?? false, "{$operation} path parameters must be required.");
                $this->assertIsArray($parameter['schema'] ?? null, "{$operation} path parameters must define a schema.");
                $documentedPathParameters[] = $parameter['name'] ?? null;
            }

            $this->assertSame($matches[1], $documentedPathParameters, "{$operation} path parameters do not match its URI.");
            $this->assertNotEmpty($definition['responses'] ?? [], "{$operation} must define responses.");

            foreach ($definition['responses'] as $status => $response) {
                $context = "{$operation} {$status}";
                $this->assertIsString($response['description'] ?? null, "{$context} must have a description.");
                $this->assertArrayHasKey('X-Request-ID', $response['headers'] ?? [], "{$context} must document X-Request-ID.");

                if ((string) $status !== '204') {
                    $this->assertNotEmpty($response['content'] ?? [], "{$context} must define response content.");

                    foreach ($response['content'] as $mediaType) {
                        $this->assertIsArray($mediaType['schema'] ?? null, "{$context} content must define a schema.");
                    }
                }

                if (str_starts_with((string) $status, '4')) {
                    $this->assertErrorResponseExample($response, $context);
                }
            }
        }

        foreach ($this->references($document) as $reference) {
            $this->assertReferenceExists($document, $reference);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function document(): array
    {
        $document = Yaml::parseFile(base_path('openapi/openapi.yaml'));
        $this->assertIsArray($document);

        return $document;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function documentedOperations(): array
    {
        $operations = [];

        foreach ($this->document()['paths'] as $path => $pathItem) {
            foreach (self::HTTP_METHODS as $method) {
                if (isset($pathItem[$method])) {
                    $operations[strtoupper($method).' '.$path] = $pathItem[$method];
                }
            }
        }

        ksort($operations);

        return $operations;
    }

    /**
     * @return array<string, Route>
     */
    private function runtimeRoutes(): array
    {
        $routes = [];

        foreach (RouteFacade::getRoutes()->getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }

            $path = '/'.substr($route->uri(), strlen('api/v1/'));

            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $routes[$method.' '.$path] = $route;
            }
        }

        ksort($routes);

        return $routes;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function assertErrorResponseExample(array $response, string $context): void
    {
        $json = $response['content']['application/json'] ?? null;
        $this->assertIsArray($json, "{$context} must use the JSON error envelope.");
        $this->assertSame('#/components/schemas/ErrorResponse', $json['schema']['$ref'] ?? null);
        $examples = isset($json['example'])
            ? [$json['example']]
            : array_column($json['examples'] ?? [], 'value');
        $this->assertNotEmpty($examples, "{$context} must include a stable error example.");

        foreach ($examples as $example) {
            $this->assertIsString($example['error']['code'] ?? null, "{$context} example must include an error code.");
            $this->assertIsString($example['error']['message'] ?? null, "{$context} example must include an error message.");
            $this->assertIsString($example['error']['request_id'] ?? null, "{$context} example must include a request ID.");
        }
    }

    /**
     * @param  array<string, mixed>  $value
     * @return list<string>
     */
    private function references(array $value): array
    {
        $references = [];

        foreach ($value as $key => $item) {
            if ($key === '$ref' && is_string($item)) {
                $references[] = $item;
            } elseif (is_array($item)) {
                $references = [...$references, ...$this->references($item)];
            }
        }

        return array_values(array_unique($references));
    }

    /**
     * @param  array<string, mixed>  $document
     */
    private function assertReferenceExists(array $document, string $reference): void
    {
        $this->assertStringStartsWith('#/', $reference);
        $target = $document;

        foreach (explode('/', substr($reference, 2)) as $segment) {
            $this->assertArrayHasKey($segment, $target, "Missing OpenAPI reference {$reference}.");
            $target = $target[$segment];
            $this->assertIsArray($target, "OpenAPI reference {$reference} does not resolve to an object.");
        }
    }
}
