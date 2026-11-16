<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Media\CreateSignedMediaUrl;
use App\BookAccessLevel;
use App\BookFileType;
use App\Exceptions\AudiobookNotFound;
use App\Exceptions\BookNotFound;
use App\Exceptions\ContentAccessDenied;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AudioTrackResource;
use App\Models\Book;
use App\Models\BookFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AudioTrackController extends Controller
{
    public function __invoke(
        Request $request,
        string $book,
        CreateSignedMediaUrl $createSignedMediaUrl,
    ): JsonResponse {
        $bookModel = $this->findPublishedBook($book);

        if ($bookModel->access_level === BookAccessLevel::Vip) {
            throw new ContentAccessDenied;
        }

        $tracks = $bookModel->files()
            ->where('type', BookFileType::Audio->value)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($tracks->isEmpty()) {
            throw new AudiobookNotFound;
        }

        $expiresAt = now()->addMinutes((int) config('media.signed_url_ttl_minutes'));

        return response()->json([
            'data' => $tracks->map(
                fn (BookFile $track): array => (new AudioTrackResource(
                    resource: $track,
                    streamUrl: $createSignedMediaUrl->handle($track, $expiresAt),
                    expiresAt: $expiresAt,
                ))->resolve($request),
            )->all(),
        ]);
    }

    private function findPublishedBook(string $book): Book
    {
        $query = Book::query()->visibleToReaders();

        if (ctype_digit($book)) {
            $query->whereKey((int) $book);
        } else {
            $query->where('slug', $book);
        }

        return $query->first() ?? throw new BookNotFound;
    }
}
