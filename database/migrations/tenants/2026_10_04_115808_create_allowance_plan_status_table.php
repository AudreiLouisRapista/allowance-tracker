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
        Schema::create('allowance_plan_status', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->unique();
        });

        DB::table('allowance_plan_status')->insert([
            ['status' => 'active'],
            ['status' => 'inactive'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allowance_plan_status');
    }
};
