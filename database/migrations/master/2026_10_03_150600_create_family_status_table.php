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
        Schema::create('family_status', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->unique();
        });

        // Lookup rows live in the migration so the ids are always the same.
        DB::table('family_status')->insert([
            ['status' => 'active'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_status');
    }
};
