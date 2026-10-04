<?php

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
        Schema::create('profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('username')->nullable()->unique();
            $table->string('display_name')->nullable();
            $table->text('bio')->nullable();

            $table->foreignUlid('avatar_media_id')
                ->nullable()
                ->constrained('media')
                ->nullOnDelete();

            $table->foreignUlid('cover_media_id')
                ->nullable()
                ->constrained('media')
                ->nullOnDelete();

            $table->string('website')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
