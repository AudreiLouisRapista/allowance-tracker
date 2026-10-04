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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('purchase_req_status_id')->constrained('purchase_req_status')->restrictOnDelete();
            $table->string('store_name', 150);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('lacking_amount', 10, 2)->default(0.00);
            $table->dateTime('requested_at');
            $table->dateTime('decided_at')->nullable();
            $table->dateTime('settled_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
