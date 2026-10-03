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
        Schema::create('allowance_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('allowance_plan')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('cycle_date');
            $table->dateTime('paid_at');
            $table->dateTime('created_at')->useCurrent();

            $table->unique(['plan_id', 'cycle_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allowance_payouts');
    }
};
