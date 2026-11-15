<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookAlternativeTitle;
use App\Models\BookKeyword;
use App\Models\Genre;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[DataProvider('accentInsensitiveSearchTerms')]
    public function test_search_matches_catalog_fields_without_accents_or_case(string $term): void
    {
        $matchingBook = $this->createSearchCatalog();
        Book::factory()->create([
            'title' => 'Unrelated Book',
            'synopsis' => 'No matching catalog text.',
        ]);

        $bookIds = Book::search($term)->pluck('id')->all();

        $this->assertSame([$matchingBook->id], $bookIds);
    }

    public function test_search_matches_accented_term_against_unaccented_text(): void
    {
        $book = Book::factory()->create([
            'title' => 'Truyen Kieu',
        ]);

        $bookIds = Book::search('Truyện Kiều')->pluck('id')->all();

        $this->assertSame([$book->id], $bookIds);
    }

    public function test_search_treats_sql_wildcards_as_literal_text(): void
    {
        $book = Book::factory()->create([
            'title' => 'The 100% Reader',
        ]);
        Book::factory()->create([
            'title' => 'Ordinary Reader',
        ]);

        $bookIds = Book::search('%')->pluck('id')->all();

        $this->assertSame([$book->id], $bookIds);
    }

    public function test_blank_search_term_keeps_the_query_unfiltered(): void
    {
        $books = Book::factory()->count(2)->create();

        $bookIds = Book::search('  ')->orderBy('id')->pluck('id')->all();

        $this->assertSame($books->pluck('id')->all(), $bookIds);
    }

    public function test_search_term_cannot_inject_sql(): void
    {
        Book::factory()->create([
            'title' => 'Truyen Kieu',
        ]);

        $bookIds = Book::search("%' OR 1=1 --")->pluck('id')->all();

        $this->assertSame([], $bookIds);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function accentInsensitiveSearchTerms(): array
    {
        return [
            'title and case' => ['TRUYEN KIEU'],
            'original title' => ['kim van kieu'],
            'alternative title' => ['doan truong tan thanh'],
            'author' => ['NGUYEN DU'],
            'genre' => ['van hoc co dien'],
            'synopsis' => ['so phan con nguoi'],
            'keyword' => ['than phan'],
        ];
    }

    private function createSearchCatalog(): Book
    {
        $author = Author::factory()->create([
            'name' => 'Nguyễn Du',
        ]);
        $book = Book::factory()->for($author)->create([
            'title' => 'Truyện Kiều',
            'original_title' => 'Kim Vân Kiều',
            'synopsis' => 'Câu chuyện về số phận con người.',
        ]);
        $genre = Genre::factory()->create([
            'slug' => 'van-hoc-co-dien',
            'name' => 'Văn học cổ điển',
        ]);
        $book->genres()->attach($genre);
        BookAlternativeTitle::factory()->for($book)->create([
            'title' => 'Đoạn trường tân thanh',
        ]);
        BookKeyword::factory()->for($book)->create([
            'value' => 'thân phận',
        ]);

        return $book;
    }
}
