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
        Schema::table('feed_items', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'post_id']);

            if (! Schema::hasColumn('feed_items', 'type')) {
                $table->string('type')->default('post')->after('post_id');
            }

            if (! Schema::hasColumn('feed_items', 'actor_id')) {
                $table->foreignUlid('actor_id')
                    ->nullable()
                    ->after('type')
                    ->constrained('users')
                    ->cascadeOnDelete();
            }

            $table->unique(['user_id', 'post_id', 'type', 'actor_id'], 'feed_items_user_post_type_actor_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feed_items', function (Blueprint $table) {
            $table->dropUnique('feed_items_user_post_type_actor_unique');

            if (Schema::hasColumn('feed_items', 'actor_id')) {
                $table->dropConstrainedForeignId('actor_id');
            }

            if (Schema::hasColumn('feed_items', 'type')) {
                $table->dropColumn('type');
            }

            $table->unique(['user_id', 'post_id']);
        });
    }
};

