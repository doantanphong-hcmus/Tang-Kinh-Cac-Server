<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Models\Book;
use App\Models\BookFile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class EpubSeedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sample_fixture_has_required_epub_container_header(): void
    {
        $sample = File::get(database_path('seeders/media/tang-kinh-cac-sample.epub'));
        $compressionMethod = ord($sample[8]) | (ord($sample[9]) << 8);
        $filenameLength = ord($sample[26]) | (ord($sample[27]) << 8);
        $extraLength = ord($sample[28]) | (ord($sample[29]) << 8);
        $payloadOffset = 30 + $filenameLength + $extraLength;

        $this->assertSame("PK\x03\x04", substr($sample, 0, 4));
        $this->assertSame(0, $compressionMethod);
        $this->assertSame('mimetype', substr($sample, 30, $filenameLength));
        $this->assertSame('application/epub+zip', substr($sample, $payloadOffset, 20));
    }

    public function test_seeded_sample_epub_downloads_end_to_end_without_exposing_storage_metadata(): void
    {
        Storage::fake('media');
        $this->seed();
        $book = Book::query()->where('slug', 'truyen-kieu')->sole();
        $file = BookFile::query()->whereBelongsTo($book)->where('type', 'epub')->sole();
        $sample = File::get(database_path('seeders/media/tang-kinh-cac-sample.epub'));
        Sanctum::actingAs(User::query()->where('email', 'reader@example.test')->sole());

        $descriptor = $this->getJson("/api/v1/books/{$book->id}/epub")->assertOk();
        $download = $this->get($descriptor->json('data.download_url'));

        $descriptor
            ->assertJsonPath('data.size', strlen($sample))
            ->assertJsonPath('data.checksum_sha256', hash('sha256', $sample))
            ->assertJsonMissingPath('data.storage_disk')
            ->assertJsonMissingPath('data.storage_path');
        $download
            ->assertOk()
            ->assertStreamed()
            ->assertHeader('Content-Length', (string) strlen($sample))
            ->assertHeader('ETag', '"'.hash('sha256', $sample).'"')
            ->assertStreamedContent($sample);
        Storage::disk('media')->assertExists($file->storage_path);
    }
}
