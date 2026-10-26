<?php

namespace App\Models;

use App\Services\TenantConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phonenumber
 * @property int $usr_status_id
 * @property int $usr_role_id
 * @property int $is_creator
 * @property Carbon|null $removed_at
 */
class FamilyMember extends Model
{
    // Each family member belongs to one status row.
    /** @return BelongsTo<UserStatus, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(UserStatus::class, 'usr_status_id');
    }

    // Each family member belongs to one role row.
    /** @return BelongsTo<UserRole, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(UserRole::class, 'usr_role_id');
    }

    // Use the "tenant" connection, which jwt.auth points at the signed-in family's database.
    protected $connection = TenantConnection::CONNECTION;

    // Family members are users with either the parent or student role.
    protected $table = 'users';

    // The only columns that create() and update() may fill. Anything else is ignored.
    protected $fillable = [
        'usr_status_id', 'usr_role_id', 'name', 'email', 'phonenumber', 'is_creator', 'removed_at',
    ];

    protected $casts = ['removed_at' => 'datetime'];

    // Reusable filter for places that specifically need users with the student role.
    /**
     * @param  Builder<FamilyMember>  $query
     * @return Builder<FamilyMember>
     */
    public function scopeOnlyStudents(Builder $query): Builder
    {
        return $query->whereIn(
            'usr_role_id',
            DB::connection(TenantConnection::CONNECTION)->table('user_role')->where('role', 'student')->select('id')
        );
    }

    // Reusable filter for active family members.
    /**
     * @param  Builder<FamilyMember>  $query
     * @return Builder<FamilyMember>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn(
            'usr_status_id',
            DB::connection(TenantConnection::CONNECTION)->table('user_status')->where('status', 'active')->select('id')
        );
    }

    // Reusable filter for places that specifically need users with the parent role.
    /**
     * @param  Builder<FamilyMember>  $query
     * @return Builder<FamilyMember>
     */
    public function scopeOnlyParents(Builder $query): Builder
    {
        return $query->whereIn(
            'usr_role_id',
            DB::connection(TenantConnection::CONNECTION)->table('user_role')->where('role', 'parent')->select('id')
        );
    }

    // Reusable filter for removed family members.
    /**
     * @param  Builder<FamilyMember>  $query
     * @return Builder<FamilyMember>
     */
    public function scopeRemoved(Builder $query): Builder
    {
        return $query->whereIn(
            'usr_status_id',
            DB::connection(TenantConnection::CONNECTION)->table('user_status')->where('status', 'removed')->select('id')
        );
    }
}
