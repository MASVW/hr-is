<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasFactory;
    protected $table = 'user_notifications';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['user_id', 'message', 'is_read'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
