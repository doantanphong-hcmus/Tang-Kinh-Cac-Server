<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Models\Book;
use App\Models\BookFile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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

    public function test_unsatisfiable_range_returns_416_with_file_boundary(): void
    {
        [, , $url] = $this->createTrack();

        $this->withHeader('Range', 'bytes=10-')->getJson($url)
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

    public function test_returns_403_for_vip_audio_track(): void
    {
        $book = Book::factory()->published()->vip()->create();
        [, , $url] = $this->createTrack($book);

        $this->getJson($url)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'CONTENT_ACCESS_DENIED');
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
}
