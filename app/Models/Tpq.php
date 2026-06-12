<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tpq extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'head_name',
        'status',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function students()
    {
        return $this->hasMany(Santri::class);
    }

    public function teachers()
    {
        return $this->hasMany(Guru::class);
    }

    public function studyClasses()
    {
        return $this->hasMany(Kelas::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}
