<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\BookFileType;
use App\Models\BookFile;
use DateTimeInterface;
use Illuminate\Support\Facades\URL;

final class CreateSignedMediaUrl
{
    public function handle(BookFile $file, DateTimeInterface $expiresAt): string
    {
        [$route, $parameters] = $file->type === BookFileType::Audio
            ? ['audio-tracks.stream', ['track' => $file]]
            : ['media.download', ['file' => $file]];

        // Sign only the database identifier so storage topology never reaches the client.
        return URL::temporarySignedRoute($route, $expiresAt, $parameters);
    }
}
