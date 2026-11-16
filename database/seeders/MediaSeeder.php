<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\BookFileType;
use App\Models\Book;
use App\Models\BookFile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class MediaSeeder extends Seeder
{
    public function run(): void
    {
        $book = Book::query()->where('slug', 'truyen-kieu')->sole();
        $sourcePath = database_path('seeders/media/tang-kinh-cac-sample.epub');
        $storagePath = 'epubs/tang-kinh-cac-sample.epub';
        $contents = File::get($sourcePath);

        $this->storeBookFile($book, $storagePath, $contents, [
            'type' => BookFileType::Epub,
            'title' => null,
            'original_filename' => 'tang-kinh-cac-sample.epub',
            'mime_type' => 'application/epub+zip',
            'sort_order' => null,
            'duration_seconds' => null,
        ]);

        foreach ([
            ['Sample Track 1', 1, chr(128)],
            ['Sample Track 2', 2, chr(96).chr(160)],
        ] as [$title, $sortOrder, $sample]) {
            $audioContents = $this->wavContents($sample);
            $filename = "tang-kinh-cac-sample-track-{$sortOrder}.wav";

            $this->storeBookFile($book, "audio/{$filename}", $audioContents, [
                'type' => BookFileType::Audio,
                'title' => $title,
                'original_filename' => $filename,
                'mime_type' => 'audio/wav',
                'sort_order' => $sortOrder,
                'duration_seconds' => 1,
            ]);
        }
    }

    /**
     * @param  array{type: BookFileType, title: ?string, original_filename: string, mime_type: string, sort_order: ?int, duration_seconds: ?int}  $metadata
     */
    private function storeBookFile(Book $book, string $storagePath, string $contents, array $metadata): void
    {
        if (! Storage::disk('media')->put($storagePath, $contents)) {
            throw new RuntimeException("The sample media file [{$storagePath}] could not be stored.");
        }

        BookFile::query()->updateOrCreate(
            ['storage_path' => $storagePath],
            [
                'book_id' => $book->id,
                ...$metadata,
                'storage_disk' => 'media',
                'size' => strlen($contents),
                'checksum_sha256' => hash('sha256', $contents),
                'version' => 1,
                'is_active' => true,
            ],
        );
    }

    private function wavContents(string $sample): string
    {
        $sampleRate = 8_000;
        $audio = str_repeat($sample, intdiv($sampleRate, strlen($sample)));
        $dataSize = strlen($audio);

        return 'RIFF'
            .pack('V', 36 + $dataSize)
            .'WAVEfmt '
            .pack('VvvVVvv', 16, 1, 1, $sampleRate, $sampleRate, 1, 8)
            .'data'
            .pack('V', $dataSize)
            .$audio;
    }
}
