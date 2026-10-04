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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usr_status_id')->constrained('user_status')->restrictOnDelete();
            $table->foreignId('usr_role_id')->constrained('user_role')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('email', 255)->unique();
            $table->string('google_id', 64)->nullable()->unique();
            $table->boolean('is_creator');
            $table->dateTime('removed_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
