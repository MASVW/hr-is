<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Approval extends Model
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'approved_at' => 'datetime'
    ];

    protected $fillable = [
        'request_id',
        'status',
        'hrd_approval',
        'chairman_approval',
        'is_closed',
        'approved_at',
        'reason'
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequest::class, 'request_id');
    }

    public function requestApproval(): HasOne
    {
        return $this->hasOne(RecruitmentRequest::class, 'approval_id');
    }
}
