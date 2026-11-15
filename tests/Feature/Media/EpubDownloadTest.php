<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\BookStatus;
use App\Models\Book;
use App\Models\BookFile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class EpubDownloadTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_signed_url_streams_exact_epub_bytes_and_download_headers(): void
    {
        Storage::fake('media');
        $bytes = "PK\x03\x04sample-epub-bytes";
        $book = Book::factory()->published()->create();
        $file = BookFile::factory()->for($book)->create([
            'storage_path' => 'books/sample.epub',
            'original_filename' => 'unsafe "title".epub',
            'size' => strlen($bytes),
            'checksum_sha256' => hash('sha256', $bytes),
            'version' => 2,
        ]);
        Storage::disk('media')->put($file->storage_path, $bytes);
        Sanctum::actingAs(User::factory()->create());
        $descriptor = $this->getJson("/api/v1/books/{$book->id}/epub")->assertOk();

        $response = $this->get($descriptor->json('data.download_url'));

        $response
            ->assertOk()
            ->assertStreamed()
            ->assertHeader('Content-Type', 'application/epub+zip')
            ->assertHeader('Content-Length', (string) strlen($bytes))
            ->assertHeader('Content-Disposition', "attachment; filename=book-{$book->id}-v2.epub")
            ->assertHeader('ETag', '"'.hash('sha256', $bytes).'"')
            ->assertStreamedContent($bytes);
        Storage::disk('media')->assertExists('books/sample.epub');
    }

    public function test_returns_403_when_signature_is_missing(): void
    {
        $file = BookFile::factory()->create();

        $this->getJson("/api/v1/media/{$file->id}/download")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'INVALID_MEDIA_SIGNATURE');
    }

    public function test_returns_403_when_file_identifier_is_modified(): void
    {
        $file = BookFile::factory()->create();
        $url = URL::temporarySignedRoute('media.download', now()->addMinutes(5), ['file' => $file]);
        $modifiedUrl = str_replace("/{$file->id}/download", '/999999/download', $url);

        $this->getJson($modifiedUrl)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'INVALID_MEDIA_SIGNATURE');
    }

    public function test_returns_403_when_signature_has_expired(): void
    {
        $this->travelTo('2026-11-15 12:00:00');
        $file = BookFile::factory()->create();
        $url = URL::temporarySignedRoute('media.download', now()->addMinutes(5), ['file' => $file]);
        $this->travel(6)->minutes();

        $this->getJson($url)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'INVALID_MEDIA_SIGNATURE');
    }

    public function test_returns_404_when_signed_epub_is_no_longer_active(): void
    {
        $book = Book::factory()->published()->create();
        $file = BookFile::factory()->for($book)->create();
        $url = URL::temporarySignedRoute('media.download', now()->addMinutes(5), ['file' => $file]);
        $file->update(['is_active' => false]);

        $this->getJson($url)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'EPUB_NOT_FOUND');
    }

    public function test_returns_404_when_signed_identifier_belongs_to_audio(): void
    {
        $book = Book::factory()->published()->create();
        $file = BookFile::factory()->audio()->for($book)->create();
        $url = URL::temporarySignedRoute('media.download', now()->addMinutes(5), ['file' => $file]);

        $this->getJson($url)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'EPUB_NOT_FOUND');
    }

    public function test_returns_404_when_book_is_no_longer_published(): void
    {
        $book = Book::factory()->published()->create();
        $file = BookFile::factory()->for($book)->create();
        $url = URL::temporarySignedRoute('media.download', now()->addMinutes(5), ['file' => $file]);
        $book->update(['status' => BookStatus::Archived]);

        $this->getJson($url)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'EPUB_NOT_FOUND');
    }
}
