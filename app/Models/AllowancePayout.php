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

    protected $fillable = ['plan_id', 'student_id', 'amount', 'cycle_date', 'paid_at', 'created_at'];

    protected $casts = [
        'amount' => 'decimal:2',
        'cycle_date' => 'date:Y-m-d',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(AllowancePlan::class, 'plan_id');
    }
}
