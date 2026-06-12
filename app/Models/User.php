<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'tpq_id',
        'username',
        'email',
        'password',
        'role',
        'status',
        'photo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function guru()
    {
        return $this->hasOne(Guru::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function tpq()
    {
        return $this->belongsTo(Tpq::class);
    }

    public function teacher()
    {
        return $this->hasOne(Guru::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}
