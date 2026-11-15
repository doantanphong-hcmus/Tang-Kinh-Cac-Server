<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BookKeywordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['book_id', 'value'])]
class BookKeyword extends Model
{
    /** @use HasFactory<BookKeywordFactory> */
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
