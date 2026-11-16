<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Models\Book;
use App\Models\BookFile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class EpubDescriptorTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_requires_authentication(): void
    {
        $book = Book::factory()->published()->create();

        $this->getJson("/api/v1/books/{$book->id}/epub")
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_returns_latest_active_epub_descriptor_with_five_minute_signed_url(): void
    {
        $this->travelTo('2026-11-15 12:00:00');
        $book = Book::factory()->published()->create();
        BookFile::factory()->for($book)->create(['version' => 1]);
        $file = BookFile::factory()->for($book)->create([
            'version' => 2,
            'size' => 2_457_600,
            'checksum_sha256' => str_repeat('a', 64),
        ]);
        BookFile::factory()->inactive()->for($book)->create(['version' => 3]);
        $this->authenticate();

        $response = $this->getJson("/api/v1/books/{$book->id}/epub");

        $response
            ->assertOk()
            ->assertJsonPath('data.file_id', $file->id)
            ->assertJsonPath('data.book_id', $book->id)
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.mime_type', 'application/epub+zip')
            ->assertJsonPath('data.size', 2_457_600)
            ->assertJsonPath('data.checksum_sha256', str_repeat('a', 64))
            ->assertJsonPath('data.expires_at', '2026-11-15T12:05:00Z');

        $downloadUrl = $response->json('data.download_url');
        $this->assertIsString($downloadUrl);
        parse_str((string) parse_url($downloadUrl, PHP_URL_QUERY), $downloadQuery);
        $this->assertSame('1794744300', $downloadQuery['expires'] ?? null);
        $this->assertTrue(URL::hasValidSignature(Request::create($downloadUrl)));
        $this->assertStringNotContainsString($file->storage_disk, parse_url($downloadUrl, PHP_URL_QUERY) ?: '');
        $this->assertStringNotContainsString($file->storage_path, $response->getContent());
    }

    public function test_returns_403_for_vip_epub(): void
    {
        $book = Book::factory()->published()->vip()->create();
        BookFile::factory()->for($book)->create();
        $this->authenticate();

        $this->getJson("/api/v1/books/{$book->id}/epub")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'CONTENT_ACCESS_DENIED')
            ->assertJsonPath('error.message', 'Your account cannot access this content.');
    }

    public function test_returns_404_when_no_active_epub_exists(): void
    {
        $book = Book::factory()->published()->create();
        BookFile::factory()->inactive()->for($book)->create();
        $this->authenticate();

        $this->getJson("/api/v1/books/{$book->id}/epub")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'EPUB_NOT_FOUND')
            ->assertJsonPath('error.message', 'No EPUB is available for this book.');
    }

    public function test_returns_404_for_unpublished_book(): void
    {
        $book = Book::factory()->create();
        BookFile::factory()->for($book)->create();
        $this->authenticate();

        $this->getJson("/api/v1/books/{$book->id}/epub")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'BOOK_NOT_FOUND');
    }

    private function authenticate(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }
}
