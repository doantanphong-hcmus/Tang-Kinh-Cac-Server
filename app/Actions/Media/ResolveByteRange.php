<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Exceptions\RangeNotSatisfiable;
use InvalidArgumentException;

final class ResolveByteRange
{
    /**
     * @return array{start: int, end: int, length: int, partial: bool}
     */
    public function handle(?string $header, int $fileSize): array
    {
        if ($fileSize < 1) {
            throw new InvalidArgumentException('The file size must be positive.');
        }

        if ($header === null) {
            return $this->range(0, $fileSize - 1, partial: false);
        }

        if (preg_match('/^bytes=(\d*)-(\d*)$/', trim($header), $matches) !== 1) {
            throw new RangeNotSatisfiable($fileSize);
        }

        [$startPart, $endPart] = [$matches[1], $matches[2]];

        if ($startPart === '' && $endPart === '') {
            throw new RangeNotSatisfiable($fileSize);
        }

        if ($startPart === '') {
            $suffixLength = (int) $endPart;

            if ($suffixLength < 1) {
                throw new RangeNotSatisfiable($fileSize);
            }

            $length = min($suffixLength, $fileSize);

            return $this->range($fileSize - $length, $fileSize - 1, partial: true);
        }

        $start = (int) $startPart;

        if ($start >= $fileSize) {
            throw new RangeNotSatisfiable($fileSize);
        }

        $end = $endPart === '' ? $fileSize - 1 : min((int) $endPart, $fileSize - 1);

        if ($end < $start) {
            throw new RangeNotSatisfiable($fileSize);
        }

        return $this->range($start, $end, partial: true);
    }

    /**
     * @return array{start: int, end: int, length: int, partial: bool}
     */
    private function range(int $start, int $end, bool $partial): array
    {
        // HTTP range ends are inclusive, while stream reads use a byte count.
        return [
            'start' => $start,
            'end' => $end,
            'length' => $end - $start + 1,
            'partial' => $partial,
        ];
    }
}
