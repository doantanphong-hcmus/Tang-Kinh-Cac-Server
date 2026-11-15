<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListBooksRequest;
use App\Http\Resources\Api\V1\BookCollection;
use App\Models\Book;

final class BookController extends Controller
{
    public function index(ListBooksRequest $request): BookCollection
    {
        $filters = $request->filters();
        $query = Book::query()
            ->visibleToReaders()
            ->with('author');

        if ($filters['q'] !== null) {
            $query->search($filters['q']);
        }

        if ($filters['genre'] !== null) {
            $query->whereHas('genres', fn ($genreQuery) => $genreQuery->where('genres.slug', $filters['genre']));
        }

        if ($filters['country'] !== null) {
            // Equality avoids treating user-supplied SQL wildcard characters as country filters.
            $query->whereRaw('LOWER(books.country) = LOWER(?)', [$filters['country']]);
        }

        if ($filters['access_level'] !== null) {
            $query->where('access_level', $filters['access_level']->value);
        }

        // PostgreSQL otherwise places undated rows first when sorting descending.
        $query = match ($filters['sort']) {
            'newest' => $query->orderByRaw('published_at DESC NULLS LAST')->orderByDesc('id'),
            'rating' => $query->orderByDesc('average_rating')->orderBy('id'),
            default => $query->orderBy('title')->orderBy('id'),
        };

        return new BookCollection($query->paginate($filters['per_page']));
    }
}
