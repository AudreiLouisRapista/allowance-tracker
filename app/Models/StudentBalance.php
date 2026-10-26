<?php

namespace App\Models;

use App\Services\TenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentBalance extends Model
{
    protected $connection = TenantConnection::CONNECTION;

    protected $table = 'student_balance';

    protected $primaryKey = 'student_id';

    public $incrementing = false;

    protected $keyType = 'int';

    // The table keeps its own updated_at and has no created_at column.
    public $timestamps = false;

    protected $fillable = ['student_id', 'balance', 'updated_at'];

    protected $casts = [
        'balance' => 'decimal:2',
        'updated_at' => 'datetime',
    ];

    // The student whose running allowance balance this row stores.
    /** @return BelongsTo<FamilyMember, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'student_id');
    }
}
