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
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('account_type')->nullable()->after('bio');
            $table->string('category')->nullable()->after('account_type');
            $table->string('location')->nullable()->after('category');
            $table->foreignUlid('church_id')
                ->nullable()
                ->after('location')
                ->constrained('churches')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropForeign(['church_id']);
            $table->dropColumn(['account_type', 'category', 'location', 'church_id']);
        });
    }
};
