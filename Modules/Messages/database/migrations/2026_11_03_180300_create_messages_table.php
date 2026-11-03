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
        Schema::create('messages', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('conversation_id')
                ->constrained('conversations')
                ->cascadeOnDelete();

            $table->foreignUlid('sender_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('body')->nullable();

            $table->ulid('reply_to_message_id')->nullable();

            $table->timestamp('edited_at')->nullable();
            $table->timestamp('deleted_for_everyone_at')->nullable();

            $table->timestamps();

            $table->index(['conversation_id', 'id']);
            $table->index(['conversation_id', 'created_at']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreign('reply_to_message_id')
                ->references('id')
                ->on('messages')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
