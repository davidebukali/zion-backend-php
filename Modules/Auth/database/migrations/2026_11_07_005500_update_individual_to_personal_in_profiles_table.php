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
        DB::table('profiles')
            ->where('account_type', 'individual')
            ->update(['account_type' => 'personal']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('profiles')
            ->where('account_type', 'personal')
            ->update(['account_type' => 'individual']);
    }
};
