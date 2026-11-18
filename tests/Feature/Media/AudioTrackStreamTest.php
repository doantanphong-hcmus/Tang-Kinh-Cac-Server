<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Actions\Media\OpenBookFileStream;
use App\Actions\Media\ResolveByteRange;
use App\Http\Controllers\Api\V1\AudioTrackStreamController;
use App\Models\Book;
use App\Models\BookFile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class AudioTrackStreamTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_signed_url_streams_complete_audio_with_seek_headers(): void
    {
        [$track, $bytes, $url] = $this->createTrack();

        $response = $this->get($url);

        $response
            ->assertOk()
            ->assertStreamed()
            ->assertHeader('Accept-Ranges', 'bytes')
            ->assertHeader('Content-Length', '10')
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertHeader('ETag', '"'.hash('sha256', $bytes).'"')
            ->assertHeaderMissing('Content-Range')
            ->assertStreamedContent($bytes);
        Storage::disk('media')->assertExists($track->storage_path);
    }

    public function test_valid_range_streams_exact_partial_bytes_and_returns_206(): void
    {
        [, , $url] = $this->createTrack();

        $response = $this->withHeader('Range', 'bytes=2-5')->get($url);

        $response
            ->assertStatus(206)
            ->assertStreamed()
            ->assertHeader('Accept-Ranges', 'bytes')
            ->assertHeader('Content-Length', '4')
            ->assertHeader('Content-Range', 'bytes 2-5/10')
            ->assertStreamedContent('2345');
    }

    #[DataProvider('validRangeProvider')]
    public function test_supports_seek_range_variants(string $range, string $contentRange, string $expected): void
    {
        [, , $url] = $this->createTrack();

        $this->withHeader('Range', $range)->get($url)
            ->assertStatus(206)
            ->assertHeader('Content-Length', (string) strlen($expected))
            ->assertHeader('Content-Range', $contentRange)
            ->assertStreamedContent($expected);
    }

    public function test_unsatisfiable_range_returns_416_with_file_boundary(): void
    {
        [, , $url] = $this->createTrack();

        $this->withHeader('Range', 'bytes=10-')->getJson($url)
            ->assertStatus(416)
            ->assertHeader('Content-Range', 'bytes */10')
            ->assertJsonPath('error.code', 'RANGE_NOT_SATISFIABLE');
    }

    #[DataProvider('invalidRangeProvider')]
    public function test_rejects_invalid_range_variants(string $range): void
    {
        [, , $url] = $this->createTrack();

        $this->withHeader('Range', $range)->getJson($url)
            ->assertStatus(416)
            ->assertHeader('Content-Range', 'bytes */10')
            ->assertJsonPath('error.code', 'RANGE_NOT_SATISFIABLE');
    }

    public function test_returns_403_when_signature_is_missing(): void
    {
        [$track] = $this->createTrack();

        $this->getJson("/api/v1/audio-tracks/{$track->id}/stream")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'INVALID_MEDIA_SIGNATURE');
    }

    public function test_returns_403_when_signature_has_expired(): void
    {
        $this->travelTo('2026-11-16 12:00:00');
        [, , $url] = $this->createTrack();
        $this->travel(6)->minutes();

        $this->getJson($url)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'INVALID_MEDIA_SIGNATURE');
    }

    public function test_returns_403_when_signed_track_id_is_modified(): void
    {
        [$track, , $url] = $this->createTrack();
        $modifiedUrl = str_replace("/{$track->id}/stream", '/999999/stream', $url);

        $this->getJson($modifiedUrl)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'INVALID_MEDIA_SIGNATURE');
    }

    public function test_returns_404_when_signed_track_is_no_longer_active(): void
    {
        [$track, , $url] = $this->createTrack();
        $track->update(['is_active' => false]);

        $this->getJson($url)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'AUDIOBOOK_NOT_FOUND');
    }

    public function test_returns_403_for_vip_audio_track(): void
    {
        $book = Book::factory()->published()->vip()->create();
        [, , $url] = $this->createTrack($book);

        $this->getJson($url)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'CONTENT_ACCESS_DENIED');
    }

    public function test_large_audio_stream_has_bounded_memory_growth(): void
    {
        Storage::fake('media');
        $size = 16 * 1_024 * 1_024;
        $storagePath = 'audio/large-track.mp3';
        Storage::disk('media')->makeDirectory('audio');
        $path = Storage::disk('media')->path($storagePath);
        $handle = fopen($path, 'wb');
        $chunk = str_repeat('x', 65_536);

        $this->assertIsResource($handle);

        for ($written = 0; $written < $size; $written += strlen($chunk)) {
            fwrite($handle, $chunk);
        }

        fclose($handle);
        $book = Book::factory()->published()->create();
        $track = BookFile::factory()->audio()->for($book)->create([
            'storage_path' => $storagePath,
            'size' => $size,
            'checksum_sha256' => hash_file('sha256', $path),
        ]);
        $response = app(AudioTrackStreamController::class)(
            Request::create("/api/v1/audio-tracks/{$track->id}/stream"),
            (string) $track->id,
            app(ResolveByteRange::class),
            app(OpenBookFileStream::class),
        );
        $streamedBytes = 0;
        ob_start(function (string $buffer) use (&$streamedBytes): string {
            $streamedBytes += strlen($buffer);

            return '';
        }, 65_536);
        memory_reset_peak_usage();
        $baselineMemory = memory_get_usage(true);

        try {
            $response->sendContent();
        } finally {
            ob_end_flush();
        }

        $memoryGrowth = memory_get_peak_usage(true) - $baselineMemory;

        $this->assertSame($size, $streamedBytes);
        $this->assertLessThan(4 * 1_024 * 1_024, $memoryGrowth);
    }

    /**
     * @return array{BookFile, string, string}
     */
    private function createTrack(?Book $book = null): array
    {
        Storage::fake('media');
        $bytes = '0123456789';
        $book ??= Book::factory()->published()->create();
        $track = BookFile::factory()->audio()->for($book)->create([
            'storage_path' => 'audio/test-track-'.$book->id.'.mp3',
            'size' => strlen($bytes),
            'checksum_sha256' => hash('sha256', $bytes),
        ]);
        Storage::disk('media')->put($track->storage_path, $bytes);
        $url = URL::temporarySignedRoute(
            'audio-tracks.stream',
            now()->addMinutes(5),
            ['track' => $track],
        );

        return [$track, $bytes, $url];
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function validRangeProvider(): array
    {
        return [
            'open ended' => ['bytes=7-', 'bytes 7-9/10', '789'],
            'suffix' => ['bytes=-4', 'bytes 6-9/10', '6789'],
            'last byte' => ['bytes=9-9', 'bytes 9-9/10', '9'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidRangeProvider(): array
    {
        return [
            'reverse range' => ['bytes=5-4'],
            'multiple ranges' => ['bytes=0-1,4-5'],
            'unsupported unit' => ['items=0-1'],
        ];
    }
}
