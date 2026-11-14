<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_factory_creates_a_reader_with_complete_identity_fields(): void
    {
        $user = User::factory()->create([
            'full_name' => 'Nguyen Van An',
            'username' => 'Reader.One',
            'username_normalized' => 'reader.one',
            'birth_date' => '1990-06-15',
            'phone_number' => '+84901234567',
            'email' => 'reader@example.com',
            'password' => 'correct-horse-battery-staple',
        ]);

        $this->assertSame('Nguyen Van An', $user->full_name);
        $this->assertSame('reader.one', $user->username_normalized);
        $this->assertSame('1990-06-15', $user->birth_date->toDateString());
        $this->assertSame(UserRole::Reader, $user->role);
        $this->assertTrue(Hash::check('correct-horse-battery-staple', $user->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_admin_factory_state_creates_an_administrator(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertSame(UserRole::Admin, $user->role);
    }

    public function test_database_rejects_duplicate_normalized_usernames(): void
    {
        User::factory()->create([
            'username' => 'Reader.One',
            'username_normalized' => 'reader.one',
        ]);

        $this->expectException(QueryException::class);

        User::factory()->create([
            'username' => 'reader_one',
            'username_normalized' => 'reader.one',
        ]);
    }

    public function test_database_requires_normalized_identity_fields(): void
    {
        $attributes = User::factory()->raw();
        unset($attributes['username_normalized']);

        $this->expectException(QueryException::class);

        DB::table('users')->insert($attributes);
    }

    public function test_database_rejects_unknown_roles(): void
    {
        $attributes = User::factory()->raw([
            'role' => 'moderator',
        ]);

        $this->expectException(QueryException::class);

        DB::table('users')->insert($attributes);
    }
}
