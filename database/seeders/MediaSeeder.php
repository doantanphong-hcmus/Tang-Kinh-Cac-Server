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
        $sourcePath = database_path('seeders/media/tang-kinh-cac-sample.epub');
        $storagePath = 'epubs/tang-kinh-cac-sample.epub';
        $contents = File::get($sourcePath);

        if (! Storage::disk('media')->put($storagePath, $contents)) {
            throw new RuntimeException('The sample EPUB could not be stored.');
        }

        $book = Book::query()->where('slug', 'truyen-kieu')->sole();

        BookFile::query()->updateOrCreate(
            ['storage_path' => $storagePath],
            [
                'book_id' => $book->id,
                'type' => BookFileType::Epub,
                'title' => null,
                'storage_disk' => 'media',
                'original_filename' => 'tang-kinh-cac-sample.epub',
                'mime_type' => 'application/epub+zip',
                'size' => strlen($contents),
                'checksum_sha256' => hash('sha256', $contents),
                'version' => 1,
                'sort_order' => null,
                'duration_seconds' => null,
                'is_active' => true,
            ],
        );
    }
}
