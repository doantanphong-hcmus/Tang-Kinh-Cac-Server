<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        $reader = User::query()->updateOrCreate(
            ['email' => 'reader@example.test'],
            [
                'full_name' => 'Development Reader',
                'username' => 'reader',
                'username_normalized' => 'reader',
                'birth_date' => '1990-01-01',
                'phone_number' => '+84900000001',
                'password' => 'password',
            ],
        );
        $reader->email_verified_at = '2026-01-01 00:00:00';
        $reader->role = UserRole::Reader;
        $reader->save();

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.test'],
            [
                'full_name' => 'Development Administrator',
                'username' => 'admin',
                'username_normalized' => 'admin',
                'birth_date' => '1990-01-01',
                'phone_number' => '+84900000002',
                'password' => 'password',
            ],
        );
        $admin->email_verified_at = '2026-01-01 00:00:00';
        $admin->role = UserRole::Admin;
        $admin->save();
    }
}
