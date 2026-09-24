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
        Schema::create('comment_media', function (Blueprint $table) {
            $table->id();

            $table->foreignUlid('comment_id')
                ->constrained('comments')
                ->cascadeOnDelete();

            $table->foreignUlid('media_id')
                ->constrained('media')
                ->cascadeOnDelete();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['comment_id', 'media_id']);

            $table->unique(['comment_id', 'sort_order']);

            $table->unique('media_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_media');
    }
};
