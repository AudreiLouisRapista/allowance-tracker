<?php

namespace App\Models;

use App\Services\TenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllowancePayout extends Model
{
    // Payouts are written in the payouts phase; the plans page only reads them.
    protected $connection = TenantConnection::CONNECTION;

    protected $table = 'allowance_payouts';

    // The table only has created_at, so Eloquent must not look for updated_at.
    public $timestamps = false;

    protected $fillable = [
        'plan_id',
        'student_id',
        'amount',
        'cycle_date',
        'paid_at',
        'paymongo_checkout_id',
        'paymongo_payment_id',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cycle_date' => 'date:Y-m-d',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // The allowance plan this payout was made from.
    /** @return BelongsTo<AllowancePlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(AllowancePlan::class, 'plan_id');
    }

    // The student who received this payout.
    /** @return BelongsTo<FamilyMember, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'student_id');
    }
}
