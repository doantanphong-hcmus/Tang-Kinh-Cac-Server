<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CatalogMigrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_catalog_schema_stores_relational_data_with_defaults(): void
    {
        $catalog = $this->createCatalog();

        $book = DB::table('books')->find($catalog['book_id']);
        $genre = DB::table('genres')->find($catalog['genre_id']);

        $this->assertNotNull($book);
        $this->assertSame('0.00', (string) $book->average_rating);
        $this->assertSame(0, $book->review_count);
        $this->assertNotNull($genre);
        $this->assertTrue((bool) $genre->is_active);
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $catalog['book_id'],
            'genre_id' => $catalog['genre_id'],
        ]);
        $this->assertDatabaseHas('book_alternative_titles', [
            'book_id' => $catalog['book_id'],
            'title' => 'The Tale of Kieu',
        ]);
        $this->assertDatabaseHas('book_keywords', [
            'book_id' => $catalog['book_id'],
            'value' => 'classic',
        ]);
    }

    public function test_database_rejects_duplicate_book_slugs(): void
    {
        $catalog = $this->createCatalog();

        $this->expectException(QueryException::class);

        $this->insertBook($catalog['author_id'], 'truyen-kieu');
    }

    public function test_database_rejects_duplicate_book_genres(): void
    {
        $catalog = $this->createCatalog();

        $this->expectException(QueryException::class);

        DB::table('book_genre')->insert([
            'book_id' => $catalog['book_id'],
            'genre_id' => $catalog['genre_id'],
        ]);
    }

    public function test_database_rejects_duplicate_alternative_titles_for_a_book(): void
    {
        $catalog = $this->createCatalog();

        $this->expectException(QueryException::class);

        DB::table('book_alternative_titles')->insert([
            'book_id' => $catalog['book_id'],
            'title' => 'The Tale of Kieu',
        ]);
    }

    public function test_database_rejects_duplicate_keywords_for_a_book(): void
    {
        $catalog = $this->createCatalog();

        $this->expectException(QueryException::class);

        DB::table('book_keywords')->insert([
            'book_id' => $catalog['book_id'],
            'value' => 'classic',
        ]);
    }

    #[DataProvider('invalidBookEnums')]
    public function test_database_rejects_invalid_book_enum_values(string $column, string $value): void
    {
        $authorId = DB::table('authors')->insertGetId(['name' => 'Nguyen Du']);

        $this->expectException(QueryException::class);

        $this->insertBook($authorId, 'invalid-book', [
            $column => $value,
        ]);
    }

    public function test_database_restricts_deleting_an_author_with_books(): void
    {
        $catalog = $this->createCatalog();

        $this->expectException(QueryException::class);

        DB::table('authors')->where('id', $catalog['author_id'])->delete();
    }

    public function test_deleting_a_book_cascades_its_catalog_metadata(): void
    {
        $catalog = $this->createCatalog();

        DB::table('books')->where('id', $catalog['book_id'])->delete();

        $this->assertDatabaseCount('book_genre', 0);
        $this->assertDatabaseCount('book_alternative_titles', 0);
        $this->assertDatabaseCount('book_keywords', 0);
        $this->assertDatabaseHas('authors', ['id' => $catalog['author_id']]);
        $this->assertDatabaseHas('genres', ['id' => $catalog['genre_id']]);
    }

    public function test_deleting_a_genre_cascades_only_its_book_assignments(): void
    {
        $catalog = $this->createCatalog();

        DB::table('genres')->where('id', $catalog['genre_id'])->delete();

        $this->assertDatabaseCount('book_genre', 0);
        $this->assertDatabaseHas('books', ['id' => $catalog['book_id']]);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidBookEnums(): array
    {
        return [
            'unknown access level' => ['access_level', 'premium'],
            'unknown publication status' => ['status', 'deleted'],
        ];
    }

    /**
     * @return array{author_id: int, book_id: int, genre_id: int}
     */
    private function createCatalog(): array
    {
        $authorId = DB::table('authors')->insertGetId([
            'name' => 'Nguyen Du',
            'original_name' => 'Nguyen Du',
        ]);
        $genreId = DB::table('genres')->insertGetId([
            'slug' => 'classic-poetry',
            'name' => 'Classic Poetry',
        ]);
        $bookId = $this->insertBook($authorId, 'truyen-kieu');

        DB::table('book_genre')->insert([
            'book_id' => $bookId,
            'genre_id' => $genreId,
        ]);
        DB::table('book_alternative_titles')->insert([
            'book_id' => $bookId,
            'title' => 'The Tale of Kieu',
        ]);
        DB::table('book_keywords')->insert([
            'book_id' => $bookId,
            'value' => 'classic',
        ]);

        return [
            'author_id' => $authorId,
            'book_id' => $bookId,
            'genre_id' => $genreId,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertBook(int $authorId, string $slug, array $overrides = []): int
    {
        return DB::table('books')->insertGetId(array_replace([
            'author_id' => $authorId,
            'slug' => $slug,
            'title' => 'Truyen Kieu',
            'country' => 'Vietnam',
            'synopsis' => 'A Vietnamese narrative poem.',
            'access_level' => 'free',
            'status' => 'published',
        ], $overrides));
    }
}
