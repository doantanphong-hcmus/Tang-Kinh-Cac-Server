<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\BookAccessLevel;
use App\BookStatus;
use App\Models\Book;
use App\Models\BookFile;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DatabaseSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_seeds_development_accounts_and_catalog(): void
    {
        Storage::fake('media');

        $this->seed();

        $reader = User::query()->where('email', 'reader@example.test')->sole();
        $admin = User::query()->where('email', 'admin@example.test')->sole();

        $this->assertSame(UserRole::Reader, $reader->role);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue(Hash::check('password', $reader->password));
        $this->assertTrue(Hash::check('password', $admin->password));

        $catalog = Book::query()
            ->with('author')
            ->orderBy('slug')
            ->get()
            ->mapWithKeys(fn (Book $book): array => [
                $book->slug => [
                    'title' => $book->title,
                    'author' => $book->author->name,
                    'access_level' => $book->access_level,
                ],
            ])
            ->all();

        $this->assertSame([
            'chinh-phu-ngam' => [
                'title' => 'Chinh phụ ngâm',
                'author' => 'Đặng Trần Côn',
                'access_level' => BookAccessLevel::Free,
            ],
            'cung-oan-ngam-khuc' => [
                'title' => 'Cung oán ngâm khúc',
                'author' => 'Nguyễn Gia Thiều',
                'access_level' => BookAccessLevel::Free,
            ],
            'dai-viet-su-ky-toan-thu' => [
                'title' => 'Đại Việt sử ký toàn thư',
                'author' => 'Ngô Sĩ Liên',
                'access_level' => BookAccessLevel::Vip,
            ],
            'hoang-le-nhat-thong-chi' => [
                'title' => 'Hoàng Lê nhất thống chí',
                'author' => 'Ngô gia văn phái',
                'access_level' => BookAccessLevel::Free,
            ],
            'luc-van-tien' => [
                'title' => 'Lục Vân Tiên',
                'author' => 'Nguyễn Đình Chiểu',
                'access_level' => BookAccessLevel::Free,
            ],
            'quoc-am-thi-tap' => [
                'title' => 'Quốc âm thi tập',
                'author' => 'Nguyễn Trãi',
                'access_level' => BookAccessLevel::Free,
            ],
            'truyen-kieu' => [
                'title' => 'Truyện Kiều',
                'author' => 'Nguyễn Du',
                'access_level' => BookAccessLevel::Free,
            ],
            'vu-trung-tuy-but' => [
                'title' => 'Vũ trung tùy bút',
                'author' => 'Phạm Đình Hổ',
                'access_level' => BookAccessLevel::Free,
            ],
        ], $catalog);
        $this->assertSame(8, Book::query()->where('status', BookStatus::Published)->count());
        $this->assertSame(1, Book::query()->where('access_level', BookAccessLevel::Vip)->count());

        $books = Book::query()->with(['alternativeTitles', 'genres', 'keywords'])->get();
        $this->assertTrue($books->every(fn (Book $book): bool => $book->alternativeTitles->isNotEmpty()));
        $this->assertTrue($books->every(fn (Book $book): bool => $book->genres->isNotEmpty()));
        $this->assertTrue($books->every(fn (Book $book): bool => $book->keywords->isNotEmpty()));
        $this->assertSame(1, Book::search('TRUYEN KIEU')->count());

        $epub = BookFile::query()->where('type', 'epub')->sole();
        $sample = File::get(database_path('seeders/media/tang-kinh-cac-sample.epub'));
        $this->assertSame(strlen($sample), $epub->size);
        $this->assertSame(hash('sha256', $sample), $epub->checksum_sha256);
        $this->assertTrue($epub->is_active);
        Storage::disk('media')->assertExists($epub->storage_path);

        $audioTracks = BookFile::query()
            ->where('type', 'audio')
            ->orderBy('sort_order')
            ->get();
        $this->assertSame(['Sample Track 1', 'Sample Track 2'], $audioTracks->pluck('title')->all());
        $this->assertSame([1, 2], $audioTracks->pluck('sort_order')->all());

        foreach ($audioTracks as $audioTrack) {
            $audio = Storage::disk('media')->get($audioTrack->storage_path);
            $this->assertStringStartsWith('RIFF', $audio);
            $this->assertSame('WAVE', substr($audio, 8, 4));
            $this->assertSame(strlen($audio), $audioTrack->size);
            $this->assertSame(hash('sha256', $audio), $audioTrack->checksum_sha256);
        }
    }

    public function test_it_can_run_more_than_once_without_duplicate_records(): void
    {
        Storage::fake('media');

        $this->seed();
        $firstCounts = $this->seededRecordCounts();

        $this->seed();

        $this->assertSame($firstCounts, $this->seededRecordCounts());
    }

    /**
     * @return array<string, int>
     */
    private function seededRecordCounts(): array
    {
        return [
            'users' => DB::table('users')->count(),
            'authors' => DB::table('authors')->count(),
            'genres' => DB::table('genres')->count(),
            'books' => DB::table('books')->count(),
            'book_genre' => DB::table('book_genre')->count(),
            'book_alternative_titles' => DB::table('book_alternative_titles')->count(),
            'book_keywords' => DB::table('book_keywords')->count(),
            'book_files' => DB::table('book_files')->count(),
        ];
    }
}
