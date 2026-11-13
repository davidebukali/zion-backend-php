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
        Schema::create('message_reports', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('reporter_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignUlid('conversation_id')
                ->constrained('conversations')
                ->cascadeOnDelete();

            $table->foreignUlid('message_id')
                ->nullable()
                ->constrained('messages')
                ->nullOnDelete();

            $table->string('reason');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_reports');
    }
};
