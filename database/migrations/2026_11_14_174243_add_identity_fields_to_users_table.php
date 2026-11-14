<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('name', 'full_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name', 100)->change();
            $table->string('username', 20);
            $table->string('username_normalized', 20)->unique();
            $table->date('birth_date');
            $table->string('phone_number', 16);
            $table->enum('role', ['reader', 'admin'])->default('reader');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'username_normalized',
                'birth_date',
                'phone_number',
                'role',
            ]);
            $table->string('full_name')->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('full_name', 'name');
        });
    }
};
