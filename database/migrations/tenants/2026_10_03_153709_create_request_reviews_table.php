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
        Schema::create('request_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('purchase_requests')->restrictOnDelete();
            $table->foreignId('parent_id')->constrained('users')->restrictOnDelete();
            $table->string('decision', 10);
            $table->text('feedback')->nullable();
            $table->dateTime('reviewed_at');

            $table->unique(['request_id', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_reviews');
    }
};
