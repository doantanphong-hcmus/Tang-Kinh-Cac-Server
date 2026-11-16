<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Models\Book;
use App\Models\BookFile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AudiobookDescriptorTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_requires_authentication(): void
    {
        $book = Book::factory()->published()->create();

        $this->getJson("/api/v1/books/{$book->id}/audiobook")
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_returns_active_audiobook_summary_for_published_free_book(): void
    {
        $book = Book::factory()->published()->create([
            'slug' => 'truyen-kieu',
            'title' => 'Truyen Kieu',
        ]);
        BookFile::factory()->audio()->for($book)->create([
            'duration_seconds' => 1_240,
            'sort_order' => 1,
        ]);
        BookFile::factory()->audio()->for($book)->create([
            'duration_seconds' => 760,
            'sort_order' => 2,
        ]);
        BookFile::factory()->audio()->inactive()->for($book)->create([
            'duration_seconds' => 999,
            'sort_order' => 3,
        ]);
        BookFile::factory()->for($book)->create();
        $this->authenticate();

        $response = $this->getJson('/api/v1/books/truyen-kieu/audiobook');

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'book_id' => $book->id,
                    'title' => 'Truyen Kieu',
                    'cover_url' => null,
                    'total_duration_seconds' => 2_000,
                    'track_count' => 2,
                    'available' => true,
                ],
            ]);
    }

    public function test_returns_403_for_vip_audiobook(): void
    {
        $book = Book::factory()->published()->vip()->create();
        BookFile::factory()->audio()->for($book)->create();
        $this->authenticate();

        $this->getJson("/api/v1/books/{$book->id}/audiobook")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'CONTENT_ACCESS_DENIED')
            ->assertJsonPath('error.message', 'Your account cannot access this content.');
    }

    public function test_returns_404_when_no_active_audio_tracks_exist(): void
    {
        $book = Book::factory()->published()->create();
        BookFile::factory()->audio()->inactive()->for($book)->create();
        BookFile::factory()->for($book)->create();
        $this->authenticate();

        $this->getJson("/api/v1/books/{$book->id}/audiobook")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'AUDIOBOOK_NOT_FOUND')
            ->assertJsonPath('error.message', 'No audiobook is available for this book.');
    }

    public function test_returns_404_for_unpublished_book(): void
    {
        $book = Book::factory()->create();
        BookFile::factory()->audio()->for($book)->create();
        $this->authenticate();

        $this->getJson("/api/v1/books/{$book->id}/audiobook")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'BOOK_NOT_FOUND');
    }

    private function authenticate(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }
}
