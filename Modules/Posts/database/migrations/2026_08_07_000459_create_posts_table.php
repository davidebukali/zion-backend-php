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
        Schema::create('posts', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('content')->nullable();

            $table->string('visibility')->default('public');

            $table->unsignedInteger('likes_count')->default(0);

            $table->unsignedInteger('comments_count')->default(0);

            $table->unsignedInteger('shares_count')->default(0);

            $table->unsignedInteger('media_count')->default(0);

            $table->timestamps();

            $table->softDeletes();

            $table->index('created_at');

            $table->index(['user_id','created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
