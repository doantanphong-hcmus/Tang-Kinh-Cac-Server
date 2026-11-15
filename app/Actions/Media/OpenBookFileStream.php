<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\BookFile;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToReadFile;
use RuntimeException;

final class OpenBookFileStream
{
    /**
     * @return resource
     */
    public function handle(BookFile $file): mixed
    {
        try {
            $stream = Storage::disk($file->storage_disk)->readStream($file->storage_path);
        } catch (UnableToReadFile $exception) {
            throw new RuntimeException('The media file could not be opened.', previous: $exception);
        }

        if (! is_resource($stream)) {
            throw new RuntimeException('The media file could not be opened.');
        }

        return $stream;
    }
}
