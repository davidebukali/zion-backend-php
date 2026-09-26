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
        Schema::create('follows', function (Blueprint $table) {
            $table->id();

            $table->foreignUlid('follower_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignUlid('following_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('status')->default('accepted');

            $table->timestamps();

            $table->unique([
                'follower_id',
                'following_id',
            ]);

            $table->index([
                'following_id',
                'created_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
