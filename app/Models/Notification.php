<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Santri;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'santri_id',
        'judul',
        'pesan',
        'dibaca',
    ];

    public function santri()
    {
        return $this->belongsTo(Santri::class);
    }
}
