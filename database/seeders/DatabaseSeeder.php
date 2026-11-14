<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'full_name' => 'Test User',
            'username' => 'test_user',
            'username_normalized' => 'test_user',
            'birth_date' => '1990-01-01',
            'phone_number' => '+84900000000',
            'email' => 'test@example.com',
        ]);
    }
}
