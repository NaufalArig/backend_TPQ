<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsensiSantri extends Model
{
    protected $table = 'student_attendances';

    protected $fillable = [
        'student_id',
        'user_id',
        'attendance_date',
        'status',
        'note',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Santri::class, 'student_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
