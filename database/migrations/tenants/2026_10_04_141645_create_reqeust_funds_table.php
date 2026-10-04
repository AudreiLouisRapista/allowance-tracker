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
        Schema::create('request_funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('purchase_requests')->restrictOnDelete();
            $table->foreignId('parent_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->dateTime('created_at')->useCurrent();

            $table->unique(['request_id', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reqeust_funds');
    }
};
