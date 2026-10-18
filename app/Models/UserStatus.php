<?php

namespace App\Models;

use App\Services\TenantConnection;  
use Illuminate\Database\Eloquent\Model;

class UserStatus extends Model
{
    protected $connection = TenantConnection::CONNECTION; // The family's database.
    protected $table = 'user_status';                     // Not "user_statuses", so say the name.
    public $timestamps = false;   
}
