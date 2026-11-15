<?php

declare(strict_types=1);

namespace Database\Factories;

use App\BookFileType;
use App\Models\Book;
use App\Models\BookFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BookFile>
 */
class BookFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = Str::uuid().'.epub';

        return [
            'book_id' => Book::factory(),
            'type' => BookFileType::Epub,
            'title' => null,
            'storage_disk' => 'media',
            'storage_path' => "books/{$filename}",
            'original_filename' => $filename,
            'mime_type' => 'application/epub+zip',
            'size' => fake()->numberBetween(1_024, 10_000_000),
            'checksum_sha256' => hash('sha256', fake()->uuid()),
            'version' => 1,
            'sort_order' => null,
            'duration_seconds' => null,
            'is_active' => true,
        ];
    }

    public function audio(): static
    {
        return $this->state(function (): array {
            $filename = Str::uuid().'.mp3';

            return [
                'type' => BookFileType::Audio,
                'title' => fake()->sentence(3),
                'storage_path' => "books/{$filename}",
                'original_filename' => $filename,
                'mime_type' => 'audio/mpeg',
                'sort_order' => 1,
                'duration_seconds' => 1_240,
            ];
        });
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
