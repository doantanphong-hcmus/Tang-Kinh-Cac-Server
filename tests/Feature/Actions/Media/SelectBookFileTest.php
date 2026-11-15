<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Media;

use App\Actions\Media\SelectBookFile;
use App\BookFileType;
use App\Models\Book;
use App\Models\BookFile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

final class SelectBookFileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_selects_highest_active_version_for_the_requested_book_and_type(): void
    {
        $book = Book::factory()->create();
        BookFile::factory()->for($book)->create(['version' => 1]);
        $selectedFile = BookFile::factory()->for($book)->create(['version' => 2]);
        BookFile::factory()->inactive()->for($book)->create(['version' => 3]);
        BookFile::factory()->audio()->for($book)->create(['version' => 4]);
        BookFile::factory()->create(['version' => 5]);

        $result = app(SelectBookFile::class)->handle($book, BookFileType::Epub);

        $this->assertTrue($result?->is($selectedFile));
    }

    public function test_returns_null_when_no_active_file_matches(): void
    {
        $book = Book::factory()->create();
        BookFile::factory()->inactive()->for($book)->create();

        $result = app(SelectBookFile::class)->handle($book, BookFileType::Epub);

        $this->assertNull($result);
    }
}
