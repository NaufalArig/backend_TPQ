<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Notification;


class Santri extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'jenis_kelamin',
        'tanggal_lahir',
        'nama_wali',
        'kontak_wali',
        'alamat',
        'tanggal_masuk',
        'status',
        'notifikasi_usia',
    ];

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
