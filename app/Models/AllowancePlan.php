<?php

namespace App\Models;

use App\Services\TenantConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class AllowancePlan extends Model
{
    // The frequencies the plan form accepts. The column is varchar(10).
    public const FREQUENCIES = ['weekly', 'biweekly', 'monthly'];

    // Allowance plans live inside the signed-in family's database.
    protected $connection = TenantConnection::CONNECTION;

    protected $table = 'allowance_plans';

    protected $fillable = [
        'student_id', 'created_by', 'plan_status_id', 'amount', 'frequency', 'start_date', 'next_payout_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date:Y-m-d',
        'next_payout_date' => 'date:Y-m-d',
    ];

    // The student who receives this allowance.
    /** @return BelongsTo<FamilyMember, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'student_id');
    }

    // The parent who created the plan.
    /** @return BelongsTo<FamilyMember, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'created_by');
    }

    // active / inactive, from allowance_plan_status.
    /** @return BelongsTo<AllowancePlanStatus, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(AllowancePlanStatus::class, 'plan_status_id');
    }

    // Every recorded disbursement for this plan (written in the payouts phase).
    /** @return HasMany<AllowancePayout, $this> */
    public function payouts(): HasMany
    {
        return $this->hasMany(AllowancePayout::class, 'plan_id');
    }

    // Reusable filter for plans whose status row is "active".
    /**
     * @param  Builder<AllowancePlan>  $query
     * @return Builder<AllowancePlan>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn(
            'plan_status_id',
            DB::connection(TenantConnection::CONNECTION)->table('allowance_plan_status')->where('status', 'active')->select('id')
        );
    }
}
