<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsensiSantri extends Model
{
    use HasFactory;

    protected $fillable = [
        'santri_id',
        'user_id',
        'tanggal',
        'status',
        'keterangan',
    ];

    public function santri()
    {
        return $this->belongsTo(Santri::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
