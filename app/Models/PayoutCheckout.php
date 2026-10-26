<?php

namespace App\Models;

use App\Services\TenantConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutCheckout extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    protected $connection = TenantConnection::CONNECTION;

    protected $table = 'payout_checkouts';

    protected $fillable = [
        'reference',
        'plan_id',
        'student_id',
        'amount',
        'cycle_date',
        'paymongo_checkout_id',
        'checkout_url',
        'paymongo_payment_id',
        'status',
        'allowance_payout_id',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cycle_date' => 'date:Y-m-d',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // The allowance plan this checkout belongs to.
    /** @return BelongsTo<AllowancePlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(AllowancePlan::class, 'plan_id');
    }

    // The student who will receive the allowance.
    /** @return BelongsTo<FamilyMember, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'student_id');
    }

    // The completed payout created after PayMongo confirms payment.
    /** @return BelongsTo<AllowancePayout, $this> */
    public function payout(): BelongsTo
    {
        return $this->belongsTo(AllowancePayout::class, 'allowance_payout_id');
    }

    // Limit a query to checkouts that are still waiting for payment.
    /**
     * @param  Builder<PayoutCheckout>  $query
     * @return Builder<PayoutCheckout>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
