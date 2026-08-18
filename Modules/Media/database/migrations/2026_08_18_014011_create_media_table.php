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
        Schema::create('media', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('mediable_type')->nullable();
            $table->ulid('mediable_id')->nullable();

            $table->string('disk')->default('r2');
            $table->string('path')->unique();

            $table->string('type');
            $table->string('mime_type');

            $table->unsignedBigInteger('size');

            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration')->nullable();

            $table->string('checksum')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->string('status')->default('pending');

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);

            $table->index([
                'mediable_type',
                'mediable_id',
            ]);

            $table->unique([
                'mediable_type',
                'mediable_id',
                'sort_order',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
