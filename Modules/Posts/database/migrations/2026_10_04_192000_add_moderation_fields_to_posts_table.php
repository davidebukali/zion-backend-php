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
        Schema::table('posts', function (Blueprint $table) {
            $table->string('moderation_status')->default('approved')->index();
            $table->timestamp('moderated_at')->nullable();
            $table->string('moderated_by_type')->default('system');
            $table->boolean('is_flagged_manually')->default(false);
            $table->json('moderation_metadata')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['moderation_status']);
            $table->dropColumn([
                'moderation_status',
                'moderated_at',
                'moderated_by_type',
                'is_flagged_manually',
                'moderation_metadata',
            ]);
        });
    }
};
