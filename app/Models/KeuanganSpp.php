<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeuanganSpp extends Model
{
    protected $table = 'tuition_payments';

    protected $fillable = [
        'student_id',
        'tpq_id',
        'user_id',
        'payment_date',
        'month',
        'year',
        'amount',
        'note',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'month' => 'integer',
        'year' => 'integer',
        'amount' => 'decimal:2',
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
