<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Genre;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_factory_states_create_boolean_activity_values(): void
    {
        $activeGenre = Genre::factory()->create();
        $inactiveGenre = Genre::factory()->inactive()->create();

        $this->assertTrue($activeGenre->is_active);
        $this->assertFalse($inactiveGenre->is_active);
    }

    public function test_active_scope_excludes_inactive_genres(): void
    {
        $activeGenre = Genre::factory()->create();
        Genre::factory()->inactive()->create();

        $activeGenreIds = Genre::active()->pluck('id')->all();

        $this->assertSame([$activeGenre->id], $activeGenreIds);
    }
}
