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
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('original_name')->nullable();
            $table->text('biography')->nullable();
            $table->timestamps();
        });

        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained()->restrictOnDelete();
            $table->string('slug')->unique();
            $table->string('title')->index();
            $table->string('original_title')->nullable();
            $table->string('country')->index();
            $table->string('original_language')->nullable();
            $table->string('publication_period')->nullable();
            $table->string('structure')->nullable();
            $table->text('historical_setting')->nullable();
            $table->text('synopsis');
            $table->enum('access_level', ['free', 'vip'])->index();
            $table->enum('status', ['draft', 'published', 'archived'])->index();
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();

            $table->index('author_id');
        });

        Schema::create('book_genre', function (Blueprint $table) {
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('genre_id')->constrained()->cascadeOnDelete();

            $table->unique(['book_id', 'genre_id']);
            $table->index('genre_id');
        });

        Schema::create('book_alternative_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->string('title')->index();

            $table->unique(['book_id', 'title']);
        });

        Schema::create('book_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->string('value')->index();

            $table->unique(['book_id', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_keywords');
        Schema::dropIfExists('book_alternative_titles');
        Schema::dropIfExists('book_genre');
        Schema::dropIfExists('books');
        Schema::dropIfExists('genres');
        Schema::dropIfExists('authors');
    }
};
