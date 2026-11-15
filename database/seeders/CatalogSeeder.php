<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\BookAccessLevel;
use App\BookStatus;
use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class CatalogSeeder extends Seeder
{
    private const AUTHORS = [
        'Nguyễn Du' => ['biography' => 'Vietnamese poet best known for Truyện Kiều.'],
        'Nguyễn Đình Chiểu' => ['biography' => 'Vietnamese poet and teacher from the nineteenth century.'],
        'Đặng Trần Côn' => ['biography' => 'Vietnamese author associated with Chinh phụ ngâm.'],
        'Nguyễn Gia Thiều' => ['biography' => 'Vietnamese poet and mandarin of the Lê dynasty.'],
        'Nguyễn Trãi' => ['biography' => 'Vietnamese scholar, poet, and statesman of the fifteenth century.'],
        'Phạm Đình Hổ' => ['biography' => 'Vietnamese scholar and author of historical and cultural prose.'],
        'Ngô gia văn phái' => ['biography' => 'A collective of writers from the Ngô Thì family.'],
        'Ngô Sĩ Liên' => ['biography' => 'Vietnamese historian of the Lê dynasty.'],
    ];

    private const GENRES = [
        'lich-su' => 'Lịch sử',
        'tho' => 'Thơ',
        'truyen-tho' => 'Truyện thơ',
        'tuy-but' => 'Tùy bút',
        'van-hoc-co-dien' => 'Văn học cổ điển',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::AUTHORS as $name => $attributes) {
                Author::query()->updateOrCreate(
                    ['name' => $name],
                    ['biography' => $attributes['biography']],
                );
            }

            foreach (self::GENRES as $slug => $name) {
                Genre::query()->updateOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'is_active' => true],
                );
            }

            foreach ($this->books() as $data) {
                $author = Author::query()->where('name', $data['author'])->sole();
                $book = Book::query()->updateOrCreate(
                    ['slug' => $data['slug']],
                    [
                        'author_id' => $author->id,
                        'title' => $data['title'],
                        'original_title' => $data['original_title'],
                        'country' => 'Vietnam',
                        'original_language' => $data['original_language'],
                        'publication_period' => $data['publication_period'],
                        'structure' => $data['structure'],
                        'historical_setting' => $data['historical_setting'],
                        'synopsis' => $data['synopsis'],
                        'access_level' => $data['access_level'],
                        'status' => BookStatus::Published,
                        'published_at' => '2026-01-01 00:00:00',
                    ],
                );

                $genreIds = Genre::query()->whereIn('slug', $data['genres'])->pluck('id');
                $book->genres()->sync($genreIds);

                $book->alternativeTitles()->whereNotIn('title', $data['alternative_titles'])->delete();
                foreach ($data['alternative_titles'] as $title) {
                    $book->alternativeTitles()->updateOrCreate(['title' => $title]);
                }

                $book->keywords()->whereNotIn('value', $data['keywords'])->delete();
                foreach ($data['keywords'] as $keyword) {
                    $book->keywords()->updateOrCreate(['value' => $keyword]);
                }
            }
        });
    }

    /**
     * @return array<int, array{
     *     author: string,
     *     slug: string,
     *     title: string,
     *     original_title: string|null,
     *     original_language: string,
     *     publication_period: string,
     *     structure: string,
     *     historical_setting: string,
     *     synopsis: string,
     *     access_level: BookAccessLevel,
     *     genres: list<string>,
     *     alternative_titles: list<string>,
     *     keywords: list<string>
     * }>
     */
    private function books(): array
    {
        return [
            [
                'author' => 'Nguyễn Du',
                'slug' => 'truyen-kieu',
                'title' => 'Truyện Kiều',
                'original_title' => 'Đoạn trường tân thanh',
                'original_language' => 'Vietnamese',
                'publication_period' => 'Early 19th century',
                'structure' => '3,254 lines of lục bát verse',
                'historical_setting' => 'A fictionalized society shaped by Confucian institutions and family duty.',
                'synopsis' => 'A gifted young woman endures separation and hardship while trying to protect her family.',
                'access_level' => BookAccessLevel::Free,
                'genres' => ['truyen-tho', 'van-hoc-co-dien'],
                'alternative_titles' => ['Kim Vân Kiều truyện'],
                'keywords' => ['đạo hiếu', 'thân phận', 'tình yêu'],
            ],
            [
                'author' => 'Nguyễn Đình Chiểu',
                'slug' => 'luc-van-tien',
                'title' => 'Lục Vân Tiên',
                'original_title' => null,
                'original_language' => 'Vietnamese',
                'publication_period' => 'Mid-19th century',
                'structure' => 'Lục bát narrative poem',
                'historical_setting' => 'A moral and heroic world inspired by traditional Vietnamese society.',
                'synopsis' => 'A young scholar faces trials that test courage, loyalty, gratitude, and justice.',
                'access_level' => BookAccessLevel::Free,
                'genres' => ['truyen-tho', 'van-hoc-co-dien'],
                'alternative_titles' => ['Truyện Lục Vân Tiên'],
                'keywords' => ['đạo lý', 'nghĩa hiệp', 'trung hiếu'],
            ],
            [
                'author' => 'Đặng Trần Côn',
                'slug' => 'chinh-phu-ngam',
                'title' => 'Chinh phụ ngâm',
                'original_title' => null,
                'original_language' => 'Classical Chinese',
                'publication_period' => '18th century',
                'structure' => 'Lyrical lament',
                'historical_setting' => 'An era of prolonged warfare and military campaigns.',
                'synopsis' => 'A soldier’s wife reflects on separation, uncertainty, and the human cost of war.',
                'access_level' => BookAccessLevel::Free,
                'genres' => ['tho', 'van-hoc-co-dien'],
                'alternative_titles' => ['Khúc ngâm người chinh phụ'],
                'keywords' => ['chiến tranh', 'chia ly', 'người phụ nữ'],
            ],
            [
                'author' => 'Nguyễn Gia Thiều',
                'slug' => 'cung-oan-ngam-khuc',
                'title' => 'Cung oán ngâm khúc',
                'original_title' => null,
                'original_language' => 'Vietnamese',
                'publication_period' => '18th century',
                'structure' => 'Song thất lục bát poem',
                'historical_setting' => 'The royal court and its secluded inner palace.',
                'synopsis' => 'A neglected palace woman voices grief, disillusionment, and questions about fate.',
                'access_level' => BookAccessLevel::Free,
                'genres' => ['tho', 'van-hoc-co-dien'],
                'alternative_titles' => ['Khúc ngâm oán trong cung'],
                'keywords' => ['bi kịch', 'cung đình', 'thân phận'],
            ],
            [
                'author' => 'Nguyễn Trãi',
                'slug' => 'quoc-am-thi-tap',
                'title' => 'Quốc âm thi tập',
                'original_title' => null,
                'original_language' => 'Vietnamese',
                'publication_period' => '15th century',
                'structure' => 'Collection of poems written in chữ Nôm',
                'historical_setting' => 'The early Lê dynasty after national independence was restored.',
                'synopsis' => 'A poetry collection contemplating nature, public duty, ordinary life, and humaneness.',
                'access_level' => BookAccessLevel::Free,
                'genres' => ['tho', 'van-hoc-co-dien'],
                'alternative_titles' => ['Thơ quốc âm Nguyễn Trãi'],
                'keywords' => ['chữ Nôm', 'nhân nghĩa', 'thiên nhiên'],
            ],
            [
                'author' => 'Phạm Đình Hổ',
                'slug' => 'vu-trung-tuy-but',
                'title' => 'Vũ trung tùy bút',
                'original_title' => null,
                'original_language' => 'Classical Chinese',
                'publication_period' => 'Early 19th century',
                'structure' => 'Collection of short essays and observations',
                'historical_setting' => 'Northern Vietnam during the decline of the Lê–Trịnh order.',
                'synopsis' => 'Notes on customs, people, scholarship, and social change observed by the author.',
                'access_level' => BookAccessLevel::Free,
                'genres' => ['tuy-but', 'van-hoc-co-dien'],
                'alternative_titles' => ['Tùy bút viết trong mưa'],
                'keywords' => ['phong tục', 'Thăng Long', 'xã hội'],
            ],
            [
                'author' => 'Ngô gia văn phái',
                'slug' => 'hoang-le-nhat-thong-chi',
                'title' => 'Hoàng Lê nhất thống chí',
                'original_title' => null,
                'original_language' => 'Classical Chinese',
                'publication_period' => 'Late 18th to early 19th century',
                'structure' => 'Historical novel in seventeen chapters',
                'historical_setting' => 'The fall of the Lê–Trịnh order and the rise of the Tây Sơn movement.',
                'synopsis' => 'A chronicle-like narrative of political upheaval and the people who shaped it.',
                'access_level' => BookAccessLevel::Free,
                'genres' => ['lich-su', 'van-hoc-co-dien'],
                'alternative_titles' => ['An Nam nhất thống chí'],
                'keywords' => ['Quang Trung', 'Tây Sơn', 'lịch sử'],
            ],
            [
                'author' => 'Ngô Sĩ Liên',
                'slug' => 'dai-viet-su-ky-toan-thu',
                'title' => 'Đại Việt sử ký toàn thư',
                'original_title' => null,
                'original_language' => 'Classical Chinese',
                'publication_period' => '15th century',
                'structure' => 'Chronological history organized by reign and era',
                'historical_setting' => 'Vietnamese history from legendary origins through successive dynasties.',
                'synopsis' => 'A foundational historical chronicle compiling events, rulers, and political lessons.',
                'access_level' => BookAccessLevel::Vip,
                'genres' => ['lich-su', 'van-hoc-co-dien'],
                'alternative_titles' => ['Toàn thư'],
                'keywords' => ['biên niên', 'sử Việt', 'triều đại'],
            ],
        ];
    }
}
