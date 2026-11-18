<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Models\BookFile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AudioSeedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeded_audiobook_tracks_are_ordered_and_stream_the_stored_bytes(): void
    {
        Storage::fake('media');
        $this->seed();
        Sanctum::actingAs(User::query()->where('email', 'reader@example.test')->sole());

        $response = $this->getJson('/api/v1/books/truyen-kieu/audio-tracks')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.title', 'Sample Track 1')
            ->assertJsonPath('data.0.sort_order', 1)
            ->assertJsonPath('data.0.mime_type', 'audio/wav')
            ->assertJsonPath('data.1.title', 'Sample Track 2')
            ->assertJsonPath('data.1.sort_order', 2);

        foreach ($response->json('data') as $trackData) {
            $track = BookFile::query()->findOrFail($trackData['id']);
            $bytes = Storage::disk('media')->get($track->storage_path);

            $this->get($trackData['stream_url'])
                ->assertOk()
                ->assertHeader('Content-Length', (string) strlen($bytes))
                ->assertHeader('ETag', '"'.hash('sha256', $bytes).'"')
                ->assertStreamedContent($bytes);
        }
    }

    public function test_seeded_track_supports_open_ended_seek_request_used_by_media3_okhttp(): void
    {
        Storage::fake('media');
        $this->seed();
        Sanctum::actingAs(User::query()->where('email', 'reader@example.test')->sole());
        $trackResponse = $this->getJson('/api/v1/books/truyen-kieu/audio-tracks')->assertOk();
        $trackId = $trackResponse->json('data.1.id');
        $streamUrl = $trackResponse->json('data.1.stream_url');
        $this->assertIsInt($trackId);
        $this->assertIsString($streamUrl);
        $track = BookFile::query()->findOrFail($trackId);
        $bytes = Storage::disk('media')->get($track->storage_path);

        $this->withHeaders([
            'Accept-Encoding' => 'identity',
            'Range' => 'bytes=44-',
        ])->get($streamUrl)
            ->assertStatus(206)
            ->assertHeader('Accept-Ranges', 'bytes')
            ->assertHeader('Content-Length', '8000')
            ->assertHeader('Content-Range', 'bytes 44-8043/8044')
            ->assertHeader('Content-Type', 'audio/wav')
            ->assertStreamedContent(substr($bytes, 44));
    }
}
