<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Media;

use App\Actions\Media\OpenBookFileStream;
use App\Models\BookFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

final class OpenBookFileStreamTest extends TestCase
{
    public function test_opens_stream_from_server_owned_file_metadata(): void
    {
        Storage::fake('media');
        Storage::disk('media')->put('books/sample.epub', 'epub-bytes');
        $file = BookFile::factory()->make([
            'storage_disk' => 'media',
            'storage_path' => 'books/sample.epub',
        ]);

        $stream = app(OpenBookFileStream::class)->handle($file);
        $contents = stream_get_contents($stream);
        fclose($stream);

        Storage::disk('media')->assertExists('books/sample.epub');
        $this->assertSame('epub-bytes', $contents);
    }

    public function test_missing_file_fails_without_exposing_private_path(): void
    {
        Storage::fake('media');
        $file = BookFile::factory()->make([
            'storage_disk' => 'media',
            'storage_path' => 'private/secret.epub',
        ]);

        try {
            app(OpenBookFileStream::class)->handle($file);
            $this->fail('Expected the missing media file to fail.');
        } catch (RuntimeException $exception) {
            Storage::disk('media')->assertMissing('private/secret.epub');
            $this->assertSame('The media file could not be opened.', $exception->getMessage());
            $this->assertStringNotContainsString('private/secret.epub', $exception->getMessage());
        }
    }
}
