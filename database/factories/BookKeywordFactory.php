<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Book;
use App\Models\BookKeyword;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookKeyword>
 */
class BookKeywordFactory extends Factory
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
            'value' => fake()->word(),
        ];
    }
}
