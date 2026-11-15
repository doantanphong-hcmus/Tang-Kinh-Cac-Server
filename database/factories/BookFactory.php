<?php

declare(strict_types=1);

namespace Database\Factories;

use App\BookAccessLevel;
use App\BookStatus;
use App\Models\Author;
use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id' => Author::factory(),
            'slug' => fake()->unique()->slug(3),
            'title' => fake()->sentence(3),
            'original_title' => null,
            'country' => fake()->country(),
            'original_language' => fake()->optional()->languageCode(),
            'publication_period' => null,
            'structure' => null,
            'historical_setting' => null,
            'synopsis' => fake()->paragraphs(2, true),
            'access_level' => BookAccessLevel::Free,
            'status' => BookStatus::Draft,
            'average_rating' => '0.00',
            'review_count' => 0,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BookStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BookStatus::Archived,
        ]);
    }

    public function vip(): static
    {
        return $this->state(fn (array $attributes): array => [
            'access_level' => BookAccessLevel::Vip,
        ]);
    }
}
