<?php

namespace App\Models;

use App\Services\TenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequestStatus extends Model
{
    // The status values stored in purchase_req_status.
    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const CANCELLED = 'cancelled';

    // Status rows live inside the signed-in family's database.
    protected $connection = TenantConnection::CONNECTION;

    protected $table = 'purchase_req_status';

    // The lookup table only has an id and a status name.
    public $timestamps = false;

    protected $fillable = ['status'];

    // Every purchase request currently using this status.
    /** @return HasMany<PurchaseRequest, $this> */
    public function requests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class, 'purchase_req_status_id');
    }
}
