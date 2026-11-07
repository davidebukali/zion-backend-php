<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('
                CREATE INDEX IF NOT EXISTS idx_posts_weighted_trending_score 
                ON posts ((((likes_count * 1) + (comments_count * 2) + (reposts_count * 3))) DESC, created_at DESC)
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_posts_weighted_trending_score');
        }
    }
};

