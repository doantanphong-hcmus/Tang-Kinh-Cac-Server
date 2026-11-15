<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookAlternativeTitle;
use App\Models\BookKeyword;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class BookDetailTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_requires_authentication(): void
    {
        $book = Book::factory()->published()->create();

        $this->getJson("/api/v1/books/{$book->id}")
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_returns_published_book_detail_by_numeric_id(): void
    {
        $author = Author::factory()->create([
            'name' => 'Nguyễn Du',
            'original_name' => 'Tố Như',
        ]);
        $book = Book::factory()->published()->for($author)->create([
            'slug' => 'truyen-kieu',
            'title' => 'Truyện Kiều',
            'original_title' => 'Đoạn trường tân thanh',
            'country' => 'Vietnam',
            'original_language' => 'Vietnamese',
            'publication_period' => 'Early 19th century',
            'structure' => '3,254 lines of lục bát verse',
            'historical_setting' => 'A society shaped by family duty.',
            'synopsis' => 'A gifted young woman protects her family.',
            'average_rating' => '4.90',
            'review_count' => 1200,
        ]);
        $genre = Genre::factory()->create([
            'slug' => 'van-hoc-co-dien',
            'name' => 'Văn học cổ điển',
        ]);
        $book->genres()->attach($genre);
        BookAlternativeTitle::factory()->for($book)->create([
            'title' => 'Kim Vân Kiều truyện',
        ]);
        BookKeyword::factory()->for($book)->create([
            'value' => 'thân phận',
        ]);
        $this->authenticate();

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'id' => $book->id,
                    'slug' => 'truyen-kieu',
                    'title' => 'Truyện Kiều',
                    'original_title' => 'Đoạn trường tân thanh',
                    'alternative_titles' => ['Kim Vân Kiều truyện'],
                    'author' => [
                        'id' => $author->id,
                        'name' => 'Nguyễn Du',
                        'original_name' => 'Tố Như',
                    ],
                    'country' => 'Vietnam',
                    'original_language' => 'Vietnamese',
                    'publication_period' => 'Early 19th century',
                    'genres' => [[
                        'id' => $genre->id,
                        'slug' => 'van-hoc-co-dien',
                        'name' => 'Văn học cổ điển',
                    ]],
                    'structure' => '3,254 lines of lục bát verse',
                    'historical_setting' => 'A society shaped by family duty.',
                    'synopsis' => 'A gifted young woman protects her family.',
                    'keywords' => ['thân phận'],
                    'access_level' => 'free',
                    'status' => 'published',
                    'average_rating' => '4.90',
                    'review_count' => 1200,
                    'cover_url' => null,
                    'has_epub' => false,
                    'has_audiobook' => false,
                    'available_content_types' => [],
                ],
            ]);
    }

    public function test_returns_published_book_detail_by_slug(): void
    {
        $book = Book::factory()->published()->create([
            'slug' => 'truyen-kieu',
        ]);
        $this->authenticate();

        $this->getJson('/api/v1/books/truyen-kieu')
            ->assertOk()
            ->assertJsonPath('data.id', $book->id);
    }

    public function test_returns_404_for_missing_book(): void
    {
        $this->authenticate();

        $this->getJson('/api/v1/books/missing-book')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'BOOK_NOT_FOUND')
            ->assertJsonPath('error.message', 'The requested book was not found.');
    }

    public function test_returns_404_for_draft_book(): void
    {
        $book = Book::factory()->create();
        $this->authenticate();

        $this->getJson("/api/v1/books/{$book->id}")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'BOOK_NOT_FOUND');
    }

    public function test_returns_404_for_archived_book(): void
    {
        $book = Book::factory()->archived()->create();
        $this->authenticate();

        $this->getJson("/api/v1/books/{$book->slug}")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'BOOK_NOT_FOUND');
    }

    private function authenticate(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }
}
