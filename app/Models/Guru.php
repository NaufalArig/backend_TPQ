<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nama',
        'alamat',
        'kontak',
        'tanggal_masuk',
        'tanggal_keluar',
        'status',
        'foto',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
