<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\BookFileType;
use App\Models\Book;
use App\Models\BookFile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

final class BookFileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_audio_factory_creates_cast_metadata_and_book_relationship(): void
    {
        $book = Book::factory()->create();

        $file = BookFile::factory()->audio()->for($book)->create();

        $this->assertSame(BookFileType::Audio, $file->type);
        $this->assertSame(1, $file->sort_order);
        $this->assertSame(1_240, $file->duration_seconds);
        $this->assertTrue($file->is_active);
        $this->assertTrue($file->book->is($book));
        $this->assertTrue($book->files()->whereKey($file)->exists());
    }
}
