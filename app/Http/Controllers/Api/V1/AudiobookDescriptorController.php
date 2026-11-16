<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\BookAccessLevel;
use App\BookFileType;
use App\Exceptions\AudiobookNotFound;
use App\Exceptions\BookNotFound;
use App\Exceptions\ContentAccessDenied;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AudiobookDescriptorResource;
use App\Models\Book;
use Illuminate\Database\Eloquent\Builder;

final class AudiobookDescriptorController extends Controller
{
    public function __invoke(string $book): AudiobookDescriptorResource
    {
        $bookModel = $this->findPublishedBook($book);

        if ($bookModel->access_level === BookAccessLevel::Vip) {
            throw new ContentAccessDenied;
        }

        if ($bookModel->active_audio_track_count === 0) {
            throw new AudiobookNotFound;
        }

        return new AudiobookDescriptorResource($bookModel);
    }

    private function findPublishedBook(string $book): Book
    {
        $query = Book::query()
            ->visibleToReaders()
            ->withCount([
                'files as active_audio_track_count' => fn (Builder $files): Builder => $files
                    ->where('type', BookFileType::Audio->value)
                    ->where('is_active', true),
            ])
            ->withSum([
                'files as active_audio_total_duration_seconds' => fn (Builder $files): Builder => $files
                    ->where('type', BookFileType::Audio->value)
                    ->where('is_active', true),
            ], 'duration_seconds');

        if (ctype_digit($book)) {
            $query->whereKey((int) $book);
        } else {
            $query->where('slug', $book);
        }

        return $query->first() ?? throw new BookNotFound;
    }
}
