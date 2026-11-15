<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\BookStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListGenreBooksRequest;
use App\Http\Resources\Api\V1\BookCollection;
use App\Http\Resources\Api\V1\GenreResource;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class GenreController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $genres = Genre::query()
            ->active()
            ->withCount([
                'books as published_books_count' => fn (Builder $query): Builder => $query->where(
                    'status',
                    BookStatus::Published->value,
                ),
            ])
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return GenreResource::collection($genres);
    }

    public function books(ListGenreBooksRequest $request, string $genre): BookCollection
    {
        $genreQuery = Genre::query()->active();

        if (ctype_digit($genre)) {
            $genreQuery->whereKey((int) $genre);
        } else {
            $genreQuery->where('slug', $genre);
        }

        $genreModel = $genreQuery->firstOrFail();
        $books = $genreModel->books()
            ->visibleToReaders()
            ->with('author')
            ->orderBy('books.title')
            ->orderBy('books.id')
            ->paginate($request->perPage());

        return new BookCollection($books);
    }
}
