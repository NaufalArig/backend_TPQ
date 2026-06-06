<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    protected $table = 'teachers';

    protected $fillable = [
        'user_id',
        'teacher_number',
        'tpq_number',
        'name',
        'gender',
        'birth_place',
        'birth_date',
        'address',
        'village',
        'district',
        'city',
        'province',
        'phone',
        'certificate_from',
        'certificate_number',
        'education',
        'join_date',
        'leave_date',
        'status',
        'photo',
        'age_notification_sent',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'join_date' => 'date',
        'leave_date' => 'date',
        'age_notification_sent' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
