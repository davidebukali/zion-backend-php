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
        if (! Schema::hasTable('reposts')) {
            Schema::create('reposts', function (Blueprint $table) {
                $table->ulid('id')->primary();

                $table->foreignUlid('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->foreignUlid('post_id')
                    ->constrained('posts')
                    ->cascadeOnDelete();

                $table->timestamps();

                $table->unique(['user_id', 'post_id']);
                $table->index(['user_id', 'created_at']);
                $table->index(['post_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reposts');
    }
};

