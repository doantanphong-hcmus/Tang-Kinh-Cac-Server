<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Book;
use App\Models\BookAlternativeTitle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookAlternativeTitle>
 */
class BookAlternativeTitleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'title' => fake()->sentence(3),
        ];
    }
}
