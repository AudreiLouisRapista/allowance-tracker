<?php

namespace App\Models;

use App\Services\TenantConnection;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    protected $connection = TenantConnection::CONNECTION;
    protected $table = 'user_role';
    public $timestamps = false;
}
