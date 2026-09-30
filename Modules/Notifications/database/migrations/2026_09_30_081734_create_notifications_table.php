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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // recipient
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // 'post_liked', 'post_commented', 'user_followed', 'comment_replied'
            $table->uuidMorphs('notifiable'); // post_id / comment_id + type
            $table->jsonb('data'); // ->jsonb for Postgres, use ->json() portability if you prefer
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']); // "unread for user" is your hottest query
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
