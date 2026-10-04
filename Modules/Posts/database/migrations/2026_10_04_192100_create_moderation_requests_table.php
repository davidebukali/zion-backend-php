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
        Schema::create('moderation_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('post_id')
                ->constrained('posts')
                ->cascadeOnDelete();

            $table->foreignUlid('report_id')
                ->nullable()
                ->constrained('post_reports')
                ->nullOnDelete();

            $table->string('content_hash', 64);
            $table->string('status')->default('pending')->index();
            $table->unsignedTinyInteger('attempts')->default(1);
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->index(['post_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moderation_requests');
    }
};
