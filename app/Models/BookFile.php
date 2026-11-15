<?php

declare(strict_types=1);

namespace App\Models;

use App\BookFileType;
use Database\Factories\BookFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property BookFileType $type */
#[Fillable([
    'book_id',
    'type',
    'title',
    'storage_disk',
    'storage_path',
    'original_filename',
    'mime_type',
    'size',
    'checksum_sha256',
    'version',
    'sort_order',
    'duration_seconds',
    'is_active',
])]
class BookFile extends Model
{
    /** @use HasFactory<BookFileFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Book, $this>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'is_active' => 'boolean',
            'size' => 'integer',
            'sort_order' => 'integer',
            'type' => BookFileType::class,
            'version' => 'integer',
        ];
    }
}
