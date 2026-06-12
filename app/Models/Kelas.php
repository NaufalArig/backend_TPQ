<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    protected $table = 'study_classes';

    protected $fillable = [
        'tpq_id',
        'teacher_id',
        'name',
        'description',
        'status',
    ];

    public function santris()
    {
        return $this->hasMany(Santri::class, 'study_class_id');
    }

    public function tpq()
    {
        return $this->belongsTo(Tpq::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Guru::class);
    }

    public function students()
    {
        return $this->hasMany(Santri::class);
    }
}
