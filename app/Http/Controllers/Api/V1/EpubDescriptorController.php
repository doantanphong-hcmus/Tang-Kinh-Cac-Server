<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Media\CreateSignedMediaUrl;
use App\Actions\Media\SelectBookFile;
use App\BookAccessLevel;
use App\BookFileType;
use App\Exceptions\BookNotFound;
use App\Exceptions\ContentAccessDenied;
use App\Exceptions\EpubNotFound;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EpubDescriptorResource;
use App\Models\Book;

final class EpubDescriptorController extends Controller
{
    public function __invoke(
        string $book,
        SelectBookFile $selectBookFile,
        CreateSignedMediaUrl $createSignedMediaUrl,
    ): EpubDescriptorResource {
        $bookModel = $this->findPublishedBook($book);

        if ($bookModel->access_level === BookAccessLevel::Vip) {
            throw new ContentAccessDenied;
        }

        $file = $selectBookFile->handle($bookModel, BookFileType::Epub);

        if ($file === null) {
            throw new EpubNotFound;
        }

        $expiresAt = now()->addMinutes((int) config('media.signed_url_ttl_minutes'));

        return new EpubDescriptorResource(
            resource: $file,
            downloadUrl: $createSignedMediaUrl->handle($file, $expiresAt),
            expiresAt: $expiresAt,
        );
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
