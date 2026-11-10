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
        Schema::create('user_reports', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('reporter_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignUlid('reported_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('reason');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');

            $table->timestamps();

            $table->index(['reported_user_id', 'status']);
            $table->index(['reporter_id', 'reported_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_reports');
    }
};

