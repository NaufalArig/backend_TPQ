<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'tpq_id',
        'title',
        'message',
        'type',
        'is_read',
        'user_id',
        'student_id',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Santri::class, 'student_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function tpq()
    {
        return $this->belongsTo(Tpq::class);
    }
}
