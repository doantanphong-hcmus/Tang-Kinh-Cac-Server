<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\BookFileType;
use App\Models\Book;
use App\Models\BookFile;

final class SelectBookFile
{
    public function handle(Book $book, BookFileType $type): ?BookFile
    {
        // The highest version wins; ID makes selection deterministic if imported metadata ties.
        return $book->files()
            ->where('type', $type->value)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->first();
    }
}
