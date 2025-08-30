<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RecruitmentRequest extends Model
{
    use HasFactory;
    use HasUuids;
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $casts = [
        'form_data' => 'array',
    ];
    protected $fillable = [
        'pic_id',
        'phase_id',
        'status',
        'title',
        'department_id',
        'requested_by',
        'approval_id',
        'form_data',
        'recruitment_type'
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(Approval::class, 'approval_id');
    }

    public function recruitmentApproval(): HasOne
    {
        return $this->hasOne(Approval::class, 'request_id');
    }

    public function recruitmentPhase(): HasOne
    {
        return $this->hasOne(RecruitmentPhase::class, 'request_id');
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(RecruitmentPhase::class, 'phase_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }
}

