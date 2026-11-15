<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\BookFileType;
use App\Models\Book;
use App\Models\BookFile;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class BookFileMigrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_schema_stores_epub_metadata_with_defaults(): void
    {
        $file = BookFile::factory()->create();

        $this->assertSame(BookFileType::Epub, $file->type);
        $this->assertSame(1, $file->version);
        $this->assertTrue($file->is_active);
        $this->assertNull($file->sort_order);
        $this->assertNull($file->duration_seconds);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    #[DataProvider('invalidMetadata')]
    public function test_database_rejects_invalid_file_metadata(array $attributes): void
    {
        $this->expectException(QueryException::class);

        BookFile::factory()->create($attributes);
    }

    public function test_database_rejects_duplicate_private_storage_paths(): void
    {
        $file = BookFile::factory()->create();

        $this->expectException(QueryException::class);

        BookFile::factory()->create([
            'storage_path' => $file->storage_path,
        ]);
    }

    public function test_database_rejects_unknown_file_types(): void
    {
        $attributes = BookFile::factory()->raw([
            'book_id' => Book::factory()->create()->id,
            'type' => 'pdf',
        ]);

        $this->expectException(QueryException::class);

        DB::table('book_files')->insert($attributes);
    }

    public function test_database_restricts_deleting_a_book_with_attached_files(): void
    {
        $file = BookFile::factory()->create();

        $this->expectException(QueryException::class);

        Book::query()->whereKey($file->book_id)->delete();
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidMetadata(): array
    {
        return [
            'wrong EPUB MIME type' => [['mime_type' => 'application/pdf']],
            'malformed checksum' => [['checksum_sha256' => str_repeat('A', 64)]],
            'empty file' => [['size' => 0]],
            'zero version' => [['version' => 0]],
            'negative audio order' => [[
                'type' => BookFileType::Audio,
                'mime_type' => 'audio/mpeg',
                'title' => 'Chapter 1',
                'sort_order' => -1,
                'duration_seconds' => 60,
            ]],
            'negative audio duration' => [[
                'type' => BookFileType::Audio,
                'mime_type' => 'audio/mpeg',
                'title' => 'Chapter 1',
                'sort_order' => 1,
                'duration_seconds' => -1,
            ]],
            'absolute storage path' => [['storage_path' => '/books/sample.epub']],
            'traversing storage path' => [['storage_path' => 'books/../sample.epub']],
            'audio without title' => [[
                'type' => BookFileType::Audio,
                'mime_type' => 'audio/mpeg',
                'title' => null,
                'sort_order' => 1,
                'duration_seconds' => 60,
            ]],
            'audio without order' => [[
                'type' => BookFileType::Audio,
                'mime_type' => 'audio/mpeg',
                'title' => 'Chapter 1',
                'sort_order' => null,
                'duration_seconds' => 60,
            ]],
            'audio without duration' => [[
                'type' => BookFileType::Audio,
                'mime_type' => 'audio/mpeg',
                'title' => 'Chapter 1',
                'sort_order' => 1,
                'duration_seconds' => null,
            ]],
        ];
    }
}
