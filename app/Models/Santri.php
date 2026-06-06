<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Santri extends Model
{
    protected $table = 'students';

    protected $fillable = [
        'study_class_id',
        'student_number',
        'tpq_number',
        'name',
        'nisn',
        'nik',
        'family_card_number',
        'gender',
        'birth_place',
        'birth_date',
        'join_date',
        'child_order',
        'siblings_count',
        'father_name',
        'mother_name',
        'guardian_phone',
        'hamlet',
        'village',
        'district',
        'city',
        'province',
        'formal_school',
        'formal_class',
        'npsn',
        'student_type',
        'status',
        'photo',
        'family_card_file',
        'birth_certificate_file',
        'age_notification_sent',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'join_date' => 'date',
        'age_notification_sent' => 'boolean',
    ];

    public function studyClass()
    {
        return $this->belongsTo(Kelas::class, 'study_class_id');
    }

    public function attendances()
    {
        return $this->hasMany(AbsensiSantri::class, 'student_id');
    }

    public function tuitionPayments()
    {
        return $this->hasMany(KeuanganSpp::class, 'student_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'student_id');
    }
}
