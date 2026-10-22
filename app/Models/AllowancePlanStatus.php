<?php

namespace App\Models;

use App\Services\TenantConnection;
use Illuminate\Database\Eloquent\Model;

class AllowancePlanStatus extends Model
{
    // Status lookups live inside the family's own database.
    protected $connection = TenantConnection::CONNECTION;

    protected $table = 'allowance_plan_status';

    protected $fillable = ['status'];
}
