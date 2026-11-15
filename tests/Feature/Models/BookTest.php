<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\BookAccessLevel;
use App\BookStatus;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookAlternativeTitle;
use App\Models\BookKeyword;
use App\Models\Genre;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_factory_states_create_cast_catalog_values(): void
    {
        $book = Book::factory()->published()->vip()->create([
            'average_rating' => '4.90',
            'review_count' => 1200,
        ]);

        $this->assertSame(BookAccessLevel::Vip, $book->access_level);
        $this->assertSame(BookStatus::Published, $book->status);
        $this->assertSame('4.90', $book->average_rating);
        $this->assertSame(1200, $book->review_count);
        $this->assertNotNull($book->published_at);
        $this->assertModelExists($book->author);
    }

    public function test_catalog_relationships_are_available_in_both_directions(): void
    {
        $author = Author::factory()->create();
        $book = Book::factory()->for($author)->create();
        $genre = Genre::factory()->create();
        $book->genres()->attach($genre);
        $alternativeTitle = BookAlternativeTitle::factory()->for($book)->create();
        $keyword = BookKeyword::factory()->for($book)->create();

        $book->load(['author', 'genres', 'alternativeTitles', 'keywords']);

        $this->assertTrue($book->author->is($author));
        $this->assertTrue($book->genres->contains($genre));
        $this->assertTrue($book->alternativeTitles->contains($alternativeTitle));
        $this->assertTrue($book->keywords->contains($keyword));
        $this->assertTrue($author->books()->whereKey($book)->exists());
        $this->assertTrue($genre->books()->whereKey($book)->exists());
        $this->assertTrue($alternativeTitle->book->is($book));
        $this->assertTrue($keyword->book->is($book));
    }

    public function test_reader_visibility_scope_returns_only_published_books(): void
    {
        Book::factory()->create();
        $publishedBook = Book::factory()->published()->create();
        $publishedVipBook = Book::factory()->published()->vip()->create();
        Book::factory()->archived()->create();

        $visibleBookIds = Book::visibleToReaders()->orderBy('id')->pluck('id')->all();

        $this->assertSame([
            $publishedBook->id,
            $publishedVipBook->id,
        ], $visibleBookIds);
    }
}
