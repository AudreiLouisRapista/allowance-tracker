<?php

namespace App\Models;

use App\Services\TenantConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PurchaseRequest extends Model
{
    // The four lifecycle states from purchase_req_status.
    public const STATUS_PENDING = PurchaseRequestStatus::PENDING;

    public const STATUS_APPROVED = PurchaseRequestStatus::APPROVED;

    public const STATUS_REJECTED = PurchaseRequestStatus::REJECTED;

    public const STATUS_CANCELLED = PurchaseRequestStatus::CANCELLED;

    // Purchase requests live inside the signed-in family's database.
    protected $connection = TenantConnection::CONNECTION;

    protected $table = 'purchase_requests';

    protected $fillable = [
        'student_id',
        'purchase_req_status_id',
        'store_name',
        'total_amount',
        'lacking_amount',
        'requested_at',
        'decided_at',
        'settled_at',
        'cancelled_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'lacking_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'decided_at' => 'datetime',
        'settled_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // The student who asked for the purchase.
    /** @return BelongsTo<FamilyMember, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'student_id');
    }

    // pending / approved / rejected / cancelled, from purchase_req_status.
    /** @return BelongsTo<PurchaseRequestStatus, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestStatus::class, 'purchase_req_status_id');
    }

    // The line items that make up this request.
    /** @return HasMany<RequestItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(RequestItem::class, 'request_id')->orderBy('id');
    }

    // Reusable filter for requests that are still waiting for a decision.
    /**
     * @param  Builder<PurchaseRequest>  $query
     * @return Builder<PurchaseRequest>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn(
            'purchase_req_status_id',
            DB::connection(TenantConnection::CONNECTION)
                ->table('purchase_req_status')
                ->where('status', self::STATUS_PENDING)
                ->select('id')
        );
    }
}
