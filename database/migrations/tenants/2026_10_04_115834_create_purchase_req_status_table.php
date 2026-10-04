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
        Schema::create('purchase_req_status', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->unique();
        });

        DB::table('purchase_req_status')->insert([
            ['status' => 'pending'],
            ['status' => 'approved'],
            ['status' => 'rejected'],
            ['status' => 'cancelled'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_req_status');
    }
};
