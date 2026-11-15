<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BookAlternativeTitleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['book_id', 'title'])]
class BookAlternativeTitle extends Model
{
    /** @use HasFactory<BookAlternativeTitleFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return BelongsTo<Book, $this>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
