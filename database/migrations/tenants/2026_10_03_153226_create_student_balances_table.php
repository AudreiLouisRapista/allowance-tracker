<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_balances', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->primary();
            $table->foreign('student_id')->references('id')->on('users')->restrictOnDelete();
            $table->decimal('balance', 10, 2)->default(0.00);
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // Laravel's schema builder has no check-constraint helper, so this is raw SQL.
        DB::statement(
            'ALTER TABLE student_balances ADD CONSTRAINT student_balances_balance_check CHECK (balance >= 0)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('student_balances');
    }
};