<?php

namespace App\Models;

use App\Services\TenantConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Student extends Model
{
    // Each student belongs to ONE status row. usr_status_id (here) points to user_status.id (there).
    public function status(): BelongsTo
    {
        return $this->belongsTo(UserStatus::class, 'usr_status_id');
    }

    // Each student belongs to ONE role row. usr_role_id points to user_role.id.
    public function role(): BelongsTo
    {
        return $this->belongsTo(UserRole::class, 'usr_role_id');
    }

    // Use the "tenant" connection, which jwt.auth points at the signed-in family's database.
    protected $connection = TenantConnection::CONNECTION;

    // The table that holds the rows. Students are rows of "users" whose role is "student".
    protected $table = 'users';

    // The only columns that create() and update() may fill. Anything else is ignored.
    protected $fillable = [
        'usr_status_id', 'usr_role_id', 'name', 'email', 'phonenumber', 'is_creator', 'removed_at',
    ];

    protected $casts = ['removed_at' => 'datetime'];

    // Reusable filter: only users whose role is "student". Used as Student::onlyStudents().
    public function scopeOnlyStudents(Builder $query): Builder
    {
        return $query->whereIn(
            'usr_role_id',
            DB::connection(TenantConnection::CONNECTION)->table('user_role')->where('role', 'student')->select('id')
        );
    }

    // Reusable filter: only users whose status is "active". Used as Student::active().
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn(
            'usr_status_id',
            DB::connection(TenantConnection::CONNECTION)->table('user_status')->where('status', 'active')->select('id')
        );
    }

    // Reusable filter: only users whose status is "active". Used as Student::active().
    public function scopeRemoved(Builder $query): Builder
    {
        return $query->whereIn(
            'usr_status_id',
            DB::connection(TenantConnection::CONNECTION)->table('user_status')->where('status', 'removed')->select('id')
        );
    }
}