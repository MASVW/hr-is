<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)
            ->withTimestamps();
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
        $isHrDepartment = $this->departments()
            ->whereRaw('UPPER(TRIM(name)) = ?', ['HRD'])
            ->exists();

        $hasDirectorRole = $this->roles()
            ->whereRaw('UPPER(TRIM(name)) = ?', ['DIRECTOR'])
            ->exists();

        return $isHrDepartment || $hasDirectorRole;
    }
    public function isTeamLeader(): bool
    {
        return $this->hasRole('Team Leader');
    }

    public function isStaff(): bool
    {
        return $this->hasRole('Staff');
    }
    public function isManager(): bool
    {
        return $this->hasRole('Manager');
    }
    public function isAssMan(): bool
    {
        return $this->hasRole('Asmen');
    }
    public function isDirector(): bool
    {
        return $this->hasRole('Director');
    }
    public function isSPV(): bool
    {
        return $this->hasRole('SPV');
    }
    public function isSU(): bool
    {
        return $this->hasRole('SU');
    }
    public function isHrDept(): bool
    {
        return $this->departments()
            ->where('name', 'HUMAN RESOURCE')
            ->exists();
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name);
    }
}
