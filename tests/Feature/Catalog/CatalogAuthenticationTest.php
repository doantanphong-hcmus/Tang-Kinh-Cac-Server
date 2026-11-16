<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class CatalogAuthenticationTest extends TestCase
{
    #[DataProvider('catalogEndpoints')]
    public function test_returns_401_for_invalid_access_token(string $endpoint): void
    {
        $response = $this->withToken('invalid-token')->getJson($endpoint);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function catalogEndpoints(): array
    {
        return [
            'audiobook descriptor' => ['/api/v1/books/missing-book/audiobook'],
            'audio track list' => ['/api/v1/books/missing-book/audio-tracks'],
            'book list' => ['/api/v1/books'],
            'book detail' => ['/api/v1/books/missing-book'],
            'genre list' => ['/api/v1/genres'],
            'genre books' => ['/api/v1/genres/missing-genre/books'],
        ];
    }
}
