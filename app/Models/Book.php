<?php

declare(strict_types=1);

namespace App\Models;

use App\BookAccessLevel;
use App\BookFileType;
use App\BookStatus;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property BookAccessLevel $access_level
 * @property string $average_rating
 * @property Carbon|null $published_at
 * @property BookStatus $status
 */
#[Fillable([
    'author_id',
    'slug',
    'title',
    'original_title',
    'country',
    'original_language',
    'publication_period',
    'structure',
    'historical_setting',
    'synopsis',
    'access_level',
    'status',
    'average_rating',
    'review_count',
    'published_at',
])]
class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Author, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    /**
     * @return BelongsToMany<Genre, $this>
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    /**
     * @return HasMany<BookAlternativeTitle, $this>
     */
    public function alternativeTitles(): HasMany
    {
        return $this->hasMany(BookAlternativeTitle::class);
    }

    /**
     * @return HasMany<BookKeyword, $this>
     */
    public function keywords(): HasMany
    {
        return $this->hasMany(BookKeyword::class);
    }

    /**
     * @return HasMany<BookFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(BookFile::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_level' => BookAccessLevel::class,
            'average_rating' => 'decimal:2',
            'published_at' => 'datetime',
            'review_count' => 'integer',
            'status' => BookStatus::class,
        ];
    }

    /**
     * @param  Builder<Book>  $query
     * @return Builder<Book>
     */
    #[Scope]
    protected function visibleToReaders(Builder $query): Builder
    {
        return $query->where('status', BookStatus::Published->value);
    }

    /**
     * @param  Builder<Book>  $query
     * @return Builder<Book>
     */
    #[Scope]
    protected function withContentAvailability(Builder $query): Builder
    {
        return $query->withExists([
            'files as has_epub' => fn (Builder $files): Builder => $files
                ->where('type', BookFileType::Epub->value)
                ->where('is_active', true),
            'files as has_audiobook' => fn (Builder $files): Builder => $files
                ->where('type', BookFileType::Audio->value)
                ->where('is_active', true),
        ]);
    }

    /**
     * @param  Builder<Book>  $query
     * @return Builder<Book>
     */
    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $escapedTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
        $pattern = "%{$escapedTerm}%";

        // shortcut: unaccent ILIKE scans text, add indexed search vectors when catalog query plans require them.
        return $query->where(function (Builder $query) use ($pattern): void {
            $query
                ->whereRaw("unaccent(books.title) ILIKE unaccent(?) ESCAPE '\\'", [$pattern])
                ->orWhereRaw("unaccent(books.original_title) ILIKE unaccent(?) ESCAPE '\\'", [$pattern])
                ->orWhereRaw("unaccent(books.synopsis) ILIKE unaccent(?) ESCAPE '\\'", [$pattern])
                ->orWhereHas('author', fn (Builder $authorQuery): Builder => $authorQuery->whereRaw(
                    "(unaccent(authors.name) ILIKE unaccent(?) ESCAPE '\\' OR unaccent(authors.original_name) ILIKE unaccent(?) ESCAPE '\\')",
                    [$pattern, $pattern],
                ))
                ->orWhereHas('genres', fn (Builder $genreQuery): Builder => $genreQuery->whereRaw(
                    "unaccent(genres.name) ILIKE unaccent(?) ESCAPE '\\'",
                    [$pattern],
                ))
                ->orWhereHas('alternativeTitles', fn (Builder $titleQuery): Builder => $titleQuery->whereRaw(
                    "unaccent(book_alternative_titles.title) ILIKE unaccent(?) ESCAPE '\\'",
                    [$pattern],
                ))
                ->orWhereHas('keywords', fn (Builder $keywordQuery): Builder => $keywordQuery->whereRaw(
                    "unaccent(book_keywords.value) ILIKE unaccent(?) ESCAPE '\\'",
                    [$pattern],
                ));
        });
    }
}
