<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasFactory;
    use HasRoles;
    use HasUuids;
    use Notifiable;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name', 'email', 'password', 'role_id', 'department_id'
    ];

    protected $primaryKey = 'id';

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function recruitmentRequests(): HasMany
    {
        return $this->hasMany(RecruitmentRequest::class, 'requested_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'approved_by');
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(\App\Models\UserNotification::class, 'user_id');
    }

    public function handleRecruitment(): HasMany
    {
        return $this->hasMany(RecruitmentRequest::class, 'pic_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $this->loadMissing(['department', 'roles']);

        $deptName = optional($this->department)->name;
        $isHrDepartment = $deptName && Str::upper(trim($deptName)) === 'HUMAN RESOURCE';

        $hasDirectionRole = $this->roles()
            ->whereRaw('UPPER(TRIM(name)) = ?', ['Director'])
            ->exists();

        return $isHrDepartment || $hasDirectionRole;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name);
    }
}
