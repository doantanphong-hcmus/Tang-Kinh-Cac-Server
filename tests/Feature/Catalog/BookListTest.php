<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class BookListTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/books')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_returns_only_published_books_in_title_order_with_pagination_metadata(): void
    {
        $beta = Book::factory()->published()->create(['title' => 'Beta']);
        $alpha = Book::factory()->published()->create(['title' => 'Alpha']);
        Book::factory()->create(['title' => 'Draft']);
        Book::factory()->archived()->create(['title' => 'Archived']);
        $this->authenticate();

        $response = $this->getJson('/api/v1/books');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $alpha->id)
            ->assertJsonPath('data.0.author.name', $alpha->author->name)
            ->assertJsonPath('data.1.id', $beta->id)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.last_page', 1)
            ->assertJsonMissingPath('links');
    }

    public function test_searches_titles_without_accents_or_case(): void
    {
        $matchingBook = Book::factory()->published()->create(['title' => 'Truyện Kiều']);
        Book::factory()->published()->create(['title' => 'Lục Vân Tiên']);
        $this->authenticate();

        $response = $this->getJson('/api/v1/books?q=TRUYEN%20KIEU');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingBook->id);
    }

    public function test_combines_genre_country_and_access_level_filters(): void
    {
        $genre = Genre::factory()->create(['slug' => 'classics']);
        $otherGenre = Genre::factory()->create(['slug' => 'history']);
        $matchingBook = Book::factory()->published()->vip()->create(['country' => 'Vietnam']);
        $matchingBook->genres()->attach($genre);
        $freeBook = Book::factory()->published()->create(['country' => 'Vietnam']);
        $freeBook->genres()->attach($genre);
        $foreignBook = Book::factory()->published()->vip()->create(['country' => 'France']);
        $foreignBook->genres()->attach($genre);
        $otherGenreBook = Book::factory()->published()->vip()->create(['country' => 'Vietnam']);
        $otherGenreBook->genres()->attach($otherGenre);
        $this->authenticate();

        $response = $this->getJson('/api/v1/books?genre=CLASSICS&country=vietnam&access_level=VIP');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingBook->id);
    }

    #[DataProvider('sortOrders')]
    public function test_sorts_books_deterministically(string $sort, array $expectedTitles): void
    {
        Book::factory()->published()->create([
            'title' => 'Alpha',
            'average_rating' => '4.50',
            'published_at' => '2026-01-01 00:00:00',
        ]);
        Book::factory()->published()->create([
            'title' => 'Beta',
            'average_rating' => '3.00',
            'published_at' => '2026-03-01 00:00:00',
        ]);
        Book::factory()->published()->create([
            'title' => 'Gamma',
            'average_rating' => '4.50',
            'published_at' => '2026-02-01 00:00:00',
        ]);
        Book::factory()->published()->create([
            'title' => 'Delta',
            'average_rating' => '0.00',
            'published_at' => null,
        ]);
        $this->authenticate();

        $response = $this->getJson("/api/v1/books?sort={$sort}");

        $response->assertOk();
        $this->assertSame($expectedTitles, $response->json('data.*.title'));
    }

    public function test_applies_requested_page_size_and_page(): void
    {
        Book::factory()->published()->create(['title' => 'Alpha']);
        Book::factory()->published()->create(['title' => 'Beta']);
        $gamma = Book::factory()->published()->create(['title' => 'Gamma']);
        $this->authenticate();

        $response = $this->getJson('/api/v1/books?per_page=2&page=2');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $gamma->id)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 2);
    }

    #[DataProvider('invalidFilters')]
    public function test_rejects_invalid_filter_values(string $query, string $field, string $message): void
    {
        $this->authenticate();

        $response = $this->getJson("/api/v1/books?{$query}");

        $response
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonPath("error.fields.{$field}.0", $message);
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function sortOrders(): array
    {
        return [
            'title' => ['title', ['Alpha', 'Beta', 'Delta', 'Gamma']],
            'newest with undated books last' => ['newest', ['Beta', 'Gamma', 'Alpha', 'Delta']],
            'rating with id tie breaker' => ['rating', ['Alpha', 'Gamma', 'Beta', 'Delta']],
        ];
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function invalidFilters(): array
    {
        return [
            'page below one' => ['page=0', 'page', 'The page field must be at least 1.'],
            'page size above limit' => ['per_page=51', 'per_page', 'The per page field must be between 1 and 50.'],
            'unknown access level' => ['access_level=premium', 'access_level', 'The selected access level is invalid.'],
            'untrusted sort value' => ['sort=title%20desc', 'sort', 'The selected sort is invalid.'],
        ];
    }

    private function authenticate(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }
}
