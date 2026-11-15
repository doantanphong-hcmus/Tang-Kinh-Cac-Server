<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('book_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['cover', 'epub', 'audio']);
            $table->string('title')->nullable();
            $table->string('storage_disk');
            $table->string('storage_path')->unique();
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('checksum_sha256', 64);
            $table->unsignedInteger('version')->default(1);
            $table->unsignedInteger('sort_order')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['book_id', 'type', 'is_active', 'sort_order']);
            $table->index('type');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE book_files
            ADD CONSTRAINT book_files_metadata_check CHECK (
                size > 0
                AND version > 0
                AND (sort_order IS NULL OR sort_order >= 0)
                AND (duration_seconds IS NULL OR duration_seconds >= 0)
                AND checksum_sha256 ~ '^[0-9a-f]{64}$'
                AND storage_path = BTRIM(storage_path)
                AND storage_path <> ''
                AND storage_path !~ '(^/|(^|/)\.\.(/|$))'
                AND (
                    (type = 'cover' AND mime_type LIKE 'image/%')
                    OR (type = 'epub' AND mime_type = 'application/epub+zip')
                    OR (type = 'audio' AND mime_type LIKE 'audio/%')
                )
                AND (
                    type <> 'audio'
                    OR (
                        title IS NOT NULL
                        AND BTRIM(title) <> ''
                        AND sort_order IS NOT NULL
                        AND duration_seconds IS NOT NULL
                    )
                )
            )
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_files');
    }
};
