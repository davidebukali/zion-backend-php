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
        Schema::create('message_media', function (Blueprint $table) {
            $table->id();

            $table->foreignUlid('message_id')
                ->constrained('messages')
                ->cascadeOnDelete();

            $table->foreignUlid('media_id')
                ->constrained('media')
                ->cascadeOnDelete();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['message_id', 'media_id']);
            $table->unique(['message_id', 'sort_order']);
            $table->unique('media_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_media');
    }
};
