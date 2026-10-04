<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users_directory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')
                ->constrained('families')
                ->restrictOnDelete();
            $table->string('email', 255)->unique();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_directory');
    }
};