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
        Schema::table('allowance_payouts', function (Blueprint $table) {
            // Keep the PayMongo references on the completed payout for auditing.
            $table->string('paymongo_checkout_id', 64)->nullable()->after('paid_at');
            $table->string('paymongo_payment_id', 64)->nullable()->after('paymongo_checkout_id');
        });

        Schema::create('payout_checkouts', function (Blueprint $table) {
            $table->id();
            // A local reference shared with PayMongo metadata so a webhook can find
            // the checkout even if it arrives before we save PayMongo's id.
            $table->string('reference', 64)->unique();
            $table->foreignId('plan_id')->constrained('allowance_plans')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('cycle_date');
            $table->string('paymongo_checkout_id', 64)->nullable()->unique();
            $table->text('checkout_url')->nullable();
            $table->string('paymongo_payment_id', 64)->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('allowance_payout_id')->nullable()->constrained('allowance_payouts')->nullOnDelete();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();

            $table->index(['plan_id', 'cycle_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_checkouts');

        Schema::table('allowance_payouts', function (Blueprint $table) {
            $table->dropColumn(['paymongo_checkout_id', 'paymongo_payment_id']);
        });
    }
};
