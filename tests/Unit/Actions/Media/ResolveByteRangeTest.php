<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Media;

use App\Actions\Media\ResolveByteRange;
use App\Exceptions\RangeNotSatisfiable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ResolveByteRangeTest extends TestCase
{
    /**
     * @param  array{start: int, end: int, length: int, partial: bool}  $expected
     */
    #[DataProvider('validRanges')]
    public function test_resolves_full_and_single_byte_ranges(?string $header, array $expected): void
    {
        $range = (new ResolveByteRange)->handle($header, 10);

        $this->assertSame($expected, $range);
    }

    #[DataProvider('invalidRanges')]
    public function test_rejects_unsatisfiable_or_malformed_ranges(string $header): void
    {
        $this->expectException(RangeNotSatisfiable::class);

        (new ResolveByteRange)->handle($header, 10);
    }

    /**
     * @return array<string, array{string|null, array{start: int, end: int, length: int, partial: bool}}>
     */
    public static function validRanges(): array
    {
        return [
            'full response without header' => [null, ['start' => 0, 'end' => 9, 'length' => 10, 'partial' => false]],
            'bounded range' => ['bytes=2-5', ['start' => 2, 'end' => 5, 'length' => 4, 'partial' => true]],
            'open ended range' => ['bytes=7-', ['start' => 7, 'end' => 9, 'length' => 3, 'partial' => true]],
            'suffix range' => ['bytes=-4', ['start' => 6, 'end' => 9, 'length' => 4, 'partial' => true]],
            'oversized suffix becomes full range' => ['bytes=-20', ['start' => 0, 'end' => 9, 'length' => 10, 'partial' => true]],
            'end is clamped to file boundary' => ['bytes=8-20', ['start' => 8, 'end' => 9, 'length' => 2, 'partial' => true]],
            'single boundary byte' => ['bytes=9-9', ['start' => 9, 'end' => 9, 'length' => 1, 'partial' => true]],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidRanges(): array
    {
        return [
            'unsupported unit' => ['items=0-1'],
            'multiple ranges' => ['bytes=0-1,4-5'],
            'missing bounds' => ['bytes=-'],
            'zero suffix' => ['bytes=-0'],
            'start outside file' => ['bytes=10-'],
            'end before start' => ['bytes=5-4'],
            'non numeric bound' => ['bytes=a-b'],
        ];
    }
}
