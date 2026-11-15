<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class GenreBookListTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_requires_authentication(): void
    {
        $genre = Genre::factory()->create();

        $this->getJson("/api/v1/genres/{$genre->slug}/books")
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_returns_paginated_published_books_for_genre_by_slug(): void
    {
        $genre = Genre::factory()->create(['slug' => 'classics']);
        $otherGenre = Genre::factory()->create(['slug' => 'history']);
        $beta = Book::factory()->published()->create(['title' => 'Beta']);
        $alpha = Book::factory()->published()->create(['title' => 'Alpha']);
        $draft = Book::factory()->create(['title' => 'Draft']);
        $archived = Book::factory()->archived()->create(['title' => 'Archived']);
        $otherBook = Book::factory()->published()->create(['title' => 'Other']);
        $genre->books()->attach([$beta->id, $alpha->id, $draft->id, $archived->id]);
        $otherGenre->books()->attach($otherBook);
        $this->authenticate();

        $response = $this->getJson('/api/v1/genres/classics/books?per_page=1&page=2');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $beta->id)
            ->assertJsonPath('data.0.author.name', $beta->author->name)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonMissingPath('links');
    }

    public function test_resolves_active_genre_by_numeric_id(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->published()->create();
        $genre->books()->attach($book);
        $this->authenticate();

        $this->getJson("/api/v1/genres/{$genre->id}/books")
            ->assertOk()
            ->assertJsonPath('data.0.id', $book->id);
    }

    public function test_returns_404_for_missing_genre(): void
    {
        $this->authenticate();

        $this->getJson('/api/v1/genres/missing/books')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_returns_404_for_inactive_genre(): void
    {
        $genre = Genre::factory()->inactive()->create();
        $this->authenticate();

        $this->getJson("/api/v1/genres/{$genre->slug}/books")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_rejects_page_size_above_limit(): void
    {
        $genre = Genre::factory()->create();
        $this->authenticate();

        $this->getJson("/api/v1/genres/{$genre->slug}/books?per_page=51")
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonPath('error.fields.per_page.0', 'The per page field must be between 1 and 50.');
    }

    private function authenticate(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }
}
