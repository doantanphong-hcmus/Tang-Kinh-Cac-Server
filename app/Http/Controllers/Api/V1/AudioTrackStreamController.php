<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Media\OpenBookFileStream;
use App\Actions\Media\ResolveByteRange;
use App\BookAccessLevel;
use App\BookFileType;
use App\BookStatus;
use App\Exceptions\AudiobookNotFound;
use App\Exceptions\ContentAccessDenied;
use App\Http\Controllers\Controller;
use App\Models\BookFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AudioTrackStreamController extends Controller
{
    private const int CHUNK_SIZE = 65_536;

    public function __invoke(
        Request $request,
        string $track,
        ResolveByteRange $resolveByteRange,
        OpenBookFileStream $openBookFileStream,
    ): StreamedResponse {
        $audioTrack = $this->findAvailableTrack($track);

        if ($audioTrack->book->access_level === BookAccessLevel::Vip) {
            throw new ContentAccessDenied;
        }

        $rangeHeader = $request->header('Range');
        $range = $resolveByteRange->handle(
            is_string($rangeHeader) ? $rangeHeader : null,
            $audioTrack->size,
        );
        $stream = $openBookFileStream->handle($audioTrack);
        $headers = [
            'Accept-Ranges' => 'bytes',
            'Content-Length' => (string) $range['length'],
            'Content-Type' => $audioTrack->mime_type,
            'ETag' => '"'.$audioTrack->checksum_sha256.'"',
        ];

        if ($range['partial']) {
            $headers['Content-Range'] = "bytes {$range['start']}-{$range['end']}/{$audioTrack->size}";
        }

        return response()->stream(
            fn () => $this->streamRange($stream, $range['start'], $range['length']),
            $range['partial'] ? 206 : 200,
            $headers,
        );
    }

    private function findAvailableTrack(string $track): BookFile
    {
        if (! ctype_digit($track)) {
            throw new AudiobookNotFound;
        }

        return BookFile::query()
            ->with('book')
            ->whereKey((int) $track)
            ->where('type', BookFileType::Audio->value)
            ->where('is_active', true)
            ->whereHas('book', fn (Builder $books): Builder => $books
                ->where('status', BookStatus::Published->value))
            ->first() ?? throw new AudiobookNotFound;
    }

    /**
     * @param  resource  $stream
     */
    private function streamRange(mixed $stream, int $start, int $length): void
    {
        try {
            $this->moveToOffset($stream, $start);
            $remaining = $length;

            while ($remaining > 0) {
                $chunk = fread($stream, min(self::CHUNK_SIZE, $remaining));

                if ($chunk === false || $chunk === '') {
                    throw new RuntimeException('The media stream ended before the expected byte count.');
                }

                echo $chunk;
                $remaining -= strlen($chunk);
            }
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param  resource  $stream
     */
    private function moveToOffset(mixed $stream, int $offset): void
    {
        if ($offset === 0) {
            return;
        }

        $metadata = stream_get_meta_data($stream);

        if ($metadata['seekable'] === true) {
            if (fseek($stream, $offset) !== 0) {
                throw new RuntimeException('The media stream could not seek to the requested byte.');
            }

            return;
        }

        $remaining = $offset;

        while ($remaining > 0) {
            $chunk = fread($stream, min(self::CHUNK_SIZE, $remaining));

            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('The media stream ended before the requested byte.');
            }

            $remaining -= strlen($chunk);
        }
    }
}
