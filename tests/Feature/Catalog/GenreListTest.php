<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class GenreListTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/genres')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_returns_active_genres_in_name_order_with_published_book_counts(): void
    {
        $zeta = Genre::factory()->create([
            'slug' => 'zeta',
            'name' => 'Zeta',
        ]);
        $alpha = Genre::factory()->create([
            'slug' => 'alpha',
            'name' => 'Alpha',
        ]);
        $inactive = Genre::factory()->inactive()->create([
            'slug' => 'hidden',
            'name' => 'Hidden',
        ]);
        $alpha->books()->attach(Book::factory()->published()->create());
        $alpha->books()->attach(Book::factory()->published()->create());
        $alpha->books()->attach(Book::factory()->create());
        $alpha->books()->attach(Book::factory()->archived()->create());
        $inactive->books()->attach(Book::factory()->published()->create());
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/genres');

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    [
                        'id' => $alpha->id,
                        'slug' => 'alpha',
                        'name' => 'Alpha',
                        'published_book_count' => 2,
                    ],
                    [
                        'id' => $zeta->id,
                        'slug' => 'zeta',
                        'name' => 'Zeta',
                        'published_book_count' => 0,
                    ],
                ],
            ]);
    }
}
