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

final class AudioTrackTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_requires_authentication(): void
    {
        $book = Book::factory()->published()->create();

        $this->getJson("/api/v1/books/{$book->id}/audio-tracks")
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_returns_active_tracks_in_stable_order_with_signed_urls(): void
    {
        $this->travelTo('2026-11-16 12:00:00');
        $book = Book::factory()->published()->create(['slug' => 'truyen-kieu']);
        $secondTrack = BookFile::factory()->audio()->for($book)->create([
            'title' => 'Chapter 2',
            'sort_order' => 2,
            'duration_seconds' => 760,
            'size' => 18_200_000,
            'checksum_sha256' => str_repeat('b', 64),
        ]);
        $firstTrack = BookFile::factory()->audio()->for($book)->create([
            'title' => 'Chapter 1',
            'sort_order' => 1,
            'duration_seconds' => 1_240,
            'size' => 20_100_000,
            'checksum_sha256' => str_repeat('a', 64),
        ]);
        BookFile::factory()->audio()->inactive()->for($book)->create(['sort_order' => 0]);
        BookFile::factory()->for($book)->create();
        $this->authenticate();

        $response = $this->getJson('/api/v1/books/truyen-kieu/audio-tracks');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $firstTrack->id)
            ->assertJsonPath('data.0.title', 'Chapter 1')
            ->assertJsonPath('data.0.sort_order', 1)
            ->assertJsonPath('data.0.duration_seconds', 1_240)
            ->assertJsonPath('data.0.mime_type', 'audio/mpeg')
            ->assertJsonPath('data.0.size', 20_100_000)
            ->assertJsonPath('data.0.checksum_sha256', str_repeat('a', 64))
            ->assertJsonPath('data.0.expires_at', '2026-11-16T12:05:00Z')
            ->assertJsonPath('data.1.id', $secondTrack->id)
            ->assertJsonPath('data.1.expires_at', '2026-11-16T12:05:00Z')
            ->assertJsonMissingPath('data.0.original_filename')
            ->assertJsonMissingPath('data.0.storage_disk')
            ->assertJsonMissingPath('data.0.storage_path');

        $this->assertStringNotContainsString($firstTrack->storage_path, $response->getContent());
        $this->assertStringNotContainsString($secondTrack->storage_path, $response->getContent());

        foreach ($response->json('data.*') as $trackData) {
            $streamUrl = $trackData['stream_url'];
            $this->assertIsString($streamUrl);
            $this->assertTrue(URL::hasValidSignature(Request::create($streamUrl)));
            $this->assertStringContainsString("/api/v1/audio-tracks/{$trackData['id']}/stream", $streamUrl);
        }
    }

    public function test_returns_403_for_vip_audiobook_tracks(): void
    {
        $book = Book::factory()->published()->vip()->create();
        BookFile::factory()->audio()->for($book)->create();
        $this->authenticate();

        $this->getJson("/api/v1/books/{$book->id}/audio-tracks")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'CONTENT_ACCESS_DENIED');
    }

    public function test_returns_404_when_no_active_audio_tracks_exist(): void
    {
        $book = Book::factory()->published()->create();
        BookFile::factory()->audio()->inactive()->for($book)->create();
        $this->authenticate();

        $this->getJson("/api/v1/books/{$book->id}/audio-tracks")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'AUDIOBOOK_NOT_FOUND');
    }

    public function test_returns_404_for_unpublished_book(): void
    {
        $book = Book::factory()->create();
        BookFile::factory()->audio()->for($book)->create();
        $this->authenticate();

        $this->getJson("/api/v1/books/{$book->id}/audio-tracks")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'BOOK_NOT_FOUND');
    }

    private function authenticate(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }
}
