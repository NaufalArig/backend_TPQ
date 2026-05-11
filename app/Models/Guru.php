<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'alamat',
        'kontak',
        'tanggal_masuk',
        'tanggal_keluar',
        'status',
    ];
}
