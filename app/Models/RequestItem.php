<?php

namespace App\Models;

use App\Services\TenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestItem extends Model
{
    // Items live in the same family database as their request.
    protected $connection = TenantConnection::CONNECTION;

    protected $table = 'request_items';

    // The table only has created_at, so Eloquent must not look for updated_at.
    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'material_name',
        'quantity',
        'unit_price',
        'line_total',
        'created_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    // The purchase request this line belongs to.
    /** @return BelongsTo<PurchaseRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class, 'request_id');
    }
}
