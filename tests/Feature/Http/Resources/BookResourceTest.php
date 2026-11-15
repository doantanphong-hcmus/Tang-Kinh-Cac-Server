<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Resources;

use App\Http\Resources\Api\V1\BookDetailResource;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookAlternativeTitle;
use App\Models\BookKeyword;
use App\Models\Genre;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class BookResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->prefix('api/v1/testing')->group(function (): void {
            Route::get('/book-resources/{book}', function (Book $book): BookResource {
                return new BookResource($book->load('author'));
            });

            Route::get('/book-detail-resources/{book}', function (Book $book): BookDetailResource {
                return new BookDetailResource($book->load([
                    'alternativeTitles',
                    'author',
                    'genres',
                    'keywords',
                ]));
            });
        });
    }

    public function test_list_resource_returns_stable_catalog_shape(): void
    {
        $author = Author::factory()->create([
            'name' => 'Nguyễn Du',
            'original_name' => 'Tố Như',
        ]);
        $book = Book::factory()->published()->for($author)->create([
            'slug' => 'truyen-kieu',
            'title' => 'Truyện Kiều',
            'country' => 'Vietnam',
            'average_rating' => '4.90',
            'review_count' => 1200,
        ]);

        $response = $this->getJson("/api/v1/testing/book-resources/{$book->id}");

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'id' => $book->id,
                    'slug' => 'truyen-kieu',
                    'title' => 'Truyện Kiều',
                    'author' => [
                        'id' => $author->id,
                        'name' => 'Nguyễn Du',
                    ],
                    'country' => 'Vietnam',
                    'access_level' => 'free',
                    'status' => 'published',
                    'average_rating' => '4.90',
                    'review_count' => 1200,
                    'cover_url' => null,
                    'has_epub' => false,
                    'has_audiobook' => false,
                ],
            ]);
    }

    public function test_detail_resource_returns_nested_catalog_metadata(): void
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

        $response = $this->getJson("/api/v1/testing/book-detail-resources/{$book->id}");

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
}
