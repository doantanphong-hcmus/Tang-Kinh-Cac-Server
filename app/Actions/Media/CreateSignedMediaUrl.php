<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\BookFile;
use DateTimeInterface;
use Illuminate\Support\Facades\URL;

final class CreateSignedMediaUrl
{
    public function handle(BookFile $file, DateTimeInterface $expiresAt): string
    {
        // Sign only the database identifier so storage topology never reaches the client.
        return URL::temporarySignedRoute('media.download', $expiresAt, [
            'file' => $file,
        ]);
    }
}
