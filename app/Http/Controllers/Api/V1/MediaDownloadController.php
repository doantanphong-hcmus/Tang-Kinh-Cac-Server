<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Media\OpenBookFileStream;
use App\BookAccessLevel;
use App\BookFileType;
use App\BookStatus;
use App\Exceptions\ContentAccessDenied;
use App\Exceptions\EpubNotFound;
use App\Http\Controllers\Controller;
use App\Models\BookFile;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MediaDownloadController extends Controller
{
    public function __invoke(string $file, OpenBookFileStream $openBookFileStream): StreamedResponse
    {
        if (! ctype_digit($file)) {
            throw new EpubNotFound;
        }

        $bookFile = BookFile::query()
            ->with('book')
            ->whereKey((int) $file)
            ->where('type', BookFileType::Epub->value)
            ->where('is_active', true)
            ->whereHas('book', fn (Builder $books): Builder => $books
                ->where('status', BookStatus::Published->value))
            ->first();

        if ($bookFile === null) {
            throw new EpubNotFound;
        }

        if ($bookFile->book->access_level === BookAccessLevel::Vip) {
            throw new ContentAccessDenied;
        }

        $stream = $openBookFileStream->handle($bookFile);
        $filename = "book-{$bookFile->book_id}-v{$bookFile->version}.epub";

        // The public filename is server-generated; original filenames may be untrusted.
        return response()->streamDownload(
            function () use ($stream): void {
                try {
                    fpassthru($stream);
                } finally {
                    fclose($stream);
                }
            },
            $filename,
            [
                'Content-Type' => $bookFile->mime_type,
                'Content-Length' => (string) $bookFile->size,
                'ETag' => '"'.$bookFile->checksum_sha256.'"',
            ],
        );
    }
}
