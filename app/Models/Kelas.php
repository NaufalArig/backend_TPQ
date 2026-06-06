<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    protected $table = 'study_classes';

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    public function santris()
    {
        return $this->hasMany(Santri::class, 'study_class_id');
    }
}
