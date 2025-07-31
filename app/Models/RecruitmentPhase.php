<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RecruitmentPhase extends Model
{
    use HasFactory;
    use HasUuids;
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $casts = [
        'form_data' => 'array',
        'started_at' => 'datetime',
        'finishAt' => 'datetime',
    ];

    protected $fillable = [
        'request_id',
        'phase_type',
        'status',
        'started_at',
        'finish_at',
        'form_data',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequest::class, 'request_id');
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(Approval::class);
    }

    public function recruitmentRequest(): HasOne
    {
        return $this->hasOne(RecruitmentRequest::class, 'phase_id');
    }
}

